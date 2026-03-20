FROM php:8.3-fpm

# Install nginx and system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    zip \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql zip gd mbstring xml dom

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy source files and install dependencies
COPY src/ .
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Apply nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default

EXPOSE 80

# Start php-fpm in background, then nginx in foreground
CMD sh -c "php-fpm & nginx -g 'daemon off;'"
