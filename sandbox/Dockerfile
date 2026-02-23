FROM php:8.2-cli-alpine

RUN apk add --no-cache git unzip curl libstdc++ \
    && docker-php-ext-install bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /opt/www

COPY src/           ./hyperf-avro/src/
COPY composer.json  ./hyperf-avro/composer.json

COPY sandbox/ .

RUN composer install --no-interaction --no-progress
