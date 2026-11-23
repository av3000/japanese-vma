FROM php:8.3-fpm

WORKDIR /var/www/html

ARG INSTALL_DEV_DEPENDENCIES=false

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
    fonts-noto-cjk \
    fonts-ipafont-gothic \
    fonts-hanazono \
    postgresql-client \
    nodejs \
    npm \
    libicu-dev \
    libpq-dev \
    # ADDED: autoconf for PECL extensions
    autoconf

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/zz-memory-limit.ini

# `app/`, `config/` and `routes/` are bind-mounted from the host for live code reload (see
# docker-compose.yml), unlike `vendor/`, which has its own container-local volume. On Windows,
# Docker Desktop's WSL2 file sharing makes stat() on bind-mounted files expensive, and opcache's
# default timestamp validation stat()s nearly every included file on every request - this alone
# was costing ~2.5-3s per API request. Disabling it trades automatic hot-reload for a ~3-4x
# request speedup: after editing PHP code, run `docker compose restart laravel-app` (~9s) to pick
# up the change. CLI (artisan, queue workers, Horizon) is unaffected - opcache.enable_cli is off.
RUN echo "opcache.validate_timestamps=0" > /usr/local/etc/php/conf.d/zz-opcache-dev.ini

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip intl

# Install Redis PHP extension via PECL
RUN pecl channel-update pecl.php.net \
    && pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

# Ensure storage and bootstrap/cache directories exist and set correct permissions
RUN mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/fonts \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy the application code LAST to avoid invalidating cache
COPY . /var/www/html

# TODO: doublecheck if this fits for deployments to production when working on them.
RUN if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ]; then \
        composer install --no-interaction --optimize-autoloader; \
    else \
        composer install --no-interaction --optimize-autoloader --no-dev; \
    fi

EXPOSE 9000
CMD ["php-fpm"]
