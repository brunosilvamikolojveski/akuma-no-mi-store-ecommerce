FROM dunglas/frankenphp:1-php8.4

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_mysql zip bcmath pdo_sqlite

WORKDIR /app
COPY . .
COPY docker/Caddyfile /etc/caddy/Caddyfile

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && composer check-platform-reqs --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000
CMD ["sh", "-c", "php artisan storage:link --force && exec frankenphp run --config /etc/caddy/Caddyfile"]
