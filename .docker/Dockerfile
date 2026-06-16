FROM php:8.5-fpm-trixie

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HTACCESS_PROTECT=0
ENV COMPOSER_HOME=/var/www/html/

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# Install and run composer
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        zip; \
    cd /tmp; \
    docker-php-ext-install gd zip; \
    curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer

# Copy application files
COPY --chown=www-data:www-data ../ /var/www/html/

# Composer install
RUN cd /var/www/html && composer install

# PHP settings
COPY --chown=www-data:www-data .docker/php/php.ini /usr/local/etc/php/php.ini

# PHP pool configuration
COPY --chown=www-data:www-data .docker/php/pool.conf /usr/local/etc/php-fpm.d/zz-docker.conf
