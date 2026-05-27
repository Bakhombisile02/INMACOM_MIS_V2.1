# Stage 1: Build the React / Vite frontend assets
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Build the PHP application container with FrankenPHP
FROM dunglas/frankenphp:latest-php8.3

# Install system dependencies and PHP extensions securely
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    intl \
    gd \
    opcache

# Recommended PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /app
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application source code
COPY . .

# Copy built React assets from Stage 1
COPY --from=frontend-builder /app/public/build ./public/build

# Install production PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set correct storage and cache directory permissions for FrankenPHP's webserver user
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Make docker-entrypoint.sh executable
RUN chmod +x docker-entrypoint.sh

# Port configuration (Cloud Run sets PORT env var automatically)
ENV PORT=8080
EXPOSE 8080

# Set the document root to Laravel's public directory
ENV FRANKENPHP_DOCUMENT_ROOT=/app/public

ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["frankenphp", "php-server", "--listen", ":8080", "--root", "./public"]

