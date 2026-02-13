FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libssl-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www