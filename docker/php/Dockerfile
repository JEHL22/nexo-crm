FROM php:8.4-fpm-alpine

ENV TZ=America/Lima

# Librerías de runtime: estas sí quedan en la imagen final
RUN apk add --no-cache \
        bash curl git unzip tzdata \
        freetype libjpeg-turbo libpng icu-libs libzip oniguruma \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

# Extensiones PHP: los paquetes -dev y el toolchain de compilación viven solo
# durante este RUN (grupo virtual .build-deps) y se eliminan al final
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS linux-headers \
        freetype-dev libjpeg-turbo-dev libpng-dev \
        icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring intl zip opcache gd bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html
