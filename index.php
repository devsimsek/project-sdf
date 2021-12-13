<?php
/**
 * Project SDF
 * devsimsek software development framework.
 * Copyright devsimsek
 * @package     SDF
 * @file        index.php
 * @version     v1.0.0 Early-Alpha Release
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/
 * @since       Version 1.0
 * @filesource
 */
const SDF = true;

/**
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Directory Setup
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Sdf has a nice setup for your application but
 * if you want to change your folder structure
 * you can change these lines bellow.
 * Note: NO Trailing Slash!
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_DIR: directory of sdf framework. if empty
 * sdf will search for 'sdf' directory
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_APP: the directory of application which
 * includes views, third-party libraries, helpers,
 * middlewares and models.
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_APP_CONF: the configuration directory for
 * sdf application.
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_APP_CONT: the controllers directory for
 * your sdf application.
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_APP_VIEW: the views directory for your sdf
 * application.
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_APP_HELP: the helpers directory for your
 * sdf application.
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_APP_LIB: the libraries directory for your
 * sdf application.
 * (Official libraries developed by
 * sdf team: https://github.com/smsksoft/sdf-libs)
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_APP_MODL: the models directory for your sdf
 * application.
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_APP_MIDD: the middlewares directory for
 * your sdf application.
 */
$SDF_DIR = '';
$SDF_APP = 'app';
$SDF_APP_CONF = 'config';
$SDF_APP_CONT = 'controllers';
$SDF_APP_VIEW = 'views';
$SDF_APP_HELP = 'helpers';
$SDF_APP_LIB = 'libraries';
$SDF_APP_MODL = 'models';
$SDF_APP_MIDD = 'middlewares';

// -----------------------------------------------

/**
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Environment
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 */
const SDF_ENV = 'development';

// -----------------------------------------------

/**
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Error Handlers
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF has a really nice feature which helps
 * developer choose his error handler function
 * or built-in handler function.
 * Such as 404, method not allowed, server side
 * error (500) and furthermore.
 * All Handlers Must Locate At
 * app/handlers/errors.php file.
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF_EH_404: Handles 404 (path not found) error.
 * SDF_EH_405: Handles 405 (method not allowed).
 */
const SDF_EH_404 = 'eh_pathNotFound';
const SDF_EH_405 = 'eh_methodNotAllowed';

// -----------------------------------------------

/**
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Benchmark
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Do you want to test you application's speed?
 * If you do please set the line true.
 * To view benchmarking open browser's developer
 * console.
 */
const SDF_Benchmark = true;

// -----------------------------------------------

/**
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Allowed Static Files
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * SDF gives strict access to local files.
 * Set your extensions and your mime types to
 * allow browser read files.
 * Notice: Static serving works only when
 * application environment is set to development
 * mode.
 * For example;
 * '.css' => 'text/css'
 */

const SDF_STATIC_MIMES = [
    '.css' => 'text/css',
    '.js' => 'text/javascript',
];

// -----------------------------------------------
// End Of The User Options, Please Don't Touch
// Bellow If You Don't Now What You're Doing.
// -----------------------------------------------

/**
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 * Initialize SDF
 * ------- ~ ------- ~ ------- ~ ------- ~ -------
 */
define("SDF_APP", $SDF_APP . DIRECTORY_SEPARATOR);
define("SDF_APP_CONF", SDF_APP . $SDF_APP_CONF . DIRECTORY_SEPARATOR);
define("SDF_APP_CONT", SDF_APP . $SDF_APP_CONT . DIRECTORY_SEPARATOR);
define("SDF_APP_VIEW", SDF_APP . $SDF_APP_VIEW . DIRECTORY_SEPARATOR);
define("SDF_APP_HELP", SDF_APP . $SDF_APP_HELP . DIRECTORY_SEPARATOR);
define("SDF_APP_LIB", SDF_APP . $SDF_APP_LIB . DIRECTORY_SEPARATOR);
define("SDF_APP_MODL", SDF_APP . $SDF_APP_MODL . DIRECTORY_SEPARATOR);
define("SDF_APP_MIDD", SDF_APP . $SDF_APP_MIDD . DIRECTORY_SEPARATOR);
define("SDF_ROOT", getenv('PWD'));
if (!file_exists($SDF_DIR)) {
    define("SDF_DIR", 'sdf/');
} else {
    define("SDF_DIR", $SDF_DIR);
}
// And pass the flag :)
// Please do not forget to configure your
// application in config directory.
require SDF_DIR . '__init.php';