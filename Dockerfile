# -------------------------------------------------------------------
# Stage 1: Build PHP Dependencies
# -------------------------------------------------------------------
FROM composer:2.7 AS vendor
WORKDIR /app

# Copy composer files first to leverage caching
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-scripts

# Copy the rest of the application to run scripts
COPY . .
RUN composer run-script post-autoload-dump

# -------------------------------------------------------------------
# Stage 2: Build Frontend Assets
# -------------------------------------------------------------------
FROM node:20-alpine AS frontend
WORKDIR /app

# Copy package.json and lock file
COPY package.json package-lock.json ./
RUN npm ci

# Copy the rest of the application
COPY . .
RUN npm run build

# -------------------------------------------------------------------
# Stage 3: Final Production Image
# -------------------------------------------------------------------
FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    oniguruma-dev \
    icu-dev \
    supervisor \
    linux-headers \
    pcre-dev \
    ${PHPIZE_DEPS} \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev ${PHPIZE_DEPS} \
    && rm -rf /var/cache/apk/* /tmp/*

WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built vendor directory from 'vendor' stage
COPY --from=vendor /app/vendor/ ./vendor/

# Copy built frontend assets from 'frontend' stage
COPY --from=frontend /app/public/build/ ./public/build/

# Setup permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
