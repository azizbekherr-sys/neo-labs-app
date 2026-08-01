#!/bin/sh
set -e

# Render provides $PORT (defaults to 10000). Bind nginx to it.
: "${PORT:=10000}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/http.d/default.conf

cd /var/www/html

# Env vars come from Render at runtime, so config cache is built at startup.
# Package discovery and compiled views are already prepared in the image.
php artisan config:cache || true
php artisan migrate --force
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# php-fpm in background, nginx in foreground
php-fpm -D
exec nginx -g 'daemon off;'
