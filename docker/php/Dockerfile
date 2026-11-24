# syntax=docker/dockerfile:1.7

# --------------------------
# Base stage - shared deps (PHP 8.5 - absolute latest stable)
# --------------------------
FROM php:8.5-fpm-alpine AS base

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    mongo-c-driver-dev \
    linux-headers \
    autoconf \
    g++ \
    make \
    openssl-dev \
    php85-gd \
    php85-pdo_mysql \
    php85-mbstring \
    php85-exif \
    php85-pcntl \
    php85-bcmath \
    php85-zip \
    php85-intl \
    php85-sockets \
    php85-posix \
    libmemcached-dev

# Install memcached via pecl
RUN pecl install memcached \
    && docker-php-ext-enable memcached

# Extensions installed via apk packages above (opcache included in php85-common)
RUN cp /usr/lib/php85/modules/pcntl.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/ \
    && echo "extension=pcntl" > /usr/local/etc/php/conf.d/pcntl.ini

RUN pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

RUN addgroup -g ${GID} --system app \
    && adduser -u ${UID} --system -G app app

WORKDIR /var/www/html

# OPcache config - optimized for PHP 8.5 JIT
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit_buffer_size=64M" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP 8.5+ optimizations
RUN echo "zend.exception_ignore_args=On" > /usr/local/etc/php/conf.d/optimization.ini \
    && echo "zend.exception_string_param_max_len=0" >> /usr/local/etc/php/conf.d/optimization.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/optimization.ini

# --------------------------
# Builder stage - install deps
# --------------------------
FROM base AS builder

USER root

COPY --chown=app:app composer.json ./
COPY --chown=app:app composer.lock* ./

RUN --mount=type=cache,target=/tmp/composer-cache,id=composer,uid=1000,gid=1000 \
    composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

# --------------------------
# Dev stage
# --------------------------
FROM base AS dev

USER root

RUN apk add --no-cache \
    postgresql-dev \
    && docker-php-ext-install pdo_pgsql

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=develop,debug,coverage" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.log_level=0" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.discover_client_host=1" >> /usr/local/etc/php/conf.d/xdebug.ini

# Copy vendor from builder
COPY --from=builder /var/www/html/vendor ./vendor

# Copy application code for dev
COPY --chown=app:app . .

RUN mkdir -p \
    storage/logs \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R app:app storage bootstrap/cache

USER app

EXPOSE 9000

CMD ["php-fpm"]

# --------------------------
# Production stage
# --------------------------
FROM base AS prod

USER root

COPY --from=builder /var/www/html/vendor ./vendor
COPY --chown=app:app . .

RUN mkdir -p \
    storage/logs \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R app:app storage bootstrap/cache

USER app

HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD php -r "exit(0);" || exit 1

EXPOSE 9000

CMD ["php-fpm"]