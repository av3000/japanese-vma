FROM php:8.3-fpm

WORKDIR /var/www/html

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
  wget \
  default-mysql-client \
  libxrender1 \
  libfontconfig1 \
  libxtst6 \
  xvfb \
  wkhtmltopdf \
  nodejs \
  npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Ensure storage and bootstrap/cache directories exist and set correct permissions
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
  && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
