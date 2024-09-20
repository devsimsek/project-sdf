<?php

namespace SDF;

/**
 * smskSoft SDF Router
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Router.php
 * @version     v1.5.0 Revision 1
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core.md#router
 * @since       v1.0
 * @filesource
 */
class Router extends Core
{
  /**
   * @var array
   */
  protected static array $routes = [];

  /**
   * @var array
   */
  protected static array $config = [
    "debug" => false,
    "magicRouting" => true,
    "controllersDir" => SDF_APP_CONT,
    "pathNotFound",
    "methodNotAllowed",
    "case_matters" => false,
    "trailing_slash_matters" => false,
    "multimatch" => false,
    "basepath" => "/",
  ];

  /**
   * @param string $expression
   * @param string $controller
   * @param string $method
   * @return void
   */
  public static function add(string $expression, string $controller, string $method = "any"): void
  {
    $patterns = [
      "{url}" => "([0-9a-zA-Z]+)",
      "{id}" => "([0-9]+)",
      "{num}" => "([0-9]+)",
      "{all}" => "(.*)",
    ];
    $expression = str_replace(
      array_keys($patterns),
      array_values($patterns),
      $expression
    );

    self::$routes[$expression] = [
      "expression" => $expression,
      "controller" => $controller,
      "method" => $method,
    ];
  }

  /**
   * @return array
   */
  public static function _getRoutes(): array
  {
    return self::$routes;
  }

  /**
   * @param $function
   * @return void
   */
  public static function pathNotFound($function): void
  {
    self::$config["pathNotFound"] = $function;
  }

  /**
   * @param $function
   * @return void
   */
  public static function methodNotAllowed($function): void
  {
    self::$config["methodNotAllowed"] = $function;
  }

  /**
   * @param string $field
   * @param $value
   * @return bool
   */
  public static function setRConfig(string $field, $value): bool
  {
    if (array_key_exists($field, self::$config)) {
      self::$config[$field] = $value;
      return true;
    }
    // Return false and print err.
    self::core_triggerError(
      SDF_E_RENDER,
      "Router config " . $field . " not needed."
    );
    return false;
  }

  /**
   * Ignite router
   * Starts routing class and render's each controller by request
   * Handles 404 and 405 errors.
   * Also handles magic routing.
   * @return void
   */
  public static function ignite(): void
  {
    $basepath = rtrim(self::$config["basepath"], "/");
    $parsed_url = parse_url($_SERVER["REQUEST_URI"]);
    $request_path = $parsed_url["path"] ?? "/";

    if (isset($parsed_url["path"])) {
      if (self::$config["trailing_slash_matters"]) {
        $request_path = $parsed_url["path"];
      } else {
        $request_path = ($basepath . '/' != $parsed_url['path']) ? rtrim($parsed_url['path'], '/') : $parsed_url['path'];
      }
    }

    $request_method = $_SERVER["REQUEST_METHOD"] ?? "get";
    $path_match_found = false;
    $route_match_found = false;
    $controllerDir = self::$config["controllersDir"];
    $routeMatches = [];

    foreach (self::$routes as $route) {
      if ($basepath != "" && $basepath != "/") {
        $route["expression"] = "(" . $basepath . ")" . $route["expression"];
      }

      $route['expression'] = '^' . $route['expression'] . '$';

      if (preg_match("#" . $route["expression"] . "#" . (self::$config["case_matters"] ? "" : "i") . (self::$config["multimatch"] ? "" : "u"), $request_path, $routeMatches)) {
        $path_match_found = true;
        if (strtolower($request_method) == strtolower($route["method"]) || $route["method"] == "any") {
          array_shift($routeMatches);
          if (is_callable($route["controller"])) {
            call_user_func_array($route["controller"], $routeMatches);
            return;
          }
          $route_match_found = self::handleController($route["controller"], $controllerDir, $routeMatches);
          if ($route_match_found) return;
        }
      }
    }

    if (self::$config["magicRouting"]) {
      $request = explode("/", $request_path);
      array_shift($request);
      $route_match_found = self::handleMagicRouting($request, $controllerDir, $routeMatches);
      if ($route_match_found) return;
    }

    self::handleNotFound($route_match_found, $path_match_found, $request_path, $request_method);
  }

