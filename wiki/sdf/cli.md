# CLI Documentation

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

## Definition

sdf/cli is a command line interface that allows you to generate code for your application as well as run the application
in development mode.
It requires the php 8 installed on your machine as well as the PATH environment variable set to the php executable.

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
- `database (or db)` - Manages the database.
  - `migrate` - Migrates the database.
  - `rollback` - Rolls back the last migration.
  - `seed` - Seeds the database.
  - `reset` - Resets the database.
- `serve (or devserver)` - Runs the application in development mode.
  - `-q` - Runs the application in quiet mode.
  - `-p` - Specifies the port to run the application on. Default is 8000.

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

## Serve

The serve command allows you to run the application in development mode. The application is run on `localhost:8000` by
default.

```bash
./sdf/cli serve
```

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
[Twitter](https://x.com/devsimsek) if you have any questions or suggestions.
