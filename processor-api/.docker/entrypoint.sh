#!/bin/bash

# Adjust permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Wait for MySQL to be ready
while ! mysqladmin ping -h"$DB_HOST" --silent; do
    echo "Waiting for database connection..."
    sleep 2
done

# Create common tables migrations
php artisan migrate --path=database/migrations/now

exec php-fpm
