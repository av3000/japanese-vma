#!/bin/bash

# Check if permissions are already correct before adjusting
if [ "$(stat -c '%U' storage)" != "www-data" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    echo "Permissions fixed."
else
    echo "Permissions already correct."
fi

# Wait for MySQL to be ready

while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" --silent; do
    echo "Waiting for database connection at $DB_HOST:$DB_PORT ..."
    sleep 2
done
echo "Database connection established!"

# Create common tables migrations
# php artisan migrate --path=database/migrations/now

exec php-fpm
