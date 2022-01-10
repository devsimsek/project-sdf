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