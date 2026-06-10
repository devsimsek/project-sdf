<?php

/**
 * Project SDF Initializer
 * devsimsek software development framework.
 * Copyright devsimsek
 * @package     SDF
 * @file        __init.php
 * @version     v1.5.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2022-2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/
 * @since       v1.0
 * @filesource
 */

use SDF\Core;
use SDF\Level;

if (!defined("SDF")) {
    print_r('PANIC: sdf is not called by it\'s own script.');
    exit(1);
}
const SDF_VERSION = "2.1.0";

// Check minimum version requirement of this framework.
// PHP 8.2 or higher is required, framework is tested and compatible up to PHP 8.5
if (version_compare(PHP_VERSION, "8.2.0") < 0) {
    throw new \RuntimeException(
        "FATAL ERROR: Sdf is designed to work with php 8.2 and upper versions. Please update your php version."
    );
}

// Check's if the application is running on a php cli server (aka. devserver)
if (PHP_SAPI == "cli-server") {
    header("X-Powered-By: SDF/" . SDF_VERSION);
    $url = parse_url($_SERVER["REQUEST_URI"]);
    $file = SDF_ROOT . $url["path"];

    // Live Reload Check
    if ($url["path"] === "/__sdf_reload_check") {
        $signalFile = sys_get_temp_dir() . "/sdf_reload.signal";
        echo file_exists($signalFile) ? file_get_contents($signalFile) : "0";
        exit();
    }

    // Cache Management (restricted to localhost)
    $remote = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
    $isLocal = in_array($remote, ["127.0.0.1", "::1"]);

    if ($url["path"] === "/__sdf_cache_clear") {
        if (!$isLocal) {
            header("HTTP/1.1 403 Forbidden");
            exit();
        }
        $temp = sys_get_temp_dir();
        @unlink($temp . "/sdf_routes.cache");
        @unlink($temp . "/sdf_config.cache");
        echo "Cache cleared.";
        exit();
    }

    if ($url["path"] === "/__sdf_cache_refresh") {
        if (!$isLocal) {
            header("HTTP/1.1 403 Forbidden");
            exit();
        }
        $temp = sys_get_temp_dir();
        @unlink($temp . "/sdf_routes.cache");
        @unlink($temp . "/sdf_config.cache");
        // Setting a reload signal will also refresh the browser
        file_put_contents($temp . "/sdf_reload.signal", time());
        echo "Cache refreshed. Browser will reload.";
        exit();
    }

    preg_match("/([0-9a-zA-Z]+).?(.*)?(\.(.*))/", $url["path"], $data);
    if (!empty($data) and array_key_exists("." . $data[4], SDF_STATIC_MIMES)) {
        if (file_exists($file) and !is_dir($file)) {
            header("Content-Type: " . SDF_STATIC_MIMES["." . $data[4]]);
            print file_get_contents($file);
            exit(1);
        }
    }
}
// Add Constants
require SDF_DIR . "constants.php";

// Load Composer autoloader (PSR-4 support for SDF\ namespace)
$composerAutoload = SDF_DIR . '../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// lets require our core, benchmark and router files.
require SDF_DIR . "core/Core.php";
$bm = Core::coreLoadClass("Benchmark");
// And Here We Start Benchmarking...
$bm->mark("__sdf__init__start__");

// Initialize Logger early so we can write debug marks
require_once SDF_DIR . "core/Logger.php";
Core::coreLoadConfigurations();
// Use configuration if present (do not crash if not)
$loggerConfig = Core::coreGetConfig("logger") ?: [];
$logger = SDF\Logger::getInstance($loggerConfig);

// Initialize Cache facade
require_once SDF_DIR . "core/Cache/CacheDriver.php";
require_once SDF_DIR . "core/Cache/Cache.php";
require_once SDF_DIR . "core/Cache/FileDriver.php";
require_once SDF_DIR . "core/Cache/RedisDriver.php";
require_once SDF_DIR . "core/Cache/MemcachedDriver.php";
$cacheDriver = Core::coreGetConfig("cache", "driver") ?: "file";
SDF\Logger::log(Level::DEBUG, "Cache facade initialized", ["driver" => $cacheDriver]);

// sdf-15: better error handling and managed exceptions
require_once SDF_DIR . "core/Exceptions.php"; // https://github.com/devsimsek/project-sdf/pull/12#discussion_r3299746185
require_once SDF_DIR . "core/ExceptionHandler.php";
set_exception_handler([\SDF\ExceptionHandler::class, "handle"]);
// Lets include our error handlers...
require SDF_APP . "handlers/errors.php";
// And Router...
$router = Core::coreLoadClass("Router");

