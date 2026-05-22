FROM php:8.3-fpm-bookworm

ARG DEBIAN_FRONTEND=noninteractive
ARG WWWUSER=1000
ARG WWWGROUP=1000

WORKDIR /var/www/html

ENV COMPOSER_HOME=/tmp/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        default-mysql-client \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN if ! awk -F: -v gid="${WWWGROUP}" '$3 == gid { found = 1 } END { exit !found }' /etc/group; then groupadd -g "${WWWGROUP}" sail; fi \
    && if ! awk -F: -v uid="${WWWUSER}" '$3 == uid { found = 1 } END { exit !found }' /etc/passwd; then useradd -ms /bin/bash --gid "${WWWGROUP}" --uid "${WWWUSER}" sail; fi \
    && mkdir -p /tmp/composer \
    && chmod -R 777 /tmp/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-erp.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY docker/php/entrypoint.sh /usr/local/bin/erp-entrypoint

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts

COPY . .

RUN chmod +x /usr/local/bin/erp-entrypoint \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R "${WWWUSER}:${WWWGROUP}" storage bootstrap/cache vendor \
    && chmod -R ug+rwX storage bootstrap/cache vendor

USER ${WWWUSER}:${WWWGROUP}

ENTRYPOINT ["erp-entrypoint"]
CMD ["php-fpm"]
