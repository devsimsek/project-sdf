<?php

namespace SDF;

/**
 * smskSoft SDF Router
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Router.php
 * @version     v1.0.1 Early-Alpha Release
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

    /**
     * Set Router's Config
     * @param string $field
     * @param string $value
     * @return bool
     */
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
     * Next version this function will be more readable. Sorry for
     * the mess.
     * @param string $basePath
     * @return void
     */
    public static function ignite(string $basePath = '/'): void
    {
        $parsed_url = parse_url($_SERVER['REQUEST_URI']);
        $request_path = $parsed_url['path'] ?? '/';
        $request_method = ($_SERVER['REQUEST_METHOD'] ?? 'get');
        $controllerDir = self::$rConfig['controllersDir'];
        $path_match_found = false;
        $route_match_found = false;
        foreach (self::$routes as $route) {
            if ($basePath != '' && $basePath != '/') $route['expression'] = '(' . $basePath . ')' . $route['expression'];
            if (preg_match("@\A{$route['expression']}\z@", $request_path, $routeMatches)) {
                $path_match_found = true;
                if (strtolower($request_method) == strtolower($route['method']) or $route['method'] == 'any') {
                    array_shift($routeMatches);
                    if (is_callable($route['controller'])) call_user_func_array($route['controller'], $routeMatches);
                    $request = explode('/', $route['controller']);
                    if (count($request) >= 2) {
                        $possible_method = $request[count($request) - 1];
                        $possible_controller = $request[count($request) - 2];
                        $search_path = $controllerDir . join('/', array_slice($request, 0, -2));
                        // Simple Workaround. May Cause Problems In Future.
                        if (!file_exists($search_path)) {
                            $search_path = $controllerDir . join('/', array_slice(array_map('ucfirst', $request), 0, -2));
                        }
                        if (file_exists($search_path . '/' . $possible_controller . '.php')) {
                            if (!isset($foundController)) {
                                require $search_path . '/' . $possible_controller . '.php';
                                $foundController = true;
                            }
                            $route_match_found = true;
                        }
                        if (file_exists($search_path . '/' . ucfirst($possible_controller) . '.php')) {
                            if (!isset($foundController)) {
                                require $search_path . '/' . ucfirst($possible_controller) . '.php';
                                $foundController = true;
                            }
                            $route_match_found = true;
                        }
                        if ($route_match_found) {
                            if (is_callable($renderer = [new $possible_controller, $possible_method ?? 'index'])) {
                                call_user_func_array($renderer, $routeMatches);
                                return;
                            } else {
                                $path_match_found = false;
                            }
                        }
                    } else {
                        $controller = $request[count($request) - 1];
                        $search_path = $controllerDir . join('/', array_slice($request, 0, -1));
                        // Simple Workaround. May Cause Problems In Future.
                        if (!file_exists($search_path)) {
                            $search_path = $controllerDir . join('/', array_slice(array_map('ucfirst', $request), 0, -2));
                        }
                        if (file_exists($search_path . '/' . $controller . '.php')) {
                            if (!isset($foundController)) {
                                require $search_path . '/' . $controller . '.php';
                                $foundController = true;
                            }
                            $route_match_found = true;
                        }
                        if (file_exists($search_path . '/' . ucfirst($controller) . '.php')) {
                            if (!isset($foundController)) {
                                require $search_path . '/' . ucfirst($controller) . '.php';
                                $foundController = true;
                            }
                            $route_match_found = true;
                        }
                        if ($route_match_found) {
                            if (is_callable($renderer = [new $controller, 'index'])) {
                                call_user_func_array($renderer, $routeMatches);
                                return;
                            } else {
                                $path_match_found = false;
                            }
                        }
                    }
                }
            }
        }
        if (self::$rConfig['magicRouting']) {
            $request = explode('/', $request_path);
            array_shift($request);
            if (count($request) > 2) {
                $possible_method = $request[count($request) - 1];
                $possible_controller = $request[count($request) - 2];
                $search_path = $controllerDir . join('/', array_slice($request, 0, -2));
                // Simple Workaround. May Cause Problems In Future.
                if (!file_exists($search_path)) {
                    $search_path = $controllerDir . join('/', array_slice(array_map('ucfirst', $request), 0, -2));
                }
                if (file_exists($search_path . '/' . $possible_controller . '.php')) {
                    if (!isset($foundController)) {
                        require $search_path . '/' . $possible_controller . '.php';
                        $foundController = true;
                    }
                    $route_match_found = true;
                }
                if (file_exists($search_path . '/' . ucfirst($possible_controller) . '.php')) {
                    if (!isset($foundController)) {
                        require $search_path . '/' . ucfirst($possible_controller) . '.php';
                        $foundController = true;
                    }
                    $route_match_found = true;
                }
                if ($route_match_found) {
                    if (is_callable($renderer = [new $possible_controller, $possible_method ?? 'index'])) {
                        call_user_func_array($renderer, $routeMatches);
                        return;
                    } else {
                        $path_match_found = false;
                    }
                }
            } else {
                $controller = $request[count($request) - 1];
                $search_path = $controllerDir . join('/', array_slice($request, 0, -1));
                // Simple Workaround. May Cause Problems In Future.
                if (!file_exists($search_path)) {
                    $search_path = $controllerDir . join('/', array_slice(array_map('ucfirst', $request), 0, -2));
                }
                if (file_exists($search_path . '/' . $controller . '.php')) {
                    if (!isset($foundController)) {
                        require $search_path . '/' . $controller . '.php';
                        $foundController = true;
                    }
                    $route_match_found = true;
                }
                if (file_exists($search_path . '/' . ucfirst($controller) . '.php')) {
                    if (!isset($foundController)) {
                        require $search_path . '/' . ucfirst($controller) . '.php';
                        $foundController = true;
                    }
                    $route_match_found = true;
                }
                if ($route_match_found) {
                    if (is_callable($renderer = [new $controller, 'index'])) {
                        call_user_func_array($renderer, $routeMatches);
                        return;
                    } else {
                        $path_match_found = false;
                    }
                }
            }
        }
        if (!$route_match_found) {
            if ($path_match_found) {
                header("HTTP/1.0 405 Method Not Allowed");
                if (self::$rConfig['methodNotAllowed']) {
                    call_user_func_array(self::$rConfig['methodNotAllowed'], array($request_path, $request_method));
                }
            } else {
                header("HTTP/1.0 404 Not Found");
                call_user_func_array(self::$rConfig['pathNotFound'], array($request_path));
            }
        } else {
            if (!$path_match_found) {
                header("HTTP/1.0 404 Not Found");
                call_user_func_array(self::$rConfig['pathNotFound'], array($request_path));
            }
        }
    }
}