<?php

const SDF = true;
define('SDF_DIR', dirname(__DIR__) . '/sdf/');
define('SDF_APP', dirname(__DIR__) . '/app/');

require_once SDF_DIR . 'core/Core.php';
require_once SDF_DIR . 'core/Spark.php';
require_once SDF_DIR . 'core/Fuse.php';
require_once SDF_DIR . 'core/Router.php';
require_once SDF_DIR . 'core/Middleware.php';
require_once SDF_DIR . 'core/Guard.php';
require_once SDF_DIR . 'core/Scope.php';
require_once SDF_DIR . 'core/Request.php';
require_once SDF_DIR . 'core/Response.php';
require_once SDF_DIR . 'core/Logger.php';

// Test helpers
require_once __DIR__ . '/TestMiddlewares.php';
require_once __DIR__ . '/TestRequest.php';
