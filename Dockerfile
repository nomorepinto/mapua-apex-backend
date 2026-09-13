FROM php:8.3-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git curl libzip-dev unzip libonig-dev \
    && docker-php-ext-install zip mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy project files
COPY . .

# Install production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Set storage permissions
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Run the Laravel development server on $PORT (Render sets this automatically)
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
