FROM php:8.2-fpm

RUN set -ex \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libfreetype6-dev \
        imagemagick \
        libmagickwand-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure mysqli --with-mysqli=mysqlnd \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-install -j"$(nproc)" gd intl zip mysqli pdo_mysql \
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis \
    && rm -rf /var/lib/apt/lists/*

COPY php.ini /usr/local/etc/php/conf.d/sphynx.ini

WORKDIR /var/www/html
