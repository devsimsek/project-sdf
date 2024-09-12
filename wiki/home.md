# Welcome to the wiki!

This is the home page of the wiki, where you can find all the information you need to get started.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

> Completion status: 100% (18/18) (Except tutorials)
> Tutorials status: 0% (0/5)

# Navigation

- [Home](home.md) <!-- Done -->
- [App](app/home.md)
  - [Tutorials](app/tutorials/home.md)
  - [Configuration](app/config.md)
  - [Controllers](app/controllers.md)
  - [Handlers](app/handlers.md)
  - [Helpers](app/helpers.md)
  - [Libraries](app/libraries.md)
  - [Models](app/models.md)
  - [Routes](app/routes.md)
  - [Views](app/views.md)
- [Core](sdf/home.md)
  - [Router](sdf/core.md#router)
  - [Controller](sdf/core.md#controller)
  - [Router](sdf/core.md#router)
  - [Loader](sdf/core.md#loader)
- [CLI](sdf/cli.md) <!-- Done -->
  - [Commands](sdf/cli.md#commands)
  - [Usage](sdf/cli.md#usage)
- [Libraries](libraries/home.md)
  - [Benchmark](libraries/benchmark.md)
  - [Request](libraries/request.md)
  - [Response](libraries/response.md)
  - [Fuse](libraries/fuse.md)
  - [Sorm](libraries/sorm.md)
- [Getting started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Configuration](#configuration)
  - [Using CLI to generate code](#using-cli-to-generate-code)
  - [Running the application in development mode](#running-the-application-in-development-mode)
  - [Contributing](#contributing)

## Getting started

### Prerequisites

To use the framework, you need to have the following installed:

- [Php 8](https://www.php.net/)
  - Enable the following extensions:
    - `pdo`
    - `sqlite3`

### Installation

To install the framework, you need to clone the repository.

```bash
git clone https://github.com/devsimsek/project-sdf
cd project-sdf
```

### Configuration

To configure the framework, navigate into index.php and app/config/app.php.

### Using CLI to generate code

With version v1.5 you can use the CLI to generate code.

> CLI requires php to be installed on your system and added to the PATH.

To generate a controller, run the following command:

```bash
./sdf/cli g controller <controller_name>
```

To generate a model, run the following command:

> Version v1.5 uses Sorm\Model as the default model class. You can change this by removing use SDF\Sorm\Model and adding
> use SDF\Model.

```bash
./sdf/cli g model <model_name>
```

To generate a migration, run the following command:

```bash
./sdf/cli g migration <migration_name>
```

> You can use ```./sdf/cli g migration <migration_name> from-model``` to generate a migration from a model. Migration
> name needs to be the same as the model name.

### Running the application in development mode

To run the application in development mode, you can use the built-in PHP server.

```bash
php -S localhost:8000
```

or you can use the following command to run the application in development mode:

```bash
./sdf/cli serve
```

## Contributing

To contribute to the project, you can fork the repository and create a pull request.
