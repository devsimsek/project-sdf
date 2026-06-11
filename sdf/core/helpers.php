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
     * @param string $sessionKey Session key (default: '_csrf_token').
     * @return string
     */
    function csrf_token(string $sessionKey = '_csrf_token'): string
    {
        return \SDF\Middleware\CsrfMiddleware::token($sessionKey);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Render a hidden CSRF token input field.
     *
     * @param string $sessionKey Session key (default: '_csrf_token').
     * @return string
     */
    function csrf_field(string $sessionKey = '_csrf_token'): string
    {
        return \SDF\Middleware\CsrfMiddleware::field($sessionKey);
    }
}
