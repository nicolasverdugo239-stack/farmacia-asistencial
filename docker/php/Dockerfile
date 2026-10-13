FROM php:8.3-cli

# Extensiones que necesita el proyecto: MySQL, MongoDB, zip (Composer), intl y bcmath.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libssl-dev libicu-dev default-mysql-client \
    && docker-php-ext-install pdo_mysql zip intl bcmath \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
