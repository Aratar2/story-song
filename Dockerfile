# syntax=docker/dockerfile:1
FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx supervisor \
    && mkdir -p /run/nginx

WORKDIR /var/www/html

COPY . /var/www/html
RUN rm -rf docker fly Dockerfile docker-compose.yml fly.toml README.md

# Remove default nginx config and replace with Fly.io specific one
RUN rm -f /etc/nginx/http.d/default.conf
COPY fly/nginx.conf /etc/nginx/http.d/default.conf

COPY fly/supervisord.conf /etc/supervisord.conf

RUN set -eux; \
    if grep -Eq '^;?[[:space:]]*clear_env' /usr/local/etc/php-fpm.d/www.conf; then \
        sed -i -E 's|^;?[[:space:]]*clear_env = .*|clear_env = no|' /usr/local/etc/php-fpm.d/www.conf; \
    else \
        echo 'clear_env = no' >> /usr/local/etc/php-fpm.d/www.conf; \
    fi

EXPOSE 8080

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