// Initialize Spark ORM
$dbConfig = Core::coreGetConfig("database");
try {
    if ($dbConfig) {
        SDF\Logger::log(Level::DEBUG, "Initializing database connection", [
            "driver" => $dbConfig["driver"] ?? null,
        ]);
        // perf: use a static variable to hold dsn
        static $dsn = null;
        switch ($dbConfig["driver"]) {
            case "mysql":
                if ($dsn === null) {
                    $dsn = "mysql:host=" .
                      $dbConfig["host"] .
                      ";dbname=" .
                      $dbConfig["name"] .
                      ";port=" .
                      ($dbConfig["port"] ?? "3306") .
                      ";charset=" .
                      ($dbConfig["charset"] ?? "utf8mb4");
                }
                \SDF\Spark::connect(
                    $dsn,
                    $dbConfig["user"],
                    $dbConfig["password"],
                );
                break;
            case "psql":
            case "pgsql":
            case "postgres":
                if ($dsn === null) {
                    $dsn = "pgsql:host=" .
                      $dbConfig["host"] .
                      ";dbname=" .
                      $dbConfig["name"] .
                      ";port=" .
                      ($dbConfig["port"] ?? "5432");
                }

                \SDF\Spark::connect(
                    $dsn,
                    $dbConfig["user"],
                    $dbConfig["password"],
                );
                break;
            case "sqlite":
                $sqlitePath = $dbConfig["path"] ?? ($dbConfig["dsn"] ?? null);
                if ($sqlitePath === null) {
                    throw new \Exception(
                        "SQLite configuration missing path/dsn",
                    );
                }
                // If it already looks like a DSN (contains ':'), use as-is; otherwise prepend sqlite:
                if (str_contains($sqlitePath, ":")) {
                    \SDF\Spark::connect($sqlitePath);
                } else {
                    \SDF\Spark::connect("sqlite:" . $sqlitePath);
                }
                break;
            case "sqlsrv":
                // if using windows authentication
                if ($dbConfig["auth"]) {
                    \SDF\Spark::connect(
                        "sqlsrv:Server=" .
                            $dbConfig["host"] .
                            "," .
                            ($dbConfig["port"] ?? "1433") .
                            ";database=" .
                            $dbConfig["name"],
                    );
                } else {
                    \SDF\Spark::connect(
                        "sqlsrv:Server=" .
                            $dbConfig["host"] .
                            "," .
                            ($dbConfig["port"] ?? "1433") .
                            ";database=" .
                            $dbConfig["name"],
                        $dbConfig["user"],
                        $dbConfig["password"],
                    );
                }
                break;
            case "manual":
                // Check if 'args' is provided and is an array
                $args =
                    isset($dbConfig["args"]) && is_array($dbConfig["args"])
                        ? $dbConfig["args"]
                        : [];

                \SDF\Spark::connect($dbConfig["dsn"], ...$args);
                break;
            default:
                throw new Exception(
                    "Unsupported database driver: " . $dbConfig["driver"],
                );
        }
    }
} catch (Exception $e) {
    SDF\Logger::log(
        Level::FATAL,
        "Database connection failed: " . $e->getMessage(),
        ["exception" => $e],
    );
    throw new \SDF\HttpResponseException(
        "Database connection failed.",
        503,
        $e,
    );
}

$router::pathNotFound(SDF_EH_404);
$router::methodNotAllowed(SDF_EH_405);
// Set Routing Configuration (Class config not the routes.)
foreach (Core::coreGetConfig("app") as $config => $value) {
    if (str_starts_with($config, "rc_")) {
        $router::setRConfig(str_replace("rc_", "", $config), $value);
    }
}
// Initialize routes configuration
foreach (Core::coreGetConfig("routes") as $route => $controller) {
    if (is_array($controller)) {
        foreach ($controller as $item) {
            $router::add($route, $item[0], $item[1] ?? "any");
        }
    } else {
        $router::add($route, $controller);
    }
}
$logger->log(Level::DEBUG, "Router: preparing to ignite");
$bm->mark("__sdf__router__start__");
$router::ignite();
$logger->log(Level::DEBUG, "Router: ignite completed", [
    "elapsed_ms" => $bm->elapsedTime("__sdf__router__start__"),
]);
if (SDF_BENCHMARK) {
    print_r(
        '<script>console.log("SDF RENDERER DEBUG: Total Benchmark Result: ' .
            $bm->elapsedTime("__sdf__router__start__") .
            'ms.");</script>',
    );
}
// And This Is All :) Sdf must be initialized by now :)
