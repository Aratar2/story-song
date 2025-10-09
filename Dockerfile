# syntax=docker/dockerfile:1
FROM php:8.2-apache

# Use the Apache document root as the working directory
WORKDIR /var/www/html

# Copy application source code into the container image
COPY . /var/www/html

# Enable the Apache rewrite module (harmless if not used)
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
