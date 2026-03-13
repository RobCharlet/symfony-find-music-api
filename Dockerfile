# syntax=docker/dockerfile:1.7

FROM php:8.4-fpm

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libfcgi-bin \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-scripts

COPY . .
COPY docker/php/conf.d/zz-app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/pool.d/zz-healthcheck.conf /usr/local/etc/php-fpm.d/zz-healthcheck.conf

RUN composer dump-autoload --classmap-authoritative --no-dev --no-interaction \
    && composer run-script post-install-cmd --no-interaction \
    && php bin/console cache:warmup \
    && rm -rf var/log/* \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

USER www-data

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1
