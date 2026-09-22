# Production Dockerfile for Laravel with PHP 8.3, Composer, and LibreOffice Headless (Optimized for Render)
FROM php:8.3-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system packages & LibreOffice for PDF conversion with fonts
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
    libpq-dev \
    sqlite-dev \
    libreoffice \
    font-noto \
    ttf-dejavu \
    ttf-freefont

# Install PHP extensions for Laravel (supports MySQL, PostgreSQL, and SQLite)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
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

# Copy application source code
COPY . /var/www/html

# Copy entrypoint script and grant execution rights
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Install production dependencies without dev packages
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Setup directory permissions for Laravel web server
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Environment variables for LibreOffice on Linux container
ENV SOFFICE_BINARY=/usr/bin/soffice

# Render default port exposure
EXPOSE 8000 10000

# Execute entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
