FROM php:8.3-cli

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    unzip \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install zip mbstring bcmath xml pdo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy project files
COPY . .

# Create necessary storage directories
RUN mkdir -p storage/framework/sessions \
             storage/framework/views \
             storage/framework/cache \
             storage/logs \
             bootstrap/cache

# Install production dependencies without running artisan scripts during build
# (Artisan scripts run at runtime when environment variables are injected by Render)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Set permissions
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Discover packages and run Laravel server on $PORT
CMD php artisan package:discover --ansi && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
