FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libzip-dev \
    zip \
    unzip \
    git \
    sqlite-dev \
    linux-headers \
    && docker-php-ext-install pdo pdo_sqlite zip sockets bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 8088

CMD ["php", "-S", "0.0.0.0:8088", "-t", "public"]
