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
class Core
{
    // @var array $isLoaded Stores the classes that have been loaded
    private static array $isLoaded = [];
    // @var array $classes Stores the classes that have been loaded
    private static array $classes = [];
    // @var array $config Stores the configurations that have been loaded
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
    public static function &core_loadClass(
        string $class,
        string $directory = "core",
        ?array $param = null
    ): object {
        $_classes = self::$classes;
        // Does the class exist? If so, we're done...
        if (isset($_classes[$class])) {
            return $_classes[$class];
        }
        $name = false;
        // Look for the class first in the local application/libraries folder
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
        // Did we find the class?
        if ($name === false) {
            SDF\Logger::log(Level::ERROR, 'Unable to locate the specified class: ' . $class . '.php', ['class' => $class]);
            throw new \RuntimeException('Unable to locate the specified class: ' . $class . '.php', 5);
        }
        // Keep track of what we just loaded
        $fqcn = "\\SDF\\" . $name;
        self::core_isLoaded($class);

        // Instantiate only when class is instantiable; otherwise store a placeholder
        try {
            $rc = new \ReflectionClass($fqcn);
            if ($rc->isInstantiable()) {
                $inst = isset($param) ? new $fqcn($param) : new $fqcn();
                self::$classes[$class] = $inst;
                return self::$classes[$class];
            } else {
                // abstract/interface: store a placeholder object with classname
                self::$classes[$class] = (object)["__class" => $fqcn];
                return self::$classes[$class];
            }
        } catch (\ReflectionException $e) {
            SDF\Logger::log(Level::ERROR, 'Unable to instantiate the specified class: ' . $fqcn, ['exception' => $e]);
            throw new \RuntimeException('Unable to instantiate the specified class: ' . $fqcn, 5, $e);
        }
    }

    /**
     * Load Configurations
     * @param string $directory
     * @return void
     */
    public static function core_loadConfigurations(
        string $directory = "config"
    ): void {
        $cacheFile = sys_get_temp_dir() . '/sdf_config.cache';
        if (file_exists($cacheFile)) {
            self::$config = require $cacheFile;
            return;
        }

        foreach (
            self::core_scanDirectory(SDF_APP . DIRECTORY_SEPARATOR . $directory, ".{php,json}") as $file
        ) {
            $filePath = SDF_APP . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $file;
            if (file_exists($filePath)) {
                if (str_ends_with($file, '.json')) {
                    $config = json_decode(file_get_contents($filePath), true);
                } else {
                    require $filePath;
                }
                if (isset($config)) {
                    $key = str_replace([".php", ".json"], "", $file);

                    // If the config file returned a wrapper array keyed by the filename (e.g. $config['database'] = [...])
                    // then unwrap it to keep self::$config['database'] = [...]
                    if (is_array($config) && array_key_exists($key, $config) && is_array($config[$key])) {
                        $cfgToStore = $config[$key];
                    } else {
                        $cfgToStore = $config;
                    }

                    if (isset(self::$config[$key])) {
                        self::$config[$key] = array_merge(
                            self::$config[$key],
                            $cfgToStore
                        );
                    } else {
                        self::$config[$key] = $cfgToStore;
                    }
                }
            }
            $config = null;
        }
        file_put_contents($cacheFile, '<?php return ' . var_export(self::$config, true) . ';');
    }

    /**
     * Get Config Value by Key
     * @param string $config
     * @param string|null $key
     * @return false|mixed
     */
    public static function core_getConfig(
        string $config,
        ?string $key = null
    ): mixed {
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
    public static function core_triggerError(
        int    $errnum,
        string $errmessage,
        ?string $errfile = null,
        int    $errline = 0
    ): void {
        $input = [
          "errnum" => $errnum,
          "errmessage" => $errmessage,
          "errfile" => $errfile,
          "errline" => $errline,
        ];

        $customHandler = self::core_getConfig("app", "eh_errorHandler");
        if ($customHandler && function_exists($customHandler)) {
            call_user_func_array($customHandler, $input);
            return;
        }

        if (function_exists("eh_errorHandler")) {
            call_user_func_array("eh_errorHandler", $input);
        } else {
            // todo: currently no custom error handler available, create a new ticket in yt
            SDF\Logger::log(Level::ERROR, "(E_eh404) Fatal Error: [$errnum] $errmessage in $errfile on line $errline. (Also: SDF can't find errorHandler function)", $input);
            throw new \RuntimeException("(E_eh404) Fatal Error: [$errnum] $errmessage in $errfile on line $errline.");
        }
    }

    /**
     * Scan Directory
     * @param string $directory
     * @param string $extension
     * @return false|array
     */
    public static function core_scanDirectory(
        string $directory = "",
        string $extension = ".{php}"
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
     * called by core_loadClass
     * @param string $class
     * @return array
     */
    protected static function core_isLoaded(string $class): array
    {
        if ($class !== "") {
            self::$isLoaded[strtolower($class)] = $class;
        }

        return self::$isLoaded;
    }
}
