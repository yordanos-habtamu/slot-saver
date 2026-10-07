# Multi-stage Dockerfile for SlotSaver (Laravel 12 + PHP 8.4 + Node Vite)
FROM php:8.4-cli-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    icu-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    bcmath \
    intl \
    opcache \
    pcntl \
    zip

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Development Stage
FROM base AS dev
RUN apk add --no-cache nodejs npm
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
