FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    librabbitmq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-install pdo_pgsql bcmath zip
RUN pecl install amqp redis && docker-php-ext-enable amqp redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY ./src /var/www

# Временно отключаем эти строки:
# RUN composer install --no-interaction --optimize-autoloader --no-dev
# RUN php artisan key:generate

CMD ["php-fpm"]