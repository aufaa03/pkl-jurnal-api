# Stage 1: Install PHP dependencies
FROM composer:2 as vendor
WORKDIR /app
COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --ignore-platform-reqs --no-interaction --no-plugins --no-scripts --prefer-dist

# Stage 2: Install Node dependencies and build assets
FROM node:18 as node_assets
WORKDIR /app
COPY package.json package.json
COPY package-lock.json package-lock.json
RUN npm install
COPY . .
RUN npm run build

# Stage 3: Final application image
FROM heroku/php-apache2:2.4
WORKDIR /app
COPY --from=vendor /app/vendor/ /app/vendor/
COPY --from=node_assets /app/public/ /app/public/
COPY . .

# Set the start command
CMD ["/bin/bash", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=$PORT"]
