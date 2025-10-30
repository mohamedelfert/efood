# syntax = docker/dockerfile:experimental

# Default to PHP 8.1 (match your local version; change if using 8.2+)
ARG PHP_VERSION=8.1
ARG NODE_VERSION=18

# Use the official Fly Laravel base image (includes Ubuntu + Nginx + PHP-FPM pre-configured)
FROM fideloper/fly-laravel:${PHP_VERSION} as base

# Repeat ARG for Docker scoping
ARG PHP_VERSION

LABEL fly_launch_runtime="laravel"

# Copy source code (respects .dockerignore)
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Install PHP dependencies via Composer (no-dev for production)
RUN composer install --no-dev --optimize-autoloader

# Multi-stage build for assets (if using npm/yarn for JS/CSS)
FROM node:${NODE_VERSION} as asset-builder

WORKDIR /var/www/html
COPY . .
COPY --from=base /var/www/html/vendor /var/www/html/vendor  # Reuse Composer deps

# Build assets (Vite or Mix)
RUN if [ -f "vite.config.js" ]; then \
      ASSET_CMD="build"; \
    else \
      ASSET_CMD="production"; \
    fi && \
    if [ -f "yarn.lock" ]; then \
      yarn install --frozen-lockfile && yarn $ASSET_CMD; \
    elif [ -f "pnpm-lock.yaml" ]; then \
      corepack enable && corepack prepare pnpm@latest-7 --activate && pnpm install --frozen-lockfile && pnpm run $ASSET_CMD; \
    elif [ -f "package-lock.json" ]; then \
      npm ci --no-audit && npm run $ASSET_CMD; \
    else \
      npm install && npm run $ASSET_CMD; \
    fi

# Final image: Copy assets back
FROM base
COPY --from=asset-builder /var/www/html/public/build public/build  # If using Vite/Mix

# Clear and cache Laravel config (run these after .env is set)
RUN php artisan optimize:clear \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data /var/www/html \
    && sed -i 's/protected $proxies/protected $proxies = ""/g' app/Http/Middleware/TrustProxies.php \
    && echo "MAILTO=\"\"\n * * * * www-data /usr/bin/php /var/www/html/artisan schedule:run" > /etc/cron.d/laravel

# Copy entrypoint for runtime (handles migrations, etc.)
COPY .fly/entrypoint.sh /entrypoint
RUN chmod +x /entrypoint

# Expose port (Fly.io internal port)
EXPOSE 8080

ENTRYPOINT ["/entrypoint"]