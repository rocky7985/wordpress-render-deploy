<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'railway' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'iPXTtoXmShTjkbcwYOzRwJMalHTlUDWH' );

/** Database hostname */
define( 'DB_HOST', 'interchange.proxy.rlwy.net:54192' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

define('JWT_AUTH_CORS_ENABLE', true);


/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'TA^|=a<HbWnUD3o)G$?le5ZO_x;9Uq.i`vOU,]+fnx7)%B FhS&sg/G&{}fl;p!/' );
define( 'SECURE_AUTH_KEY',  'S3ctsZ?qyh}tlw6a=*=+_KDfR5^hcYZM6+vc-AXUc[.PwmA<AJ$-F&a]wvqwQg3L' );
define( 'LOGGED_IN_KEY',    '|$+-m )*:/1_rzm$X}|}2aWa}N3!,2|@_3o[4A=LCq;WFn|.QY@w*c>/V}X=>D1?' );
define( 'NONCE_KEY',        'Y^TG[q&1Q=^Vk#in5#Vy8_tq~PfR GiJs{WSr7R~H.%Q%{2&`GX_{i!-~bOb/bQu' );
define( 'AUTH_SALT',        '4Ng-Z`t<B42Umr&g};Lhr=rt `}H<()L/@oy5 {+=j(Sy@ u_}A@<FUSX+pH(as!' );
define( 'SECURE_AUTH_SALT', '{0eI7Vj(.~5~6Ay__GTt7I)jlo3EV48s2>R#t@S`b*I*l4Hl6=&1!Yrp66~ /%Ek' );
define( 'LOGGED_IN_SALT',   'B7Pb6ZzM6n*DVR0aKbWQyaN!Gl)@DFus pfa{i=7>dahC`uX?|E!QpuQ&/ft=X7:' );
define( 'NONCE_SALT',       'VX.Vm?jH.bKImeJzE|pO`pXym[ZQ7Gg%l?Q)*?/?HC|2uIA>yfVse3HC$#_`yi[' );
define('JWT_AUTH_SECRET_KEY', 'your-top-secret-key');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
