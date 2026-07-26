# NEO-LABS — Laravel on Render (nginx + php-fpm)
FROM php:8.2-fpm-alpine

# System libs + PHP extensions the app needs (Postgres, mbstring, zip, gd, ...)
RUN apk add --no-cache \
        nginx git unzip \
        postgresql-dev oniguruma-dev libzip-dev libpng-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring bcmath zip gd exif

# Composer (deps are installed at build time; vendor/ is not committed)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# App-level PHP settings (uploads, memory, timeout for AI requests)
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 10000
CMD ["/usr/local/bin/start.sh"]
