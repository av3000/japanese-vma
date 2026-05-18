#!/bin/sh

set -eu

mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/fonts \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Preparing Laravel test-runner environment."

if [ ! -f /var/www/html/vendor/bin/phpunit ]; then
    echo "PHPUnit is missing from the mounted vendor volume. Installing Composer dependencies for the test runner..."
    composer install --no-interaction --optimize-autoloader
fi

echo "Test database connection established!"

exec tail -f /dev/null
