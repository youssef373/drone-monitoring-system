#!/bin/sh
set -e

cd /var/www/html

echo "[entrypoint] Fixing storage permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "[entrypoint] Running database migrations..."
php artisan migrate --force

echo "[entrypoint] Caching config so php-fpm reads APP_KEY from cached config..."
php artisan config:clear
php artisan config:cache

echo "[entrypoint] Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