  /**
   * Handle controller.
   * @param $controller
   * @param $controllerDir
   * @param $routeMatches
   * @return bool
   */
  private static function handleController($controller, $controllerDir, $routeMatches): bool
  {
    $request = explode("/", $controller);
    return self::internalInvoker($request, $controllerDir, $routeMatches);
  }

  /**
   * Handle magic routing.
   * @param $request
   * @param $controllerDir
   * @param $routeMatches
   * @return bool
   */
  private static function handleMagicRouting($request, $controllerDir, $routeMatches): bool
  {
    return self::internalInvoker($request, $controllerDir, $routeMatches);
  }

  /**
   * Adjust search path.
   * @param $search_path
   * @param $request
   * @return mixed
   */
  private static function adjustSearchPath($search_path, $request): mixed
  {
    if (!file_exists($search_path)) {
      $search_path = self::$config["controllersDir"] . join("/", array_slice(array_map("ucfirst", $request), 0, -2));
    }
    return $search_path;
  }

  /**
   * Require a controller file.
   * @param $search_path
   * @param $controller
   * @return bool
   */
  private static function requireControllerFile($search_path, $controller): bool
  {
    if (file_exists($search_path . "/" . $controller . ".php")) {
      require $search_path . "/" . $controller . ".php";
      return true;
    }
    if (file_exists($search_path . "/" . ucfirst($controller) . ".php")) {
      require $search_path . "/" . ucfirst($controller) . ".php";
      return true;
    }
    return false;
  }

  /**
   * Call a controller method.
   * @param $controller
   * @param $method
   * @param $routeMatches
   * @return bool
   */
  private static function callControllerMethod($controller, $method, $routeMatches): bool
  {
    if (is_callable($renderer = [new $controller(), $method])) {
      call_user_func_array($renderer, $routeMatches);
      return true;
    }
    return false;
  }

  /**
   * Handle not found routes.
   * @param $route_match_found
   * @param $path_match_found
   * @param $request_path
   * @param $request_method
   * @return void
   */
  private static function handleNotFound($route_match_found, $path_match_found, $request_path, $request_method): void
  {
    if (!$route_match_found) {
      if ($path_match_found) {
        header("HTTP/1.0 405 Method Not Allowed");
        if (self::$config["methodNotAllowed"]) {
          call_user_func_array(self::$config["methodNotAllowed"], [$request_path, $request_method]);
        }
      } else {
        header("HTTP/1.0 404 Not Found");
        call_user_func_array(self::$config["pathNotFound"], [$request_path]);
      }
    } else {
      if (!$path_match_found) {
        header("HTTP/1.0 404 Not Found");
        call_user_func_array(self::$config["pathNotFound"], [$request_path]);
      }
    }
  }

  /**
   * Internal invoker for controller and method.
   * @param $request
   * @param $controllerDir
   * @param $routeMatches
   * @return bool
   */
  private static function internalInvoker($request, $controllerDir, $routeMatches): bool
  {
    if (count($request) >= 2) {
      $possible_method = $request[count($request) - 1];
      $possible_controller = $request[count($request) - 2];
      $search_path = $controllerDir . join("/", array_slice($request, 0, -2));
      $search_path = self::adjustSearchPath($search_path, $request);

      if (self::requireControllerFile($search_path, $possible_controller)) {
        return self::callControllerMethod($possible_controller, $possible_method, $routeMatches);
      }
    } else {
      $controller = $request[count($request) - 1];
      $search_path = $controllerDir . join("/", array_slice($request, 0, -1));
      $search_path = self::adjustSearchPath($search_path, $request);

      if (self::requireControllerFile($search_path, $controller)) {
        return self::callControllerMethod($controller, "index", $routeMatches);
      }
    }
    return false;
  }

}
