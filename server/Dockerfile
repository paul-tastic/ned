FROM php:8.4-fpm-alpine AS base

# Install system deps + PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    sqlite \
    sqlite-dev \
    curl \
    npm \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_sqlite pdo_mysql pcntl gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- Build stage: install deps + compile assets ---
FROM base AS build

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci && npm run build \
    && rm -rf node_modules

# --- Production stage ---
FROM base AS production

# Copy built app
COPY --from=build /var/www/html /var/www/html

# Create SQLite database directory + storage dirs
RUN mkdir -p database storage/logs storage/framework/{cache,sessions,views} \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html

# Nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config (PHP-FPM + nginx + queue worker + scheduler)
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
