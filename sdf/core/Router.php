<?php

namespace SDF;

/**
 * smskSoft SDF Router
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Router.php
 * @version     v1.0.0 Early-Alpha Release
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core#router
 * @since       Version 1.0
 * @filesource
 */
class Router extends Core
{
    /**
     * The $routes array will contain our URI's and callbacks.
     * @var array
     */
    protected static array $routes = [];

    /**
     * Router settings
     * @var array
     */
    protected static array $rConfig = [
        'debug' => false,
        'magicRouting' => true,
        'controllersDir' => SDF_APP_CONT,
        'pathNotFound' => '',
        'methodNotAllowed' => '',
    ];

    // --------------------------------------------------------------------

    /**
     * @param string $field
     * @param string $value
     * @return bool
     */
    public static function _setSetting(string $field, string $value): bool
    {
        if (array_key_exists($field, self::$rConfig)) {
            self::$rConfig[$field] = $value;
            return true;
        }
        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Creates route
     */
    public static function add(string $expression, string $controller, string $method = 'any'): bool
    {
        $patterns = [
            '{url}' => '([0-9a-zA-Z]+)',
            '{id}' => '([0-9]+)',
            '{all}' => '(.*)'
        ];
        $expression = str_replace(array_keys($patterns), array_values($patterns), $expression);
        return array_push(self::$routes, ['expression' => $expression, 'controller' => $controller, 'method' => $method]);
    }

    // --------------------------------------------------------------------

    /**
     * Runs when no match found in routing scheme
     * @param $function
     */
    public static function pathNotFound($function)
    {
        self::$rConfig['pathNotFound'] = $function;
    }

    // --------------------------------------------------------------------

    /**
     * Runs when the specific route has specific method other than user
     * tries to navigate
     * @param $function
     */
    public static function methodNotAllowed($function)
    {
        self::$rConfig['methodNotAllowed'] = $function;
    }

    // --------------------------------------------------------------------

    /**
     * Return's All Routes That Mapped Into Router Class
     * @return array
     */
    public static function _getRoutes(): array
    {
        return self::$routes;
    }

    // --------------------------------------------------------------------

    public static function setRConfig(string $field, string $value): bool
    {
        if (array_key_exists($field, self::$rConfig)) {
            self::$rConfig[$field] = $value;
            return true;
        }
        // Return false and print err.
        self::core_triggerError(SDF_E_RENDER, 'Router config ' . $field . ' not needed.');
        return false;
    }

    // --------------------------------------------------------------------

    /**
     * Ignite router
     * Starts routing class and render's each controller by request
     * @param string $basePath
     * @return void
     */
    public static function ignite(string $basePath = '/'): void
    {
        $parsed_url = parse_url($_SERVER['REQUEST_URI']);
        $path = $parsed_url['path'] ?? '/';
        $method = ($_SERVER['REQUEST_METHOD'] ?? 'get');
        $path_match_found = false;
        $route_match_found = false;
        foreach (self::$routes as $route) {
            if ($basePath != '' && $basePath != '/') $route['expression'] = '(' . $basePath . ')' . $route['expression'];
            if (preg_match("@\A{$route['expression']}\z@", $path, $matches)) {
                $path_match_found = true;
                if (strtolower($method) == strtolower($route['method']) or $route['method'] == 'any') {
                    array_shift($matches);
                    if ($basePath != '' && $basePath != '/') array_shift($matches);
                    if (is_callable($route['controller'])) {
                        call_user_func_array($route['controller'], $matches);
                    } else {
                        if (sscanf($route['controller'], '%[^/]/%s', $class, $function) !== 2) {
                            $function = 'index';
                        } else {
                            $controller = self::$rConfig['controllersDir'];
                            sscanf($function, '%[^/]/%s', $controllerClass, $function);
                            if (file_exists($controller . ucfirst($class) . '.php') or file_exists($controller . $class . '.php')) {
                                $class = ucfirst($class);
                                require $controller . $class . '.php';
                                $route_match_found = true;
                            }
                            if (file_exists($controller . $class . DIRECTORY_SEPARATOR . ucfirst($controllerClass) . '.php')) {
                                $classPath = $class . DIRECTORY_SEPARATOR;
                                $class = ucfirst($controllerClass);
                                require $controller . $classPath . $class . '.php';
                                $route_match_found = true;
                            }
                            if ($route_match_found) {
                                if (is_callable($renderer = [new $class, $function])) {
                                    call_user_func_array($renderer, $matches);
                                } else {
                                    $path_match_found = false;
                                }
                            }
                        }
                    }
                    $route_match_found = true;
                    break;
                }
            } else {
                // Begin magic routing
                if (self::$rConfig['magicRouting']) {
                    $path_match_found = false;
                    if (sscanf(substr($path, 1), '%[^/]/%s', $class, $function) !== 2) {
                        $function = 'index';
                    }
                    if (!empty($class)) {
                        $controller = self::$rConfig['controllersDir'] . DIRECTORY_SEPARATOR;
                        if (file_exists($controller . ucfirst($class) . '.php') or file_exists($controller . $class . '.php')) {
                            $route_match_found = true;
                            $class = ucfirst($class);
                            require $controller . $class . '.php';
                        }
                        sscanf($function, '%[^/]/%s', $controllerclass, $function);
                        if (file_exists($controller . $class . DIRECTORY_SEPARATOR . ucfirst($controllerclass) . '.php')) {
                            $route_match_found = true;
                            $classPath = $class . DIRECTORY_SEPARATOR;
                            $class = ucfirst($controllerclass);
                            require $controller . $classPath . $class . '.php';
                        }
                        if ($route_match_found) {
                            if (is_callable($renderer = [new $class, $function])) {
                                $path_match_found = true;
                                call_user_func_array($renderer, $matches);
                            } else {
                                $path_match_found = false;
                            }
                        }
                        break;
                    }
                }
            }
        }
        if (!$route_match_found) {
            if ($path_match_found) {
                header("HTTP/1.0 405 Method Not Allowed");
                if (self::$rConfig['methodNotAllowed']) {
                    call_user_func_array(self::$rConfig['methodNotAllowed'], array($path, $method));
                }
            } else {
                header("HTTP/1.0 404 Not Found");
                call_user_func_array(self::$rConfig['pathNotFound'], array($path));
            }
        } else {
            if (!$path_match_found) {
                header("HTTP/1.0 404 Not Found");
                call_user_func_array(self::$rConfig['pathNotFound'], array($path));
            }
        }
    }
}