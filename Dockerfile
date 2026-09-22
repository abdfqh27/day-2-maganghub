# Multi-stage Dockerfile for Laravel with PHP 8.3, Composer, and LibreOffice Headless (Ready for Render / Cloud)
FROM php:8.3-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies including LibreOffice for PDF conversion
RUN apk update && apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    nginx \
    supervisor \
    libreoffice \
    font-noto \
    ttf-freefont

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set environment variable for LibreOffice binary
ENV SOFFICE_BINARY=/usr/bin/soffice

# Expose port
EXPOSE 80 8000

# Start command (runs migrations, prepares template, and serves)
CMD php artisan kak:prepare-template && php artisan serve --host=0.0.0.0 --port=8000
