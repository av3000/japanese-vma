#!/bin/bash

# Ensure directories exist and have correct permissions for www-data
# This is more direct than checking, to guarantee permissions on startup
mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache

# Set ownership to www-data for these directories
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Set generous write permissions for local development
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache

echo "Storage and cache permissions set for www-data."

# Wait for MySQL to be ready
while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" --silent; do
    echo "Waiting for database connection at $DB_HOST:$DB_PORT ..."
    sleep 2
done
echo "Database connection established!"

# Create common tables migrations (uncomment if you want this to run on every startup, but be careful with production)
# php artisan migrate --path=database/migrations/now

exec php-fpm

# # Check if permissions are already correct before adjusting
# if [ "$(stat -c '%U' storage)" != "www-data" ]; then
#     chown -R www-data:www-data storage bootstrap/cache
#     chmod -R 775 storage bootstrap/cache
#     echo "Permissions fixed."
# else
#     echo "Permissions already correct."
# fi

# # Wait for MySQL to be ready

# while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" --silent; do
#     echo "Waiting for database connection at $DB_HOST:$DB_PORT ..."
#     sleep 2
# done
# echo "Database connection established!"

# # Create common tables migrations
# # php artisan migrate --path=database/migrations/now

# exec php-fpm
