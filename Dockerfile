# Stage 1: Install PHP dependencies
FROM composer:2.7 as vendor
WORKDIR /app
COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist

# Stage 2: Install Node dependencies and build assets
FROM node:18 as node_assets
WORKDIR /app
COPY package.json package.json
COPY package-lock.json package-lock.json
RUN npm install
COPY . .
RUN npm run build

# Stage 3: Final application image using official PHP Apache image
FROM php:8.2-apache

# Install system dependencies and PHP extensions required by Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
&& docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip

# Configure Apache for Laravel
RUN a2enmod rewrite
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Set the working directory
WORKDIR /var/www/html

# Copy application files
COPY --from=vendor /app/vendor/ /var/www/html/vendor/
COPY --from=node_assets /app/public/ /var/www/html/public/
COPY . /var/www/html

# Set correct permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Run migrations and then start the Apache server
CMD /bin/bash -c "php artisan migrate --force && apache2-foreground"
