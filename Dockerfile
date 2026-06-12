# syntax=docker/dockerfile:1
FROM dunglas/frankenphp:1-php8.4 AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    intl \
    opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /app/app/cache /app/logs 2>/dev/null || true

FROM dunglas/frankenphp:1-php8.4 AS dev

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    intl \
    opcache \
    xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN echo 'xdebug.mode=debug' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.start_with_request=yes' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo 'xdebug.client_host=host.docker.internal' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /app
COPY . .

RUN composer install --no-interaction

ENV SDF_ENV=development

EXPOSE 80
CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]

FROM base AS production

ENV SDF_ENV=production
ENV APP_ENV=prod

COPY app/php.ini /usr/local/etc/php/conf.d/app.ini

EXPOSE 80 443
CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
