# SDF Framework Documentation

> **v2.0.0** - Fast · Secure · PHP-native

## Navigation

- [Home](home.md)
- **App**
  - [Configuration](app/config.md)
  - [Controllers](app/controllers.md)
  - [Handlers](app/handlers.md)
  - [Helpers](app/helpers.md)
  - [Libraries](app/libraries.md)
  - [Models](app/models.md)
  - [Routes](app/routes.md)
  - [Views](app/views.md)
- **Core**
  - [Core internals](sdf/core.md)
  - [CLI reference](sdf/cli.md)
- **Libraries**
  - [Fuse - View Engine](libraries/fuse.md)
  - [Spark ORM](libraries/spark.md)
  - [Middleware & Guards](libraries/middleware.md)
  - [Request](libraries/request.md)
  - [Response](libraries/response.md)
  - [Cache](libraries/caching.md)
  - [Auth](libraries/auth.md)
  - [Session](libraries/session.md)
   - [Flash Messages](libraries/flash.md)
   - [HTTP (PSR-7)](libraries/http.md)
   - [Swagger / OpenAPI](libraries/swagger.md)
   - [Benchmark](libraries/benchmark.md)

---

## Getting Started

### Prerequisites

- PHP **8.0+** (tested up to PHP 8.5)
- Extensions: `pdo`, `pdo_mysql` or `pdo_sqlite`
- Composer (optional, required for Spark ORM and testing)

### Installation

```bash
git clone https://github.com/devsimsek/project-sdf
cd project-sdf
composer install   # installs Spark ORM, swagger-php, PHPUnit
```

### Project Structure

```
project-sdf/
├── app/
│   ├── config/         # Configuration files (.php or .json)
│   ├── controllers/    # Controller classes
│   ├── models/         # Spark ORM model classes
│   ├── views/          # Fuse templates
│   ├── helpers/        # Global helper functions
│   └── migrations/     # Database migration files
├── sdf/
│   ├── core/           # Framework core (Router, Fuse, Spark, ...)
│   └── cli             # CLI entrypoint
├── tests/              # PHPUnit test suite
└── index.php           # Application entrypoint
```

### Quick Start - Hello World

**1. Create a route** in `app/config/routes.php`:

```php
<?php
$config['/hello/{name}'] = 'Hello/greet';
```

**2. Create the controller** `app/controllers/Hello.php`:

```php
<?php

class Hello extends SDF\Controller
{
    public function greet(string $name): void
    {
        $this->fuse
            ->with(['name' => htmlspecialchars($name)])
            ->render('hello');
    }
}
```

**3. Create the view** `app/views/hello.php`:

```html
<h1>Hello, {{ $name }}!</h1>
```

**4. Run the server:**

```bash
php sdf/cli serve -p 8000
# Visit: http://localhost:8000/hello/World
```

---

## CLI Reference

```
php sdf/cli [command] [subcommand] [name]

Generate:
  g model UserProfile          # Spark ORM model
  g controller Api/UserController
  g migration create_users_table
  g view dashboard/index
  g helper string_helpers
  g config mail

Database:
  db migrate                   # run all migrations
  db rollback                  # revert last migration
  db reset                     # rollback + migrate + seed

Dev server:
  serve [-p 8080] [-q] [--live]

Cache:
  cache clear                  # flush all framework caches
```

---

## Contributing

Fork → branch off `v2.0.0-dev` → PR with tests.
Follow PSR-12. Every new feature needs unit tests in `tests/`.
