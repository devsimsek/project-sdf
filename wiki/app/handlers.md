# Handlers Documentation

This is the documentation for the handlers in the app. Here you can find all the information you need to get started.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

## Basic Handler

A basic handler in SDF looks like this:

```php
<?php

function someFunction(SDF\Request $request, SDF\Response $response)
{
    $response->text('Hello, World!');
}
```

Let's break down the handler:

- `someFunction` - This is the name of the function, you can name it whatever you want.
- `$request` - This is the request object. You can use this object to get the request data.
- `$response` - This is the response object. You can use this object to send the response data.
- `$response->text('Hello, World!');` - This is the output of the handler.

For example, let's create load_config function in `global.php` handler:

```php
<?php

function load_config($config, $path): array
{
    // remove extension if exists from $config
    $config = str_replace('.php', '', $config);
    // add the extension
    $config .= '.php';
    // check if the file exists
    if (file_exists($path . $config)) {
        // include the file
        require_once $path . $config; // require_once is used to prevent multiple inclusions of the same file
    } else {
        // if the file does not exist, throw an exception
        throw new Exception('Config file not found: ' . $config);
    }
    // check $config variable is an array
    if (!is_array($config)) {
        // if not, throw an exception
        throw new Exception('Config file is not an array: ' . $config);
    }
    return $config;
}
```

In this example, we used the `load_config` function to load the configuration file. The function takes two
parameters: `$config` and `$path`. The function loads the configuration file and returns the configuration array.

## Using Handlers in Controllers

You can use handlers in controllers as follows:

```php
<?php

class Home extends SDF\Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->handler('global.php'); // load the global.php handler
    }

    public function index()
    {
        $config = load_config('config', 'app/config/'); // the same functionality is already built in $this->load_config() from Controller definition
        $this->response->json($config);
    }
}
```

In this example, we used the `load->handler()` method to load the `global.php` handler. We then used the `load_config()`
function to load the configuration file and output it as JSON.

## Conclusion

In this section, you learned how to create handlers in SDF. You learned about the basic handler, functions, and using
handlers in controllers.
