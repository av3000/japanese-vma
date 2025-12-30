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
    # TODO: Test one by one and see which library causes issues (KEEP COMMENTED FOR NOW)
    # libxrender1 \
    # libfontconfig1 \
    # libxtst6 \
    # xvfb \
    # wkhtmltopdf \
    # fonts-ipafont-gothic \
    nodejs \
    npm \
    libicu-dev \
    # ADDED: autoconf for PECL extensions
    autoconf

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install Redis PHP extension via PECL
# ADDED: Update PECL channel and use force option
RUN pecl channel-update pecl.php.net \
    && pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

# Ensure storage and bootstrap/cache directories exist and set correct permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy the application code LAST to avoid invalidating cache
COPY . /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
