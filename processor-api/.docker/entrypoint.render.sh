#!/usr/bin/env sh
set -e

cd /var/www/html

mkdir -p storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

php artisan config:clear
php artisan cache:clear
php artisan view:clear

php artisan serve --host=0.0.0.0 --port="${PORT}"
