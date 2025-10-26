# syntax=docker/dockerfile:1
FROM php:8.3-cli

# 1) Ставим системные пакеты
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      cron git unzip libzip-dev libsqlite3-dev \
 && rm -rf /var/lib/apt/lists/*

# 2) Ставим php-расширения ДЕТЕРМИНИРОВАННО
#    (без параллелизма и внутри исходников PHP)
RUN set -eux; \
    docker-php-source extract; \
    docker-php-ext-configure zip; \
    docker-php-ext-install -j1 zip; \
    docker-php-ext-install -j1 pdo_sqlite; \
    docker-php-source delete

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


ENV COMPOSER_ALLOW_SUPERUSER=1

# 3) Зависимости
COPY composer.json composer.lock* ./

# Разрешаем нужные плагины глобально (без активации плагинов во время самой команды)
RUN composer config --global --no-plugins allow-plugins.symfony/runtime true \
 && composer config --global --no-plugins allow-plugins.symfony/flex true \
 && composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --prefer-dist


RUN composer install --no-dev --optimize-autoloader --no-interaction

# 4) Код и права
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

# 5) Крон и энтрипоинт
COPY docker/common/cron/entrypoint.sh /usr/local/bin/cron-entrypoint
RUN chmod +x /usr/local/bin/cron-entrypoint

ENTRYPOINT ["/usr/local/bin/cron-entrypoint"]
CMD ["cron", "-f"]
