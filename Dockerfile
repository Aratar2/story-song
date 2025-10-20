# syntax=docker/dockerfile:1
FROM php:8.3-fpm

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
