<?php

namespace SDF;

/**
 * smskSoft SDF Core
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF
 * @file        Core.php
 * @version     v1.0.0 Early-Alpha Release
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core#router
 * @since       Version 1.0
 * @filesource
 */
class Core
{

    private static array $isLoaded = [];
    private static array $classes = [];
    private static array $config = [];

    /**
     * Load Class
     *
     * This function acts as a singleton. If the requested class does not
     * exist it is instantiated and set to a static variable. If it has
     * previously been instantiated the variable is returned.
     *
     * @param string $class
     * @param string $directory
     * @param array|null $param
     * @return object
     */
    public static function &core_loadClass(string $class, string $directory = 'core', array $param = null): object
    {
        $_classes = self::$classes;
        // Does the class exist? If so, we're done...
        if (isset($_classes[$class])) {
            return $_classes[$class];
        }
        $name = false;
        // Look for the class first in the local application/libraries folder
        // then in the native system/libraries folder
        foreach (array(SDF_APP, SDF_DIR) as $path) {
            if (file_exists($path . $directory . '/' . $class . '.php')) {
                $name = $class;
                if (class_exists($name, false) === false) {
                    require_once($path . $directory . '/' . $class . '.php');
                }
                break;
            }
        }
        // Is the request a class extension? If so we load it too
        if (file_exists(SDF_APP . $directory . '/' . $class . '.php')) {
            $name = $class;
            if (class_exists($name, false) === false) {
                require_once(SDF_APP . $directory . '/' . $name . '.php');
            }
        }
        // Did we find the class?
        if ($name === false) {
            header('HTTP/1.0 503 Service Unavailable', true, 503);
            echo 'Unable to locate the specified class: ' . $class . '.php';
            exit(5);
        }
        // Keep track of what we just loaded
        $name = '\\SDF\\' . $name;
        self::core_isLoaded($class);
        $_classes[$class] = isset($param)
            ? new $name($param)
            : new $name();
        return $_classes[$class];
    }

    /**
     * Example configuration;
     * $config['config_file']['config_key'];
     * @param string $directory
     */
    public static function core_loadConfigurations(string $directory = 'config')
    {
        foreach (self::core_scanDirectory(SDF_APP . DIRECTORY_SEPARATOR . $directory) as $file) {
            if (file_exists(SDF_APP . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $file)) {
                require SDF_APP . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $file;
                if (isset($config)) {
                    if (isset(self::$config[str_replace('.php', '', $file)])) {
                        self::$config[str_replace('.php', '', $file)] = array_merge(self::$config[str_replace('.php', '', $file)], $config);
                    } else {
                        self::$config[str_replace('.php', '', $file)] = $config;
                    }
                }
            }
            $config = null;
        }
    }

    /**
     * @param string $config
     * @param string|null $key
     * @return false|mixed
     */
    public static function core_getConfig(string $config, string $key = null): mixed
    {
        if (array_key_exists($config, self::$config)) {
            if (!empty($key)) {
                if (array_key_exists($key, self::$config[$config])) {
                    return self::$config[$config][$key];
                } else {
                    return false;
                }
            } else {
                return self::$config[$config];
            }
        }
        return false;
    }

    /**
     * Trigger Error
     * @param int $errnum
     * @param string $errmessage
     * @param string|null $errfile
     * @param int $errline
     * @return void
     */
    public static function core_triggerError(int $errnum, string $errmessage, string $errfile = null, int $errline = 0): void
    {
        if (!self::core_getConfig('app', 'eh_errorHandler')) {
            $input = [
                "errnum" => $errnum,
                "errmessage" => $errmessage,
                "errfile" => $errfile,
                "errline" => $errline,
            ];
            call_user_func_array(self::core_getConfig('app', 'eh_errorHandler'), $input);
        }
        if (function_exists('eh_errorHandler')) {
            $input = [
                "errnum" => $errnum,
                "errmessage" => $errmessage,
                "errfile" => $errfile,
                "errline" => $errline,
            ];
            call_user_func_array('eh_errorHandler', $input);
        } else {
            die("(E_eh404) Error. SDF can't find errorHandler function. Maybe it does not exists?");
        }
    }

    /**
     * Scan Directory
     * @param string $directory
     * @param string $extension
     * @return false|array
     */
    public static function core_scanDirectory(string $directory = '', string $extension = '.{php}'): false|array
    {
        if (empty($directory)) {
            return glob('*' . $extension, GLOB_BRACE);
        } else {
            $files = glob($directory . '/*' . $extension, GLOB_BRACE);
            if (is_array($files)) {
                $return = [];
                foreach ($files as $file) {
                    array_push($return, str_replace($directory . '/', '', $file));
                }
                return $return;
            }
            return false;
        }
    }

    /**
     * Keeps track of which libraries have been loaded. This function is
     * called by core_loadClass
     * @param string $class
     * @return array
     */
    protected static function core_isLoaded(string $class): array
    {
        if ($class !== '') {
            self::$isLoaded[strtolower($class)] = $class;
        }

        return self::$isLoaded;
    }
}