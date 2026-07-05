# Handlers Documentation

This is the documentation for the handlers in the app. Here you can find all the information you need to get started.

> Handlers provide small, reusable functions that the framework or your controllers can call - for example custom error handlers, global utility functions, or boot-time callbacks. This section documents handler conventions, recommended function signatures, how to register/load handlers from `app/handlers/`, and common examples you can copy and adapt. If you need additional examples or integrations, please open an issue.

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

For example, let's create loadConfig function in `global.php` handler:

```php
<?php

function loadConfig($config, $path): array
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

In this example, we used the `loadConfig` function to load the configuration file. The function takes two
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
        $config = loadConfig('config', 'app/config/'); // the same functionality is already built in $this->loadConfig() from Controller definition
        $this->response->json($config);
    }
}
```

In this example, we used the `load->handler()` method to load the `global.php` handler. We then used the `loadConfig()`
function to load the configuration file and output it as JSON.

## Conclusion

In this section, you learned how to create handlers in SDF. You learned about the basic handler, functions, and using
handlers in controllers.

## Error Handlers (v2.3.1+)

The framework calls two built-in error handlers wired in `sdf/__init.php`:

- `eh_pathNotFound($requestPath)` - called by the Router on 404
- `eh_methodNotAllowed($requestPath, $requestMethod)` - called on 405

Both live in `app/handlers/errors.php` alongside `eh_errorHandler`. As of **v2.3.1**, these handlers:

- Send the correct HTTP status (`404 Not Found` / `405 Method Not Allowed`) and an explicit `Content-Type: text/html; charset=UTF-8` header before any output.
- HTML-escape every user-controlled value (`$requestPath`, `$requestMethod`) with `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8')` to prevent reflected XSS.

> Security: Never echo `$requestPath` or `$requestMethod` raw - both come from `$_SERVER['REQUEST_URI']` / `$_SERVER['REQUEST_METHOD']` and are fully attacker-controlled via the raw HTTP request line. If you replace these handlers, follow the same escaping + header pattern shown in `app/handlers/errors.php`.

A hardened 404 handler looks like:

```php
function eh_pathNotFound($requestPath): void
{
    if (!headers_sent()) {
        header('HTTP/1.0 404 Not Found');
        header('Content-Type: text/html; charset=UTF-8');
    }
    if (!is_array($requestPath)) {
        $safePath = htmlspecialchars($requestPath, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        print_r('404 Error, Path ' . $safePath . ' Not Found.');
    } else {
        print_r('404 Error, Path Not Found.');
    }
    exit();
}
```
