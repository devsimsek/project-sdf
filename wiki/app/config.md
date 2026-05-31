# App Configuration

SDF is a simple framework that allows you to configure your application with ease. In this section, you will learn how
to configure your application.

## Configuration Files

Most of the framework related configuration is done using the `index.php` file.
You can set which environment you are in, whether to use fuse as a view engine or not,
your directory structure,
custom error handling,
usage of the Benchmark library,
static mimes for development and local server,
and many more.

Database configuration (recommended)

- Place `app/config/database.php` with a top-level `$config` array containing driver-specific keys. This is the canonical shape used by the initializer and CLI.

Example (SQLite in-memory):

```php
<?php
$config = [
    'driver' => 'sqlite',
    'path' => ':memory:', // or '/var/www/app/database.sqlite'
];
```

Example (MySQL):

```php
<?php
$config = [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'name' => 'myapp',
    'user' => 'dbuser',
    'password' => 'secret',
    'port' => 3306,
    'charset' => 'utf8mb4',
];
```

- The initializer accepts either a full DSN (via `dsn`) or the component keys above. For SQLite the bootstrap detects full DSNs (e.g., `sqlite::memory:`) and will avoid double-prefixing `sqlite:`.
- If your legacy config used `$config['database'] = [...]`, the loader will still read it, but it's recommended to use the top-level `$config` shape above for clarity.
Routing is done in the `app/config/routes.php` file. You can define your routes here.

Ability to use magic routing is also available. You can enable it in the `app/config/app.php` file.

All the custom configuration is dependent on you, you can create new config files and use them in your application.

> Tip: use ./sdf/cli g config <config_name> to generate a new config file.

## Framework Configuration

The framework configuration is done in the `index.php` file. Here you can set the following:

- `USE_FUSE` - Whether to use Fuse as a view engine or not. Default is `true`.
- Directory structure - You can set your directory structure here. For reference, check the `index.php` file.
- Custom error handling - You can set your custom error handling function names here, the functions must be located
  at `app/handlers/errors.php` file.
- Benchmark library - You can enable or disable the Benchmark library here. Default is `true`.
- Static mimes for development and local server - You can set your static mimes here. Check the `index.php` file for
  reference.
- `SDF_ENV` - You can set your environment here. Default is `development`.

> Note: SDF is still under development, I do not recommend using it in the production without testing the application
> yourself. With that said, SDF is somewhat ready for production use.

## Application Configuration

Application configuration is done in the `app/config/app.php` file. Here you can set the following:

- `rc_magic_routing` - Whether to use magic routing or not. Default is `false`.
  MagicRouting registers method names as routes. For example, if you have a method named `getUsers` in your users
  controller,
  you can access it by visiting `/users/getUsers`.

- `app_{custom}` - You can create your custom configuration here. For example, if you want to set a custom configuration
  for your application, you can set it here.

## Routing Configuration

Routing configuration is done in the `app/config/routes.php` file. Here you can define your routes.

You can define your routes as follows:

```php
$config['/'] = 'Home/index'; // or $config['/'] = 'Home';
```

As you can see, you can define your routes with or without the method name.

> Note: You can use magic routing to register method names as routes. Check the `app/config/app.php` file for more

> For detailed information on routing, check the [Routing](routes.md) documentation.

## Custom Configuration

You can create your custom configuration files and use them in your application.

To create a new configuration file, run the following command:

```bash
./sdf/cli g config <config_name>
```

This will generate a new configuration file in the `app/config` directory.

-- or --

You can create a new configuration file manually. Just create a new file in the `app/config` directory and use it in
your
application.

> Note: You can use the `loadConfig()` function to get the configuration values in your application.

## Conclusion

In this section, you learned how to configure your application using SDF. You learned about the framework configuration,
application configuration, routing configuration, and custom configuration.

If you have any questions, feel free to reach out to me on [Mastodon](https://universeodon.com/@devsimsek).
