FROM php:8.4-fpm-alpine

# Install dependencies, Nginx, and Supervisor
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite sqlite-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite zip opcache

# Install Composer
COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
ENV APP_ENV=prod
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
COPY docker/entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
