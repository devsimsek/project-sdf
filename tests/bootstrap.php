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
require_once SDF_DIR . 'core/Auth/Guard.php';
require_once SDF_DIR . 'core/Auth/UserProvider.php';
require_once SDF_DIR . 'core/Auth/SessionGuard.php';
require_once SDF_DIR . 'core/Auth/JwtGuard.php';
require_once SDF_DIR . 'core/Auth/Auth.php';
require_once SDF_DIR . 'core/Auth/AuthMiddleware.php';
require_once SDF_DIR . 'core/Http/Stream.php';
require_once SDF_DIR . 'core/Http/Uri.php';
require_once SDF_DIR . 'core/Http/UploadedFile.php';
require_once SDF_DIR . 'core/Http/Response.php';
require_once SDF_DIR . 'core/Http/ServerRequest.php';
require_once SDF_DIR . 'core/Env.php';
require_once SDF_DIR . 'core/helpers.php';
require_once SDF_DIR . 'core/Encryption/Encrypter.php';
require_once SDF_DIR . 'core/Middleware/CsrfMiddleware.php';
require_once SDF_DIR . 'core/Middleware/CorsMiddleware.php';
require_once SDF_DIR . 'core/Middleware/RateLimitMiddleware.php';
require_once SDF_DIR . 'core/Validation/Validator.php';
require_once SDF_DIR . 'core/Spark/Paginator.php';

// New features (v2.3+)
require_once SDF_DIR . 'core/Log/LoggerAdapter.php';
require_once SDF_DIR . 'core/Events/ListenerProvider.php';
require_once SDF_DIR . 'core/Events/EventDispatcher.php';
require_once SDF_DIR . 'core/Schema/Blueprint.php';
require_once SDF_DIR . 'core/Schema/ForeignKeyDefinition.php';
require_once SDF_DIR . 'core/Schema/Schema.php';
require_once SDF_DIR . 'core/Localization/Translator.php';
require_once SDF_DIR . 'core/Mail/Mailable.php';
require_once SDF_DIR . 'core/Mail/Mailer.php';
require_once SDF_DIR . 'core/Mail/SmtpMailer.php';
require_once SDF_DIR . 'core/Mail/LogMailer.php';
require_once SDF_DIR . 'core/Mail/Mail.php';
require_once SDF_DIR . 'core/Queue/Queue.php';
require_once SDF_DIR . 'core/Queue/Job.php';
require_once SDF_DIR . 'core/Queue/DatabaseQueue.php';
require_once SDF_DIR . 'core/Queue/RedisQueue.php';
require_once SDF_DIR . 'core/Queue/Worker.php';
require_once SDF_DIR . 'core/Storage/Contracts/StorageDriver.php';
require_once SDF_DIR . 'core/Storage/Drivers/LocalDriver.php';
require_once SDF_DIR . 'core/Storage/Drivers/S3Driver.php';
require_once SDF_DIR . 'core/Storage/Storage.php';

// Test helpers
require_once __DIR__ . '/TestMiddlewares.php';
require_once __DIR__ . '/TestRequest.php';
