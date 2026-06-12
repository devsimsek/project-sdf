# CLI Documentation

> The CLI documentation describes available commands and examples for code generation, database management, and development workflows. It includes usage patterns and tips for common tasks. If you need additional examples, please open an issue or contact.

## Definition

sdf/cli is a command line interface that allows you to generate code for your application as well as run the application
in development mode.
It requires php 8.0 or higher installed on your machine as well as the PATH environment variable set to the php executable. The CLI is tested and compatible up to PHP 8.5.

> You can always use the zsh scripts to run commands. Be aware that the scripts may be outdated thus it is recommended
> to use the php cli.

## Commands

The following commands are available in the cli:

- `generate (or g)` - Generates code for your application.
  - `controller (or c)` - Generates a controller.
  - `model (or m)` - Generates a model.
  - `view (or v)` - Generates a view.
  - `helper (or h)` - Generates a helper.
  - `migration (or migrate)` - Generates a migration.
  - `route (or r)` - Generates a route.
  - `config (or cfg)` - Generates a configuration file.
  - `seeder (or s)` - Generates a database seeder.
  - `middleware (or mw)` - Generates a request middleware.
  - `guard (or g)` - Generates an authorization guard.
  - `test (or t)` - Generates a PHPUnit test scaffold.
    - `--type=` (unit, controller, model, integration)
    - `--namespace=` (FQCN for the target class)
    - `--force` (overwrite existing file)
- `database (or db)` - Manages the database.
  - `migrate` - Migrates the database.
  - `rollback` - Rolls back the last migration.
  - `seed` - Seeds the database.
  - `reset` - Resets the database.
- `cache` - Manages framework caches.
  - `clear` - Clears all framework caches.
  - `watch` - Watches files and automatically clears caches (used by `--live`).
- `format (or fmt)` - Runs PHP-CS-Fixer. Accepts any PHP-CS-Fixer arguments.
- `benchmark (or bench)` - Runs `wrk` benchmarks against the dev server.
- `serve (or devserver)` - Runs the application in development mode.
  - `-q` - Runs the application in quiet mode.
  - `-p` - Specifies the port to run the application on. Default is 8000.
  - `--live` - Enables live-reload watcher.
  - `--clear-cache` or `-c` - Clears framework caches (config, routes, Fuse) before starting the server.

### Generate

The generate command allows you to generate code for your application. The following subcommands are available:

#### Controller

The controller subcommand allows you to generate a controller. The controller is generated in the `app/controllers`
directory.

```bash
./sdf/cli generate controller <name>
```

#### Model

The model subcommand allows you to generate a model. The model is generated in the `app/models` directory.

```bash
./sdf/cli generate model <name>
```

#### View

The view subcommand allows you to generate a view. The view is generated in the `app/views` directory.

```bash
./sdf/cli generate view <name>
```

#### Helper

The helper subcommand allows you to generate a helper. The helper is generated in the `app/helpers` directory.

```bash
./sdf/cli generate helper <name>
```

#### Migration

The migration subcommand allows you to generate a migration. The migration is generated in the `app/migrations`
directory.

You can also generate a migration from a model by specifying the `from-model` option. The migration name needs to be
the same as the model name and the model needs to be in the `app/models` directory.

```bash
./sdf/cli generate migration <name> [from-model]
```

#### Route

The route subcommand allows you to generate a route. The route is generated in the `app/routes` directory.

You can specify the request method for the route. The default request method is `GET`.

```bash
./sdf/cli generate route \<path\> \<controller\>/\<method\> [request-method]
```

#### Config

The config subcommand allows you to generate a configuration file. The configuration file is generated in the
`app/config` directory.

```bash
./sdf/cli generate config <name>
```

#### Test (new)

You can scaffold a PHPUnit test using the `generate test` (or `g test`) command. This creates a basic test class under
`tests/` with the `Tests` namespace so it can be picked up by PHPUnit.

```bash
# generate a test named UserTest
./sdf/cli generate test User

# generate a test named UserControllerTest
./sdf/cli g test UserControllerTest
```

The generated file will be `tests/UserTest.php` or `tests/UserControllerTest.php` and contains a single `test_example` method.

## Serve

The serve command allows you to run the application in development mode. The application is run on `localhost:8000` by
default. It auto-detects a `frankenphp` binary — if found it uses `frankenphp php-server`, otherwise falls back to the
PHP built-in server (`php -S`).

```bash
./sdf/cli serve
```

Live reload and dev endpoints

- The devserver exposes helper endpoints and live-reload support only when explicitly enabled. To enable live reload, set the environment variable `SDF_LIVE_RELOAD=true`.
- For safety, development-only endpoints (cache clear/refresh, live reload) are restricted to requests coming from `localhost`. On shared networks, do not enable live reload unless you trust the environment.

## Running Tests (new)

You can run the test suite directly from the CLI using the `test` (or `tests`) command. Any additional arguments are
passed to the PHPUnit executable.

```bash
# run full suite
./sdf/cli test

# run with a filter
./sdf/cli test --filter PipelineExecutionTest

# run a specific tests file via phpunit args
./sdf/cli tests --testsuite unit
```

The CLI will look for `vendor/bin/phpunit` first and fall back to a global `phpunit` if not found.

## Usage

To use the cli, you need to navigate to the root directory of your application and run the cli script.

```bash
./sdf/cli <command> [subcommand] [options]
```

## Examples

To generate a controller:

```bash
./sdf/cli generate controller Home
```

This will generate a controller named `Home` in the `app/controllers` directory.

To generate a model:

```bash
./sdf/cli generate model User
```

This will generate a model named `User` in the `app/models` directory.

To generate a migration:

```bash
./sdf/cli generate migration CreateUsersTable
```

This will generate a migration named `CreateUsersTable_{timestamp}` in the `app/migrations` directory.

To generate a migration from a model:

```bash
./sdf/cli generate migration User from-model
```

This will generate a migration named `User_{timestamp}` based on the `User` model in the `app/migrations` directory.

To run the application in development mode:

```bash
./sdf/cli serve
```

This will run the application on `localhost:8000`.

To run the application in quiet mode:

```bash
./sdf/cli serve -q
```

This will run the application in quiet mode.

To run the application on a specific port:

```bash
./sdf/cli serve -p 8080
```

This will run the application on `localhost:8080`.

## Contributing

If you would like to contribute to the cli, feel free to submit a pull request. You can also reach out to me on
[Mastodon](https://universeodon.com/@devsimsek) if you have any questions or suggestions.
