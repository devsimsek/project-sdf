<?php
/**
 * Project SDF Initializer
 * devsimsek software development framework.
 * Copyright devsimsek
 * @package     SDF
 * @file        __init.php
 * @version     v1.0.0 Early-Alpha Release
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/
 * @since       Version 1.0
 * @filesource
 */
if (!defined('SDF') and !SDF) {
    print_r('PANIC: sdf is not called by it\'s own script. Are you attacking sdf?');
    exit(1);
}
const SDF_VERSION = 1.0;

// Check minimum version requirement of this framework.
if (version_compare(PHP_VERSION, '8.0.0') <= 0) {
    die('FATAL ERROR: Sdf is designed to work with php 8.0 and upper versions. Please update your php version.');
}

// Before everything lets check if request static file
// Is php cli and development? if not the server will
// process request.
if (PHP_SAPI == 'cli-server' and SDF_ENV == 'development') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = SDF_ROOT . $url['path'];
    preg_match("/([0-9a-zA-Z]+).?(.*)?(\.(.*))/", $url['path'], $data);
    if (!empty($data) and array_key_exists('.' . $data[4], SDF_STATIC_MIMES)) {
        if (file_exists($file) and !is_dir($file)) {
            header('Content-Type: ' . SDF_STATIC_MIMES['.' . $data[4]]);
            print file_get_contents($file);
            exit(1);
        }
    }
}
// Add Constants
require SDF_DIR . 'constants.php';
// lets require our core, benchmark and router files.
require SDF_DIR . 'core/Core.php';
$initializer = new SDF\Core();
$bm = $initializer::core_loadClass('Benchmark');
// And Here We Start Benchmarking...
$bm->mark('__sdf__init__start__');
$initializer::core_loadConfigurations();
// Lets include our error handlers...
require SDF_APP . 'handlers/errors.php';
// And Router...
$security = $initializer::core_loadClass('Security');
$router = $initializer::core_loadClass('Router');
// And Model, Controller, Middleware...
$initializer::core_loadClass('Controller');
$initializer::core_loadClass('Library');
$initializer::core_loadClass('Model');
$router::pathNotFound(SDF_EH_404);
$router::methodNotAllowed(SDF_EH_405);
// Set Routing Configuration (Class config not the routes.)
foreach ($initializer::core_getConfig('app') as $config => $value) {
    if (str_starts_with('rc_', $value)) {
        $router::setRConfig(str_replace('rc_', '', $config), $value);
    }
}
// Initialize routes configuration
foreach ($initializer::core_getConfig('routes') as $route => $controller) {
    if (is_array($controller)) {
        $router::add($route, $controller[0], $controller[1]);
    } else {
        $router::add($route, $controller);
    }
}
$bm->mark('__sdf__router__start__');
$router::ignite();
if (SDF_Benchmark) {
    print_r('<script>console.log("SDF RENDERER DEBUG: Total Benchmark Result: ' . $bm->elapsed_time("__sdf__router__start__") . 'ms.");</script>');
}
// And This Is All :) Sdf must be initialized by now :)
