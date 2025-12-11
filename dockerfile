# -------------------------
# 1. Base PHP Image
# -------------------------
FROM php:8.2-fpm

# -------------------------
# 2. System Dependencies
# -------------------------
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath

# -------------------------
# 3. Install Composer
# -------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -------------------------
# 4. Set Working Directory
# -------------------------
WORKDIR /var/www

# -------------------------
# 5. Copy Application Files
# -------------------------
COPY . .

# -------------------------
# 6. Install PHP Dependencies
# -------------------------
RUN composer install --no-dev --optimize-autoloader

# -------------------------
# 7. Permissions (important for storage & cache)
# -------------------------
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# -------------------------
# 8. Expose FPM Port
# -------------------------
EXPOSE 9000

CMD ["php-fpm"]
