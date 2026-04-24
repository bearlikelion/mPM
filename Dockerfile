FROM php:8.4-fpm-alpine

WORKDIR /app

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    postgresql-client \
    libpq-dev \
    nodejs \
    npm \
    oniguruma-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    supervisor

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    pcntl \
    gd \
    zip \
    intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --no-interaction --no-scripts
RUN npm ci && npm run build
RUN composer dump-autoload --optimize
RUN php artisan package:discover --ansi

# Create directory for persistent volumes (CapRover)
RUN mkdir -p /config

# Snapshot the default storage structure so the entrypoint can seed a fresh persistent volume
RUN cp -r /app/storage /app/storage-default

# Setup Supervisor
RUN mkdir -p /var/log/supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY --chown=www-data:www-data docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
EXPOSE 8080

# Environment variables for Supervisor
ENV QUEUE_NAME=default
ENV QUEUE_WORKERS=4

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
