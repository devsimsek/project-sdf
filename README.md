# Project SDF v2.3.0

Project SDF is a fast, robust, and modern project development framework designed
for PHP enthusiasts. It is compact, easy to maintain, and extremely extendable.

![version v2.3.0](https://img.shields.io/badge/version-v2.3.0-blue)
[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://devsimsek.mit-license.org)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777bb3)
![428 tests](https://img.shields.io/badge/tests-428%20%E2%9C%93-success)

## Features & Highlights

- **MVC Pattern:** Clean separation of concerns.
- **Spark ORM:** Built-in QueryBuilder, Active Record, relationships (hasOne,
  hasMany, belongsTo, belongsToMany), pagination, timestamps, and soft deletes.
- **Middleware Pipeline:** PSR-15 inspired request filtering with CSRF, CORS,
  and Rate Limiting middleware.
- **Authentication:** SessionGuard and JwtGuard (HS256) with refresh token
  support and UserProvider.
- **PSR-7 HTTP Messages:** Immutable `Request`, `Response`, `Uri`, `Stream`,
  `UploadedFile` implementations with PSR-4 autoloading.
- **PSR-16 Cache:** File, Redis, and Memcached drivers with tag support via a
  static facade (`SDF\Cache\Cache`).
- **PSR-3 Logger:** LoggerAdapter bridging monolog/mixed loggers to structured
  `SDF\Log\` interface.
- **PSR-14 Events:** EventDispatcher with prioritized, stoppable listeners.
- **Schema Builder:** MySQL/SQLite Blueprint with foreign keys, timestamps,
  soft deletes.
- **Localization:** Dot-notation translator with pluralization via `SDF\Localization\Translator`.
- **Mail:** SMTP and Log mailers with Mailable fluent API (`SDF\Mail\`).
- **Queues:** Database and Redis queue drivers with Worker (`SDF\Queue\`).
- **Storage:** Local and S3 filesystem abstraction with `disk()` switching
  (`SDF\Storage\`).
- **Encryption:** AES-256-CBC with HMAC-SHA256 encrypt-then-MAC (`SDF\Encryption\Encrypter`).
- **Validation:** 19 built-in rules with custom rule support (`SDF\Validation\Validator`).
- **Fast Routing:** Route compilation and caching for O(1) static path matching.
- **Config Loader:** Supports `.php` and `.json` configs. Compiled to cache
  automatically.
- **Modern Fuse View Engine:** Compiles to raw PHP with Blade-style
  `@extends`/`@section`/`@yield`/`@include` inheritance.
- **CLI Commands:** Generators for Models, Controllers, Migrations, Tests,
  Middleware, and more (`sdf/cli`).
- **Swagger/OpenAPI:** Automatic OpenAPI 3.0 spec generation from controller
  annotations.
- **Composer Ready:** PSR-4 autoloading via Composer with APCu optimization.

## Tech Stack

**PHP:** 8.2 or higher is required. The framework is tested up to PHP 8.5.

**Performance:** ~10,000 req/s on FrankenPHP 1.11 (Mac mini M1, full-stack
with Fuse view rendering). See `wiki/app/tutorials/docker-frankenphp.md`.

## Installation

### Via Git

```bash
git clone https://github.com/devsimsek/project-sdf.git
cd project-sdf
composer install
```

### Via Composer

```bash
composer create-project devsimsek/project-sdf
```

## Quick Start

### 1. Configuration

Copy `.env.example` to `.env` and configure your database:

```bash
cp .env.example .env
```

Configure the framework inside `app/config/`.

### 2. Run Migrations

```bash
php sdf/cli db migrate
```

### 3. Generate Code

Use the bundled CLI to generate components:

```bash
php sdf/cli g controller UserController
php sdf/cli g model User
php sdf/cli g test UserController --type=controller --namespace=App\\Controllers\\User
```

### 4. Run Development Server

```bash
php sdf/cli serve -p 8000
# Or with live-reload:
php sdf/cli serve -p 8000 --live
```

### 5. Run Tests

```bash
vendor/bin/phpunit --testdox
```

## Documentation

Full documentation is available in the `wiki/` directory.

- [Core Components](wiki/sdf/home.md)
- [Spark ORM](wiki/libraries/spark.md)
- [Fuse Template Engine](wiki/libraries/fuse.md)
- [Authentication](wiki/libraries/auth.md)
- [Caching](wiki/libraries/caching.md)
- [Validation](wiki/libraries/validation.md)
- [Storage / Filesystem](wiki/libraries/storage.md)
- [Schema Builder & Migrations](wiki/libraries/schema.md)
- [Events (PSR-14)](wiki/libraries/events.md)
- [Localization](wiki/libraries/localization.md)
- [Mail](wiki/libraries/mail.md)
- [Queues](wiki/libraries/queue.md)
- [Timestamps & Soft Deletes](wiki/libraries/timestamps.md)
- [Logging (PSR-3)](wiki/libraries/logging.md)
- [Docker & FrankenPHP](wiki/app/tutorials/docker-frankenphp.md)

## Docker

Deploy with Docker Compose (FrankenPHP + MySQL 8.4 + Redis 7):

```bash
docker compose up -d
```

See `wiki/app/tutorials/docker-frankenphp.md` for detailed setup,
worker mode, and performance tuning.

## Tests

```bash
vendor/bin/phpunit --testdox    # 428 tests, 763 assertions
composer analyze                # PHPStan level 5
composer cs:check               # PHP-CS-Fixer PSR-12
```

## Feedback

If you have any feedback or encounter issues, please open an issue within the
repository.

## Contributing

Contributions are always welcome! Please follow PSR-12 coding standards and
write tests for your components before opening a pull request.

## Authors

- [@devsimsek](https://www.github.com/devsimsek)
