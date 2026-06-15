#!/bin/sh
set -e

cd /var/www/html

echo "[entrypoint] Running database migrations..."
php artisan migrate --force

if [ "$APP_ENV" = "production" ]; then
    echo "[entrypoint] Caching config, routes, and views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "[entrypoint] Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
