# Deploying SDF with Docker & FrankenPHP

[FrankenPHP](https://frankenphp.dev/) is a modern PHP application server built on top of Caddy. It provides automatic HTTPS, HTTP/2, HTTP/3, worker mode, and a production-ready PHP runtime - no separate web server or PHP-FPM needed.

The SDF CLI (`sdf/cli serve`) auto-detects FrankenPHP — if the `frankenphp` binary is found, it runs `frankenphp php-server`, otherwise falls back to the PHP built-in server.

## Project Structure

```
project-sdf/
├── app/                  # Application code (config, controllers, views, ...)
├── sdf/                  # Framework core
├── index.php             # Application entrypoint
├── Dockerfile
├── compose.yaml
└── .dockerignore
```

## Dockerfile

Create a `Dockerfile` in the project root:

```dockerfile
FROM dunglas/frankenphp:1-php8.4 AS base

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    pdo_sqlsrv \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
WORKDIR /app
COPY . .

# Install dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Hardened permissions
RUN chown -R www-data:www-data /app/app/cache /app/logs 2>/dev/null || true

# ─── Production stage ─────────────────────────────────────────────────────────
FROM base AS production

ENV SDF_ENV=production
ENV APP_ENV=prod

EXPOSE 80 443

# FrankenPHP automatically serves index.php from /app/public
# SDF uses index.php in the root - configure Caddy to match
CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
```

## Caddyfile / FrankenPHP config

Create a `Caddyfile` in the project root:

```caddyfile
{
    # Global options
    frankenphp
}

localhost:80 {
    root * /app
    php {
        resolve_root_symlink
    }

    # Serve static files directly
    @static {
        file {
            try_files {path} {path}/index.php
        }
    }
    rewrite @static {path}

    # Default SDF entrypoint
    try_files {path} /index.php

    # Security headers
    header {
        X-Content-Type-Options "nosniff"
        X-Frame-Options "DENY"
        X-XSS-Protection "1; mode=block"
        Referrer-Policy "strict-origin-when-cross-origin"
        -Server
    }

    # Hide sensitive paths
    respond /app/config/* 403
    respond /app/cache/* 403
    respond /sdf/* 403
    respond /vendor/* 403
    respond /composer.json 403
    respond /composer.lock 403
}

# Optional: HTTPS with automatic certificates (production)
# example.com {
#     root * /app
#     php { resolve_root_symlink }
#     try_files {path} /index.php
# }
```

## Compose file

Create a `compose.yaml` for local development or production:

```yaml
services:
  app:
    build:
      context: .
      target: production
    ports:
      - "80:80"
      - "443:443"
    environment:
      - SDF_ENV=production
    volumes:
      # Mount config and uploads for persistent data
      - ./app/config:/app/app/config:ro
      - ./uploads:/app/uploads
    restart: unless-stopped
```

## .dockerignore

```
.git/
.gitignore
tests/
wiki/
*.md
.phpunit*
.php-cs-fixer*
phpstan*
.DS_Store
docker-compose*
compose.override.yaml
```

## Environment & Config

Configure `app/config/app.php` for production:

```php
<?php
$config["rc_magic_routing"] = false;       // disable magic routing in production
$config["app_version"] = "v1.0";
$config["app_title"] = "My SDF Application";
```

Set the cache driver to Redis or file in `app/config/cache.php`:

```php
<?php
$config['cache'] = [
    'driver' => 'file', // or 'redis'
    'file' => [
        'path' => '/tmp/sdf_cache/',
        'prefix' => 'sdf_cache_',
    ],
];
```

## Building & Running

```bash
# Build the image
docker build -t my-sdf-app .

# Run with Compose
docker compose up -d

# Run directly
docker run -d --name my-sdf-app -p 80:80 -p 443:443 my-sdf-app
```

## Worker Mode (FrankenPHP)

FrankenPHP can run PHP in worker mode for better performance. Create a `frankenphp-worker.php` entrypoint:

```php
<?php
// frankenphp-worker.php
// FrankenPHP worker script - runs once and handles multiple requests

const SDF = true;
const SDF_ENV = 'production';

require __DIR__ . '/index.php';
```

Then update the `Caddyfile` to enable worker mode:

```caddyfile
localhost:80 {
    root * /app
    php {
        resolve_root_symlink
        worker frankenphp-worker.php 4  # 4 workers
    }
    try_files {path} /index.php
}
```

## Performance Tuning

### OPcache (recommended)

Create `app/php.ini` and mount it, or add to `Caddyfile`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.fast_shutdown=1
```

### FrankenPHP tuning

```caddyfile
{
    frankenphp
    # Tune for your workload
    max_request_body_size 10485760  # 10 MB
    request_timeout 30s
}
```
