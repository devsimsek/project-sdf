# Helpers Documentation

This is the documentation for the helpers in the app. Here you can find all the information you need to get started.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

Helpers are your best friends when it comes to changing the core's behavior. You can use helpers to extend the core's
functionality without changing the core itself.

For example, SDF Core uses helper error handlers, with this way, you can change the error handling behavior without
changing the core.

## Errors Helper

For example, we can look at the ```app/helpers/errors.php``` file:

```php
<?php

/**
 * Path Not Found Error Handler.
 * Called By Router.
 * @param $requestPath
 * @return void
 */
function eh_pathNotFound($requestPath): void
{
    if (!is_array($requestPath)) {
        print_r('404 Error, Path ' . $requestPath . ' Not Found.');
    } else {
        print_r('404 Error, Path Not Found.');
    }
    exit();
}

/**
 * Method Not Allowed Error Handler.
 * Called By Router.
 * @param $requestPath
 * @param $requestMethod
 * @return void
 */
function eh_methodNotAllowed($requestPath, $requestMethod): void
{
    print_r("Method " . $requestMethod . " not allowed on this path.");
    exit();
}

/**
 * Error Handler. (Called by core.)
 * Example error input;
 * $input = [
 * "errnum" => $errnum,
 * "errmessage" => $errmessage,
 * "errfile" => $errfile,
 * "errline" => $errline,
 * ];
 * @return void
 */
function eh_errorHandler(): void
{
}
```

In this example, we used the `eh_pathNotFound`, `eh_methodNotAllowed`, and `eh_errorHandler` functions to handle errors.
The functions take parameters and output the error message.

Helpers are dynamically loaded by the core, so you don't need to include them in your applications.

> Note: SDF will allow more functionality in the future, such as changing the behavior of the Benchmark output, fuse's
> behavior, etc.

## Conclusion

In this section, you learned how to create helpers in SDF. You learned about the basic helper, functions, and using
