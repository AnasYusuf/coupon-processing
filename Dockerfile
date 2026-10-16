FROM php:8.2-fpm

# System packages and PHP extensions Laravel + Redis + MySQL need.
# pcntl is required by Laravel Horizon (posix ships with the base image).
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
