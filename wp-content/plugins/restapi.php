<?php
require_once( ABSPATH.'wp-admin/includes/user.php' ); 
require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' ); 
require_once( ABSPATH . 'wp-admin/includes/media.php' );


/**
 *
 * @wordpress-plugin
 * Plugin Name: Creator Rest API
 * Description: Test.
 * Version: 1.0
 * Author: Kanishk
**/ 

use Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class Custom_API extends WP_REST_Controller {
    private $api_namespace;
	private $api_version;
	private $required_capability;
	public  $user_token;
	public  $user_id;
	
	public function __construct() {
		$this->api_namespace = 'addapi/v';
		$this->api_version = '1';
		$this->required_capability = 'read';
		$this->init();
		/*------- Start: Validate Token Section -------*/
		$headers = getallheaders(); 
		if(isset($headers['Authorization'])){ 
        	if(preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)){ 
            	$this->user_token =  $matches[1]; 
        	}
        }
        /*------- End: Validate Token Section -------*/
	}
    
	private function successResponse($message='',$data=array(),$total = array()){ 
        $response =array();
        $response['status'] = "success";
        $response['message'] =$message;
        $response['data'] = $data;
        if(!empty($total)){
            $response['pagination'] = $total;
        }
        return new WP_REST_Response($response, 200);  
    }
     
    public function errorResponse($message='',$type='ERROR' , $statusCode = 400){
        $response = array();
        $response['status'] = "error";
        $response['error_type'] = $type;
        $response['message'] =$message;
        return new WP_REST_Response($response, $statusCode); 
    }

    public function register_routes(){  
		$namespace = $this->api_namespace . $this->api_version;
		
	    $privateItems = array('filter_response_codes', 'save_filtered_list', 'check_filtered_list_exists', 'get_saved_lists', 'delete_saved_list', 'edit_saved_list'); //Api Name  and use to token
	    $publicItems  = array('signup'); //no needs for token 
		
		
		foreach($privateItems as $Item){
		    register_rest_route( $namespace, '/'.$Item, array( 
                'methods' => 'POST',    
                'callback' => array( $this, $Item), 
               'permission_callback' => !empty($this->user_token)?'__return_true':'__return_false' 
				//'permission_callback' => array( $this, 'isValidToken' ) // Ensure token is validated

                )  
	    	);  
		}
		foreach($publicItems as $Item){
		  	register_rest_route( $namespace, '/'.$Item, array(
                'methods' => 'POST',
                'callback' => array( $this, $Item )
                )
	    	);
		}
	}

	public function init(){
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        // add_action('rest_api_init', 'add_custom_headers');
        add_action( 'rest_api_init', function() {
        remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
            add_filter( 'rest_pre_serve_request', function( $value ) {
                header( 'Access-Control-Allow-Origin: *' );
                header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
                header( 'Access-Control-Allow-Credentials: true' );
                header("Access-Control-Allow-Headers: Authorization, Content-Type");
                return $value;
            });
        }, 15 );
     
    }

    public function signup($request){
        global $wpdb;
        $param = $request->get_params();
        // $role  = 'customer';
    
        // Check if email is provided
        if(empty($param['email'])){
            return $this->errorResponse('Please provide email.');
        }
    
        // Check if email already exists
        if(email_exists($param['email'])){
            return $this->errorResponse('Email already exists.Please Login');
        }
    
        // Check if password and confirmPassword are provided and match
        if(empty($param['password']) || empty($param['confirmPassword'])){
            return $this->errorResponse('Please provide password and confirmPassword.');
        }
    
        if($param['password'] !== $param['confirmPassword']){
            return $this->errorResponse('Password does not match.');
        }

         // Check if role provided
        //  if(empty($param['role'])) {
        //     return $this->errorResponse('Please specify role');
        // }

         // Check if role provided
         if(empty($param['phone'])) {
            return $this->errorResponse('Please specify phone');
        }
         // Check if role provided
         if(empty($param['address'])) {
            return $this->errorResponse('Please specify address');
        }

            // Create user
        $user_id = wp_create_user($param['email'],$param['password'],$param['email']);
        if(is_wp_error($user_id)) {
            return $this->errorResponse($user_id->get_error_message());
        }

        $user = new WP_User($user_id);
        $user->set_role($param['role']);
    
        // Update user meta
        update_user_meta($user_id, 'name', $param['name']);
        update_user_meta($user_id, 'phone', $param['phone']);
        update_user_meta($user_id, 'address', $param['address']);
      
        // Get user profile data
        // $data = $this->getProfile($user_id);
    
        // Check if user was successfully registered
        if(!empty($user_id)){
            return $this->successResponse('User created successfully.', $user);
        } else {
            return $this->errorResponse('Something went wrong. Please try again later.', $user);
        }
    }
    
    private function isValidToken(){
    	$this->user_id  = $this->getUserIdByToken($this->user_token);
    }

    public function getUserIdByToken($token){
        $decoded_array = array();
        $user_id = 0;
        if($token){
            try{
                $decoded = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, apply_filters('jwt_auth_algorithm', 'HS256')));
                $decoded_array = (array) $decoded;
            }catch(\Firebase\JWT\ExpiredException $e){
                return false;
            }
        }
        if(count($decoded_array) > 0){
            $user_id = $decoded_array['data']->user->id;
        }
        if($this->isUserExists($user_id)){
            return $user_id;
        }else{
            return false;
        }
    }

    public function isUserExists($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user));
        if ($count == 1) {return true;} else {return false;}
    }

    public function jwt_auth($data, $user){
		    $user_meta = get_user_meta($user->ID);
            $user_roles = $user->roles[0]; // Fetching roles from WP_User object
            // Get user roles
            $result = $this->getProfile($user->ID);
            $result['token'] =  $data['token'];
               return $this->successResponse('Successfully Logged In', $result);

       

        $code = $data['code'];

        if($code == '[jwt_auth] incorrect_password'){
            return $this->errorResponse('The password you entered is incorrect');
        }

        elseif($code == '[jwt_auth] invalid_email'  || $code == '[jwt_auth] invalid_username'){
            return $this->errorResponse('The email you entered is incorrect');
        }

		elseif($code == '[jwt_auth] empty_username'){
            return $this->errorResponse('The username field is empty.');
        }

        elseif($code == '[jwt_auth] empty_password'){
            return $this->errorResponse('The password field is empty.');
        }
		return $user;
    }

     public function getProfile($user_id){

        if (empty($user_id)) {
            return $this->errorResponse('Unauthorized', 'Unauthorized', 401);
        }

        $user = get_user_by('ID', $user_id);       

        $profile = array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'name' => get_user_meta($user->ID, 'name', true),
            'phone' => get_user_meta($user->ID, 'phone', true),
            'address' => get_user_meta($user->ID, 'address', true)
            // 'roles' => $user->roles[0],
        );

        return $profile;
    
    }

    public function filter_response_codes($request){
    $params = $request->get_params();
    $filter = isset($params['filter']) ? sanitize_text_field($params['filter']) : '';

    if(empty($filter)){
        return $this->errorResponse('Filter parameter missing');
    }

    // Get all HTTP dog codes (standard ones for http.dog)
    $all_codes = [
        "100", "101", "102", "103",
        "200", "201", "202", "203", "204", "205", "206", "207", "208", "218", "226",
        "300", "301", "302", "303", "304", "305", "306", "307", "308",
        "400", "401", "402", "403", "404", "405", "406", "407", "408", "409", "410",
        "411", "412", "413", "414", "415", "416", "417", "418", "419", "420", "421", "422",
        "423", "424", "425", "426", "428", "429", "430", "431", "440", "444", "449", "450", "451", "460", "463", "464", "494", "495", "496", "497", "498",
        "499", "500", "501", "502", "503", "504", "505", "506", "507", "508", "509",
        "510", "511", "520", "521", "522", "523", "524", "525", "526", "527", "529", "530", "561", "598", "599",
        "999"
    ];

    // Convert filter string with 'x' to regex
    // Examples: 2xx -> ^2\d\d$, 20x -> ^20\d$
    $regex = '/^' . str_replace('x', '\d', $filter) . '$/';

    $matched_codes = array_filter($all_codes, function($code) use ($regex){
        return preg_match($regex, $code);
    });

    $result = [];
    foreach($matched_codes as $code){
        $result[] = [
            'code' => $code,
            'image_url' => "https://http.dog/{$code}.jpg"
        ];
    }

    return $this->successResponse('Filtered response codes', $result);
}

