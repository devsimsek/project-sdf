<?php

const SDF = true;
define('SDF_DIR', dirname(__DIR__) . '/sdf/');
define('SDF_APP', dirname(__DIR__) . '/app/');
define('SDF_ROOT', dirname(__DIR__));
define('SDF_APP_CONF', SDF_APP . 'config/');
define('SDF_APP_CONT', SDF_APP . 'controllers/');
define('SDF_APP_VIEW', SDF_APP . 'views/');
define('SDF_APP_HELP', SDF_APP . 'helpers/');
define('SDF_APP_LIB', SDF_APP . 'libraries/');
define('SDF_APP_MODL', SDF_APP . 'models/');
define('SDF_APP_MIDD', SDF_APP . 'middlewares/');
if (!defined('USE_FUSE')) {
    define('USE_FUSE', false);
}

require_once SDF_DIR . 'core/Core.php';
require_once SDF_DIR . 'core/Benchmark.php';
require_once SDF_DIR . 'core/Controller.php';
require_once SDF_DIR . 'core/Loader.php';
require_once SDF_DIR . 'core/Library.php';
require_once SDF_DIR . 'core/Model.php';
require_once SDF_DIR . 'core/Spark.php';
require_once SDF_DIR . 'core/Spark/Model.php';
require_once SDF_DIR . 'core/Spark/Migration.php';
require_once SDF_DIR . 'core/Spark/Seeder.php';
require_once SDF_DIR . 'core/Spark/Pool.php';
require_once SDF_DIR . 'core/Fuse.php';
require_once SDF_DIR . 'core/Router.php';
require_once SDF_DIR . 'core/Middleware.php';
require_once SDF_DIR . 'core/Pipeline.php';
require_once SDF_DIR . 'core/Middleware/LiveReloadMiddleware.php';
require_once SDF_DIR . 'core/Guard.php';
require_once SDF_DIR . 'core/Scope.php';
require_once SDF_DIR . 'core/Request.php';
require_once SDF_DIR . 'core/Exceptions.php';
require_once SDF_DIR . 'core/ExceptionHandler.php';
require_once SDF_DIR . 'core/Response.php';
require_once SDF_DIR . 'core/Logger.php';
require_once SDF_DIR . 'core/Cache/CacheDriver.php';
require_once SDF_DIR . 'core/Cache/Cache.php';
require_once SDF_DIR . 'core/Cache/FileDriver.php';
require_once SDF_DIR . 'core/Cache/RedisDriver.php';
require_once SDF_DIR . 'core/Cache/MemcachedDriver.php';
require_once SDF_DIR . 'core/Session.php';
require_once SDF_DIR . 'core/Flash.php';

// Test helpers
require_once __DIR__ . '/TestMiddlewares.php';
require_once __DIR__ . '/TestRequest.php';
