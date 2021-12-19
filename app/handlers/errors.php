<?php

/**
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
 * @return void
 */
function eh_errorHandler(): void
{
}