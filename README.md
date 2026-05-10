# Project SDF v2.0.0

Project SDF is a fast, robust, and modern project development framework designed
for PHP enthusiasts. It is compact, easy to maintain, and extremely extendable.

Version 2.0.0 introduces massive architectural improvements, performance boosts,
and better security out of the box.

![version v2.0.0](https://img.shields.io/badge/version-v2.0.0-blue)
[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://devsimsek.mit-license.org)

## Features & Highlights

- **MVC Pattern:** Clean separation of concerns.
- **Spark ORM:** Built-in QueryBuilder, Active Record implementation, and PDO
  connection manager. (Replaces `Sorm`).
- **Middleware Pipeline:** PSR-15 inspired request filtering.
- **Guards:** Explicit authentication and authorization classes.
- **Application Scopes:** Organize context gracefully (Controller, Helper,
  Global, System, View).
- **Fast Routing:** Route compilation and caching for massive performance gains.
- **Config Loader:** Supports `.php` and `.json` configs. Compiled to cache
  automatically.
- **Modern Fuse View Engine:** Compiles to raw PHP. High performance and
  `eval()`-free for enhanced security.
- **CLI Commands:** Generators for Models, Controllers, Migrations, and more
  (`sdf/cli`).
- **Composer Ready:** Optional but recommended PSR-4 autoloading via Composer,
  while retaining standalone compatibility.

## Tech Stack

**PHP:** 8.0 or higher is required. The framework is fully tested and compatible
up to PHP 8.5.

## Installation

### Via Git

```bash
git clone https://github.com/devsimsek/project-sdf.git
cd project-sdf
```

### Via Composer

```bash
composer create-project devsimsek/project-sdf
```

## Quick Start

### 1. Configuration

Configure the framework inside `app/config/app.php` or `app/config/app.json`.

### 2. Generate Code

Use the bundled CLI to generate components:

```bash
# Generate a new Controller
php sdf/cli g controller UserController

# Generate a new Spark Model
php sdf/cli g model User
```

### 3. Run Development Server

```bash
php sdf/cli serve -p 8000
```

## Documentation

Full documentation is available in the `wiki/` directory.

- [Core Components](wiki/sdf/home.md)
- [Spark ORM](wiki/libraries/sorm.md) (Updating soon)
- [Fuse Template Engine](wiki/libraries/fuse.md)

## Feedback

If you have any feedback or encounter issues, please open an issue within the
repository.

## Contributing

Contributions are always welcome! Please follow PSR-12 coding standards and
write tests for your components before opening a pull request.

## Authors

- [@devsimsek](https://www.github.com/devsimsek)
