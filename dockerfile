# -----------------------------------------------------
# 1. PHP-FPM base
# -----------------------------------------------------
FROM php:8.4-fpm AS php

RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    zip unzip git \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN chmod -R 775 storage bootstrap/cache
RUN chown -R www-data:www-data /var/www

# -----------------------------------------------------
# 2. NGINX + PHP-FPM combined runtime
# -----------------------------------------------------
FROM nginx:alpine AS runtime

# Copy Nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy app + php-fpm
COPY --from=php /var/www /var/www
COPY --from=php /usr/local/sbin/php-fpm /usr/local/sbin/php-fpm

WORKDIR /var/www

EXPOSE 80

# Start PHP-FPM in background then Nginx in foreground
CMD php-fpm -D && nginx -g "daemon off;"
