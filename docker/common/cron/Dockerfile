# syntax=docker/dockerfile:1
FROM php:8.3-cli

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        cron \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

COPY docker/common/cron/entrypoint.sh /usr/local/bin/cron-entrypoint
RUN chmod +x /usr/local/bin/cron-entrypoint

ENTRYPOINT ["/usr/local/bin/cron-entrypoint"]
CMD ["cron", "-f"]
