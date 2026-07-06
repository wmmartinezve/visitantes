# syntax=docker/dockerfile:1

FROM php:8.2-cli-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libpq-dev \
    libonig-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        mbstring \
        bcmath \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM node:20-bookworm-slim AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build

FROM base AS runner

COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY . .

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/{cache,sessions,views} storage/app/public bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/railway-entrypoint.sh /usr/local/bin/railway-entrypoint
RUN chmod +x /usr/local/bin/railway-entrypoint

ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["railway-entrypoint"]
