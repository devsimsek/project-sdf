<?php

/**
 * SDF Global Helper Functions
 *
 * @package     SDF
 * @subpackage  SDF Core
 * @filesource
 */

if (!function_exists('env')) {
    /**
     * Get an environment variable via SDF\Env.
     *
     * @param string     $key
     * @param mixed|null $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        return \SDF\Env::get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the current CSRF token (generates one if missing).
     *
     * @return string
     */
    function csrf_token(): string
    {
        return \SDF\Middleware\CsrfMiddleware::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Render a hidden CSRF token input field.
     *
     * @return string
     */
    function csrf_field(): string
    {
        return \SDF\Middleware\CsrfMiddleware::field();
    }
}
