FROM php:8.3-fpm

# Install nginx and system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    gettext-base \
    default-mysql-client \
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

# Install nginx config template and startup script
COPY docker/nginx.conf /etc/nginx/templates/app.conf.template
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh \
    && rm -f /etc/nginx/sites-enabled/default

EXPOSE 80

CMD ["/start.sh"]
