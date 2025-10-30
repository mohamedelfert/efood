# Multi-stage build for Laravel (Railway + Fly.io compatible)
FROM php:8.2-apache as base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpq-dev libonig-dev libxml2-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache for Laravel with dynamic port
RUN echo '<VirtualHost *:${PORT}>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes +FollowSymLinks\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Configure Apache to use PORT environment variable
RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for layer caching)
COPY composer.json composer.lock ./

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Complete Composer setup
RUN composer dump-autoload --optimize

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create universal startup script
COPY <<'EOF' /start.sh
#!/bin/bash
set -e

echo "Starting Laravel application..."

# Default to port 8080 if not set (Fly.io default)
# Railway will override this with their PORT variable
export PORT=${PORT:-8080}

echo "Configuring Apache to listen on port ${PORT}..."
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Wait for database if configured
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database connection..."
    max_attempts=30
    attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if php artisan db:show 2>/dev/null; then
            echo "✓ Database connected!"
            break
        fi
        attempt=$((attempt + 1))
        echo "Attempt $attempt/$max_attempts: Database not ready, waiting..."
        sleep 2
    done
    
    if [ $attempt -eq $max_attempts ]; then
        echo "⚠ Warning: Could not connect to database after $max_attempts attempts"
        echo "Continuing anyway..."
    fi
fi

# Run migrations if enabled
if [ "$AUTO_RUN_MIGRATIONS" = "true" ] || [ "$RAILWAY_RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction || echo "⚠ Migrations failed, continuing..."
fi

# Optimize Laravel
echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
echo "Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "✓ Application ready!"
echo "Apache starting on port ${PORT}..."
exec apache2-foreground
EOF

RUN chmod +x /start.sh

# Expose port (Railway and Fly.io will handle mapping)
EXPOSE ${PORT}

CMD ["/start.sh"]