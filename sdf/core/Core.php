<?php

namespace SDF;

/**
 * smskSoft SDF Core
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Core.php
 * @version     v2.0.0
 * @changelog   v2.0.0 - Config caching added. JSON config support added.
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core
 * @since       Version 1.0
 * @filesource
 */

/**
 * Shared utilities trait for the SDF framework.
 * Provides class loading, config management, error handling, and directory scanning.
 * Storage lives on Core so all classes using this trait share the same state.
 */
trait CoreUtilities
{
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
    public static function &coreLoadClass(
        string $class,
        string $directory = "core",
        ?array $param = null,
    ): object {
        $_classes = \SDF\Core::$classes;
        // Does the class exist? If so, we're done...
        if (isset($_classes[$class])) {
            return $_classes[$class];
        }
        $name = false;
        $fqcn = "\\SDF\\" . $class;
        // Try Composer's PSR-4 autoloader first
        if (class_exists($fqcn)) {
            $name = $class;
        } else {
            // Fall back to manual file lookup in the application/libraries folder
            // then in the native system/libraries folder
            foreach ([SDF_APP, SDF_DIR] as $path) {
                if (file_exists($path . $directory . "/" . $class . ".php")) {
                    $name = $class;
                    if (class_exists($name, false) === false) {
                        require_once $path . $directory . "/" . $class . ".php";
                    }
                    break;
                }
            }
        }
        // Did we find the class?
        if ($name === false) {
            Logger::log(
                Level::ERROR,
                "Unable to locate the specified class: " . $class . ".php",
                ["class" => $class],
            );
            throw new HttpResponseException(
                "Unable to locate the specified class: " . $class . ".php",
                503
            );
        }
        // Keep track of what we just loaded
        self::coreIsLoaded($class);

        // Instantiate only when class is instantiable; otherwise store a placeholder
        try {
            $rc = new \ReflectionClass($fqcn);
            if ($rc->isInstantiable()) {
                $inst = isset($param) ? new $fqcn($param) : new $fqcn();
                \SDF\Core::$classes[$class] = $inst;
                return \SDF\Core::$classes[$class];
            } else {
                // abstract/interface: store a placeholder object with classname
                \SDF\Core::$classes[$class] = (object) ["__class" => $fqcn];
                return \SDF\Core::$classes[$class];
            }
        } catch (\ReflectionException $e) {
            Logger::log(
                Level::ERROR,
                "Unable to instantiate the specified class: " . $fqcn,
                ["exception" => $e],
            );
            throw new HttpResponseException(
                "Unable to instantiate the specified class: " . $fqcn,
                503,
                $e
            );
        }
    }

    /**
     * Load Configurations
     * @param string $directory
     * @return void
     */
    public static function coreLoadConfigurations(
        string $directory = "config",
    ): void {
        $cacheFile = sys_get_temp_dir() . "/sdf_config.cache";
        if (is_file($cacheFile)) {
            $raw = file_get_contents($cacheFile);
            // Try new format (serialize) first, fall back to old format (var_export)
            $config = @unserialize($raw, ['allowed_classes' => false]);
            if ($config !== false) {
                \SDF\Core::$config = $config;
                return;
            }
            // Old format: PHP return statement from var_export
            $config = require $cacheFile;
            if (is_array($config)) {
                \SDF\Core::$config = $config;
                // Rewrite in new format for next time
                file_put_contents($cacheFile, serialize($config));
                return;
            }
        }

        foreach (
            self::coreScanDirectory(
                SDF_APP . DIRECTORY_SEPARATOR . $directory,
                ".{php,json}",
            ) as $file
        ) {
            $filePath =
                SDF_APP .
                DIRECTORY_SEPARATOR .
                $directory .
                DIRECTORY_SEPARATOR .
                $file;
            if (is_file($filePath)) {
                if (str_ends_with($file, ".json")) {
                    $config = json_decode(file_get_contents($filePath), true);
                } else {
                    require $filePath;
                }
                if (isset($config)) {
                    $key = str_replace([".php", ".json"], "", $file);

                    if (
                        is_array($config) &&
                        array_key_exists($key, $config) &&
                        is_array($config[$key])
                    ) {
                        $cfgToStore = $config[$key];
                    } else {
                        $cfgToStore = $config;
                    }

                    if (isset(\SDF\Core::$config[$key])) {
                        \SDF\Core::$config[$key] = array_merge(
                            \SDF\Core::$config[$key],
                            $cfgToStore,
                        );
                    } else {
                        \SDF\Core::$config[$key] = $cfgToStore;
                    }
                }
            }
            $config = null;
        }
        file_put_contents(
            $cacheFile,
            serialize(\SDF\Core::$config),
        );
        chmod($cacheFile, 0600);
    }

    /**
     * Get Config Value by Key
     * @param string $config
     * @param string|null $key
     * @return false|mixed
     */
    public static function coreGetConfig(
        string $config,
        ?string $key = null,
    ): mixed {
        if (array_key_exists($config, \SDF\Core::$config)) {
            if (!empty($key)) {
                if (array_key_exists($key, \SDF\Core::$config[$config])) {
                    return \SDF\Core::$config[$config][$key];
                } else {
                    return false;
                }
            } else {
                return \SDF\Core::$config[$config];
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
    public static function coreTriggerError(
        int $errnum,
        string $errmessage,
        ?string $errfile = null,
        int $errline = 0,
    ): void {
        $input = [
            "errnum" => $errnum,
            "errmessage" => $errmessage,
            "errfile" => $errfile,
            "errline" => $errline,
        ];

        $customHandler = self::coreGetConfig("app", "eh_errorHandler");
        if ($customHandler && function_exists($customHandler)) {
            call_user_func_array($customHandler, $input);
            return;
        }

        if (function_exists("eh_errorHandler")) {
            call_user_func_array("eh_errorHandler", $input);
        } else {
            Logger::log(
                Level::ERROR,
                "(E_eh404) Fatal Error: [$errnum] $errmessage in $errfile on line $errline. (Also: SDF can't find errorHandler function)",
                $input,
            );
            throw new \RuntimeException(
                "(E_eh404) Fatal Error: [$errnum] $errmessage in $errfile on line $errline.",
            );
        }
    }

    /**
     * Scan Directory
     * @param string $directory
     * @param string $extension
     * @return false|array
     */
    public static function coreScanDirectory(
        string $directory = "",
        string $extension = ".{php}",
    ): false|array {
        if (empty($directory)) {
            return glob("*" . $extension, GLOB_BRACE);
        } else {
            $files = glob($directory . "/*" . $extension, GLOB_BRACE);
            if (is_array($files)) {
                $return = [];
                foreach ($files as $file) {
                    $return[] = str_replace($directory . "/", "", $file);
                }
                return $return;
            }
            return false;
        }
    }

    /**
     * Get Loaded Libraries
     * Keeps track of which libraries have been loaded. This function is
     * called by coreLoadClass
     * @param string $class
     * @return array
     */
    protected static function coreIsLoaded(string $class): array
    {
        if ($class !== "") {
            \SDF\Core::$isLoaded[strtolower($class)] = $class;
        }

        return \SDF\Core::$isLoaded;
    }
}

/**
 * Core class - entry point for framework utilities.
 * Storage lives here; the CoreUtilities trait shares this storage
 * across all classes that use it.
 */
class Core
{
    use CoreUtilities;

    /** @var array Stores the classes that have been loaded */
    public static array $isLoaded = [];
    /** @var array Stores the classes that have been loaded */
    public static array $classes = [];
    /** @var array Stores the configurations that have been loaded */
    public static array $config = [];
}
