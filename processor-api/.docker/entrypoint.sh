#!/bin/bash

# Ensure Laravel writable directories exist without recursively walking the
# Windows bind-mounted storage tree on every container start.
WRITABLE_DIRS="
/var/www/html/storage/app/public
/var/www/html/storage/framework/cache/data
/var/www/html/storage/framework/sessions
/var/www/html/storage/framework/testing
/var/www/html/storage/framework/views
/var/www/html/storage/fonts
/var/www/html/storage/logs
/var/www/html/bootstrap/cache
"

for dir in $WRITABLE_DIRS; do
    mkdir -p "$dir"
    chown www-data:www-data "$dir" 2>/dev/null || true
    chmod 777 "$dir" 2>/dev/null || true
done

echo "Storage and cache directories are writable."

# Wait for PostgreSQL to be ready
while ! pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE"; do
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

# # Wait for PostgreSQL to be ready

# while ! pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE"; do
#     echo "Waiting for database connection at $DB_HOST:$DB_PORT ..."
#     sleep 2
# done
# echo "Database connection established!"

# # Create common tables migrations
# # php artisan migrate --path=database/migrations/now

# exec php-fpm
