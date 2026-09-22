FROM php:8.2-fpm-alpine

# Install dependencies, Nginx, and Supervisor
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite sqlite-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite zip opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Setup Nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Setup Supervisor configuration
COPY docker/supervisord.conf /etc/supervisord.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/data /var/www/html/public/media \
    && chmod -R 775 /var/www/html/var /var/www/html/data /var/www/html/public/media

# Expose port 80
EXPOSE 80

# Start Supervisor (which will start Nginx and PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
