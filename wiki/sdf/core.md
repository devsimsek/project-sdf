# Core Documentation

This is the core documentation of the SDF framework. Here you can find all the information you need to start developing
with the SDF core framework.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

## Ignition of the Core

The ignition of the core is done by the `__init.php` file in the sdf directory. This file is responsible for loading the
core classes and configurations. It is the second file that is executed when the framework is loaded.

The process of ignition is as follows:

1. The `index.php` file is executed.
2. The `__init.php` file is executed.
3. Constants are defined.
4. Check if running from CLI.
5. Load Core.
6. Load Benchmark and mark the start time.
7. Load Configurations.
8. Load Router.
9. Load Controller/Library/Model.
10. Load Sorm (can be disabled).
11. Set error handlers from the helpers.
12. Load router configurations.
13. Load the routes.
14. Mark router start time.
15. Ignite the router.
16. Mark total execution time.

This is the process of ignition of the core. The core is responsible for loading the classes and configurations. It also
loads the router and the controller/library/model classes.

This process happens within microseconds and the framework is ready to serve requests in no time.

## Provided Core Methods

> For detailed documentation of the code, navigate the `sdf/core/Core.php` file.

The core has a few methods that help sdf ignite itself. These methods are:

- `core_loadClass` - This method is used to load a class from the `core/classes` directory. Also it supports passing
  arguments to the class constructor. It keeps track of the loaded classes and returns the instance of the class if it
  is already loaded.
- `core_loadConfigurations` - This method is used to load the configurations from the `core/config` directory. It keeps
  track of the loaded configurations and returns the configuration if it is already loaded.
- `core_getConfig` - This method is used to get a configuration from the loaded configurations. It returns the
  configuration if it is loaded. Can be used to get a specific key from a specific configuration.
- `core_triggerError` - This method is used to trigger an error with a user defined helper. It is used to throw an error
  with a custom message. (reference: [Helpers documentation](app/helpers.md))
- `core_scanDirectory` - This method is used to scan a directory and return the files in it. It is used to load all the
  files in a directory. It returns an array of the files in the directory.
- `core_isLoaded` - This method is used to check if a class or a configuration is already loaded. It returns a boolean
  value.

These are the core methods that are provided by the SDF core framework. They are used to ignite the framework and keep
track of the loaded classes and configurations.

## Loader

The loader is responsible for loading the classes and configurations. It is the first class that is loaded when the
framework is ignited. The loader is responsible for loading the core classes and configurations. It is the heart of the
framework.

The loader has a few methods that help it load the classes and configurations. These methods are:

- `isLoaded` - This method is used to check if a class or a configuration is already loaded. It returns a boolean value.
- `load` - This method is used to keep track of the loaded classes and configurations. It returns true if the class or
  configuration is loaded. (private)
- `normalizeFilename` - This method is used to normalize the filename. It returns the normalized filename. (private)
- `loadFile` - This method is used to load a file. It returns the file content. (private)
- `view` - This method is used to load a view. It returns the view content.
- `helper` - This method is used to load a helper. It returns the helper content.
- `model` - This method is used to load a model. It returns the model content.
- `library` - This method is used to load a library. It returns the library content.
- `file` - This method is used to load a file. It returns the file content.
- `config` - This method is used to load a configuration. It returns the configuration content.

These are the methods that are provided by the loader class. They are used to load the classes and configurations. The
loader is the heart of the SDF framework.

## Router

The router is responsible for routing the requests to the correct controller. It is the second class that is loaded when
the framework is ignited. The router is responsible for routing the requests to the correct controller.

The router has a few methods that help it route the requests. These methods are:

- `add` - This method is used to add a route. It returns true if the route is added. It is used to add a route to the
  router.
  Also, it accepts regex patterns. The supported patterns are:
  - `{url}` - Matches any character except `/`.
  - `{id}` - Matches any number.
  - `{num}` - Matches any number.
  - `{all}` - Matches any character.
- `pathNotFound` - This method is used to set the path not found handler. It uses the helper function that user defined
  earlier in the helpers/errors file.
- `methodNotAllowed` - This method is used to set the method not allowed handler. It uses the helper function that user
  defined earlier in the helpers/errors file.
- `_getRoutes` - This method is used to get the routes. It returns the routes.
- `setRConfig` - This method is used to set a router configuration. It returns true if the key exists in the router
  configuration.
  This is done to prevent setting a non-existing key.
- `ignite` - This method is used to ignite the router. It passes the request to the correct controller.

## Controller

The controller is responsible for handling the requests. It does this while providing required modules/libraries to the
controller itself.

The controller has a few methods that help it handle the requests. These methods are:

- `load` - represents the loader class. It is used to load models, libraries, views, and more.
- `fuse` - represents the fuse view engine class. It is used to render views.
- `request` - represents the request class. It is used to get the request data.
- `response` - represents the response class. It is used to send the response.
- `get_config` - represents the configuration class. It is used to get the configuration. When provided with a key, it
  returns the value of the key.
- `load_config` - represents the configuration class. It is used to load the configuration. It returns the
  configuration.

These are the methods that are provided by the controller class. They are used to handle the requests and provide the
required modules/libraries to the controller.
