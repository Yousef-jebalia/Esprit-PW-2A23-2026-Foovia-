# Use the official PHP image with Apache
FROM php:8.2-apache

# Install any PHP extensions you might need (e.g., for MySQL)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy all your project files into the Apache web root
COPY . /var/www/html/

# Tell Apache to listen on the port Render provides
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set permissions so Apache can read your files
RUN chown -R www-data:www-data /var/www/html