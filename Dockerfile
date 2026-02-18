FROM php:8.3-apache

# Install system dependencies and Node.js optional tools
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    npm

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql zip gd mbstring xml dom

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
