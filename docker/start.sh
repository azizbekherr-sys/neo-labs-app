#!/bin/sh
set -e

# Render provides $PORT (defaults to 10000). Bind nginx to it.
: "${PORT:=10000}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/http.d/default.conf

cd /var/www/html

# Env vars come from Render at runtime — (re)build the discover manifest + caches.
php artisan package:discover --ansi || true
php artisan config:cache || true
php artisan view:cache || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# php-fpm in background, nginx in foreground
php-fpm -D
exec nginx -g 'daemon off;'