public function save_filtered_list($request) {
    global $wpdb;

    $params = $request->get_params();
    $name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
    $data = isset($params['data']) ? $params['data'] : [];

    if (empty($name)) {
        return $this->errorResponse('List name is required.');
    }

    if (empty($data) || !is_array($data)) {
        return $this->errorResponse('Data is missing or invalid.');
    }

    // Extract and sanitize codes and image URLs
    $codes = [];
    $image_urls = [];

    foreach ($data as $item) {
        if (isset($item['code']) && isset($item['image_url'])) {
            $codes[] = sanitize_text_field($item['code']);
            $image_urls[] = esc_url_raw($item['image_url']);
        }
    }

    if (empty($codes) || empty($image_urls)) {
        return $this->errorResponse('No valid codes or image URLs found.');
    }

    if (count($codes) !== count($image_urls)) {
        return $this->errorResponse('Mismatch in count of codes and image URLs.');
    }

    // Get authenticated user ID
    $user_id = $this->getUserIdByToken($this->user_token);
    if (!$user_id) {
        return $this->errorResponse('Unauthorized user.', 'Unauthorized', 401);
    }

    $table_name = 'saved_lists';
    $json_codes = wp_json_encode($codes);
    $json_image_urls = wp_json_encode($image_urls);

    // ✅ Check if list already exists for this user with same codes and image URLs
    $existing = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND code = %s AND image_url = %s",
            $user_id,
            $json_codes,
            $json_image_urls
        )
    );

    if ($existing > 0) {
        return $this->errorResponse('List already saved with the same items.');
    }

    // ✅ Insert into DB
    $inserted = $wpdb->insert($table_name, array(
        'user_id' => $user_id,
        'name' => $name,
        'code' => $json_codes,
        'image_url' => $json_image_urls,
        'created_date' => current_time('mysql')
    ));

    if ($inserted) {
        return $this->successResponse('List saved successfully.');
    } else {
        return $this->errorResponse('Failed to save list. DB Error: ' . $wpdb->last_error);
    }
}

