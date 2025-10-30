#!/bin/bash
set -e

echo "Starting Laravel application..."

# Wait for database to be ready (if using MySQL)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database..."
    until php artisan db:show 2>/dev/null || sleep 2; do
        echo "Database not ready, waiting..."
    done
fi

# Run migrations (optional - be careful in production)
# Uncomment if you want automatic migrations
# php artisan migrate --force --no-interaction

# Clear and cache configuration
echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage is writable
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "Starting Apache..."
exec apache2-foreground