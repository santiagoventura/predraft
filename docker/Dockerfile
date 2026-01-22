FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/mlbdraft mlbdraft
RUN mkdir -p /home/mlbdraft/.composer && \
    chown -R mlbdraft:mlbdraft /home/mlbdraft

# Set user
USER mlbdraft

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]