public function check_filtered_list_exists($request) {
    global $wpdb;

    $params = $request->get_params();
    $data = isset($params['data']) ? $params['data'] : [];

    if (empty($data) || !is_array($data)) {
        return $this->errorResponse('Data is missing or invalid.');
    }

    // Extract and sanitize codes and image URLs
    $codes = [];
    $image_urls = [];

    foreach ($data as $item) {
        if (isset($item['code']) && isset($item['image_url'])) {
            $codes[] = sanitize_text_field($item['code']);
            $image_urls[] = esc_url_raw($item['image_url']);
        }
    }

    if (empty($codes) || empty($image_urls)) {
        return $this->errorResponse('No valid codes or image URLs found.');
    }

    $user_id = $this->getUserIdByToken($this->user_token);
    if (!$user_id) {
        return $this->errorResponse('Unauthorized user.', 'Unauthorized', 401);
    }

    $table_name = 'saved_lists';
    $json_codes = wp_json_encode($codes);
    $json_image_urls = wp_json_encode($image_urls);

    $existing = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND code = %s AND image_url = %s",
            $user_id,
            $json_codes,
            $json_image_urls
        )
    );

    return $this->successResponse(
        $existing > 0 ? 'List already exists.' : 'List does not exist.',
        ['exists' => $existing > 0]
    );
}

public function get_saved_lists($request) {
    global $wpdb;

    $user_id = $this->getUserIdByToken($this->user_token);
    if (!$user_id) {
        return $this->errorResponse('Unauthorized user.', 'Unauthorized', 401);
    }

    $table_name = 'saved_lists';
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name, code, image_url, created_date FROM {$table_name} WHERE user_id = %d ORDER BY created_date DESC",
            $user_id
        )
    );

    if (empty($results)) {
        return $this->successResponse('No saved lists found.', []);
    }

    // Format response
    $formatted = array_map(function ($row) {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'codes' => json_decode($row->code),
            'image_urls' => json_decode($row->image_url),
            'created_date' => $row->created_date
        ];
    }, $results);

    return $this->successResponse('Saved lists retrieved.', $formatted);
}

public function delete_saved_list($request) {
    global $wpdb;

    $params = $request->get_params();
    $list_id = isset($params['id']) ? intval($params['id']) : 0;

    if (!$list_id) {
        return $this->errorResponse('List ID is required.');
    }

    $user_id = $this->getUserIdByToken($this->user_token);
    if (!$user_id) {
        return $this->errorResponse('Unauthorized user.', 'Unauthorized', 401);
    }

    $table_name = 'saved_lists';

    // Ensure the list belongs to the current user
    $exists = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE id = %d AND user_id = %d", $list_id, $user_id)
    );

    if (!$exists) {
        return $this->errorResponse('List not found or does not belong to you.');
    }

    $deleted = $wpdb->delete(
        $table_name,
        array('id' => $list_id, 'user_id' => $user_id),
        array('%d', '%d')
    );

    if ($deleted) {
        return $this->successResponse('List deleted successfully.');
    } else {
        return $this->errorResponse('Failed to delete the list. Try again.');
    }
}

public function edit_saved_list($request) {
    global $wpdb;

    $params = $request->get_params();
    $list_id = isset($params['id']) ? intval($params['id']) : 0;
    $new_name = isset($params['name']) ? sanitize_text_field($params['name']) : '';

    if (!$list_id || empty($new_name)) {
        return $this->errorResponse('List ID and new name are required.');
    }

    $user_id = $this->getUserIdByToken($this->user_token);
    if (!$user_id) {
        return $this->errorResponse('Unauthorized user.', 'Unauthorized', 401);
    }

    $table_name = 'saved_lists';

    // Check if the list belongs to this user
    $existing = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE id = %d AND user_id = %d",
            $list_id,
            $user_id
        )
    );

    if ($existing == 0) {
        return $this->errorResponse('List not found or does not belong to the user.');
    }

    // Update the name
    $updated = $wpdb->update(
        $table_name,
        ['name' => $new_name],
        ['id' => $list_id, 'user_id' => $user_id],
        ['%s'],
        ['%d', '%d']
    );

    if ($updated !== false) {
        return $this->successResponse('List name updated successfully.');
    } else {
        return $this->errorResponse('Failed to update list name. ' . $wpdb->last_error);
    }
}

}           
$serverApi = new Custom_API();
add_filter('jwt_auth_token_before_dispatch', array( $serverApi, 'jwt_auth' ), 20, 2);
add_action('wp_error_added',  array($serverApi, 'errorResponse'), 99, 3);
?>