<?php

if (!defined('SDF_ROOT')) {
    define('SDF_ROOT', __DIR__);
}

if (!defined('SDF_DIR')) {
    define('SDF_DIR', SDF_ROOT . '/sdf/');
}

if (!defined('SDF_APP')) {
    define('SDF_APP', SDF_ROOT . '/app/');
}

if (!defined('SDF_APP_CONF')) {
    define('SDF_APP_CONF', SDF_APP . 'config/');
}

if (!defined('SDF_APP_CONT')) {
    define('SDF_APP_CONT', SDF_APP . 'controllers/');
}

if (!defined('SDF_APP_VIEW')) {
    define('SDF_APP_VIEW', SDF_APP . 'views/');
}

if (!defined('SDF_APP_HELP')) {
    define('SDF_APP_HELP', SDF_APP . 'helpers/');
}

if (!defined('SDF_APP_LIB')) {
    define('SDF_APP_LIB', SDF_APP . 'libraries/');
}

if (!defined('SDF_APP_MODL')) {
    define('SDF_APP_MODL', SDF_APP . 'models/');
}

if (!defined('USE_FUSE')) {
    define('USE_FUSE', false);
}

require_once SDF_DIR . 'constants.php';
