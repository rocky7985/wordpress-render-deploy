FROM wordpress:latest

# Copy your local WordPress files to the container
COPY . /var/www/html/

# Set proper permissions (optional but recommended)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
