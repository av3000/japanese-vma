FROM php:8.3-fpm

WORKDIR /var/www/html

# Copy Composer early to cache dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    jq \
    wget \
    default-mysql-client \
    libxrender1 \
    libfontconfig1 \
    libxtst6 \
    xvfb \
    wkhtmltopdf \
    fonts-ipafont-gothic \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Ensure storage and bootstrap/cache directories exist and set correct permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy the application code LAST to avoid invalidating cache
COPY . /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
