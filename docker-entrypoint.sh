#!/bin/sh
set -e

# Ensure required storage directories exist with proper permissions
mkdir -p /var/www/html/storage/app/output
mkdir -p /var/www/html/storage/app/soffice_home
mkdir -p /var/www/html/storage/app/soffice_profiles
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/database

# Ensure SQLite file exists if using SQLite
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod 664 /var/www/html/database/database.sqlite

# Clear previous configuration cache in case env changed
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations automatically on startup
echo "Running database migrations..."
php artisan migrate --force || true

# Prepare KAK template and field mappings
echo "Preparing KAK template..."
php artisan kak:prepare-template || true

# Render assigns dynamic PORT environment variable (default: 10000 or 8000)
PORT=${PORT:-8000}
echo "Starting Laravel application on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
