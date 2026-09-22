#!/bin/sh
# Ensure directories exist
mkdir -p /var/www/html/data /var/www/html/public/media /var/www/html/var

# Fix permissions for the mounted volumes
chown -R www-data:www-data /var/www/html/data /var/www/html/public/media /var/www/html/var

# Execute the main command
exec "$@"
