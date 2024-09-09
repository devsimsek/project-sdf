<?php

namespace SDF;

/**
 * smskSoft SDF Loader
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Loader.php
 * @version     v1.5.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2024, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core#loader
 * @since       Version 1.0
 * @filesource
 */
class Loader
{
    /**
     * List of loaded files.
     * @var array
     */
    protected static array $isLoaded = [];

    /**
     * Get the list of loaded files.
     * @return array
     */
    public static function getIsLoaded(): array
    {
        return self::$isLoaded;
    }

    /**
     * Load a view file.
     * @param string $name
     * @param array|object $params
     * @param string $directory
     * @return bool
     */
    public function view(
        string $name,
        array|object $params = [],
        string $directory = SDF_APP_VIEW
    ): bool {
        $name = $this->normalizeFilename($name);

        if (!$this->isLoaded($name) && file_exists($directory . $name)) {
            if (is_object($params) && !USE_FUSE) {
                $params = get_object_vars($params);
            }
            if (is_array($params)) {
                extract($params);
            }

            $this->load($name);

            if (USE_FUSE) {
                $fuse = new Fuse();
                echo $fuse->with($params)->render($name, $directory);
            } else {
                require $directory . $name;
            }
            // checkpoint: Maybe i need to rollback to returning view content, and supporting view chaining
            return true;
        }

        return false;
    }

    /**
     * Check if a file is loaded.
     * @param string $name
     * @return bool
     */
    public function isLoaded(string $name): bool
    {
        return isset(self::$isLoaded[strtolower($name)]);
    }

    /**
     * Mark a file as loaded.
     * @param string $name
     */
    private function load(string $name): void
    {
        self::$isLoaded[strtolower($name)] = true;
    }

    /**
     * Load a helper file.
     * @param string $name
     * @param string $directory
     * @return bool
     */
    public function helper(string $name, string $directory = SDF_APP_HELP): bool
    {
        return $this->loadFile($name, $directory);
    }

    /**
     * Load a model file and instantiate the model class.
     * @param string $name
     * @param string $directory
     * @return object|bool
     */
    public function model(
        string $name,
        string $directory = SDF_APP_MODL
    ): object|bool {
        $name = $this->normalizeFilename($name);

        if (!$this->isLoaded($name) && file_exists($directory . $name)) {
            $this->load($name);
            require_once $directory . $name;

            $className = ucfirst(str_replace(".php", "", $name));
            return new $className();
        }

        return false;
    }

    /**
     * Load a library file and instantiate the library class.
     * @param string $name
     * @param array|object $params
     * @param string $directory
     * @return object|bool
     */
    public function library(
        string $name,
        array|object $params = [],
        string $directory = SDF_APP_LIB
    ): object|bool {
        $name = $this->normalizeFilename($name);

        if (!$this->isLoaded($name) && file_exists($directory . $name)) {
            $this->load($name);
            require_once $directory . $name;

            $className = ucfirst(str_replace(".php", "", strtolower($name)));
            // checkpoint: maybe i need to rollback $this->$name dynamic instantiation
            // surpressing depreciation warning with @
            return new $className(...(array) $params);
        }

        return false;
    }

    /**
     * Load a file.
     * @param string $name
     * @param string $directory
     * @return mixed
     */
    public function file(string $name, string $directory = SDF_DIR): mixed
    {
        return $this->loadFile($name, $directory);
    }

    /**
     * Load a config file.
     * @param string $file
     * @param string $directory
     * @return bool|array
     */
    public function config(
        string $file,
        string $directory = SDF_APP_CONF
    ): bool|array {
        $filePath =
            SDF_APP .
            DIRECTORY_SEPARATOR .
            $directory .
            DIRECTORY_SEPARATOR .
            $file;

        if (file_exists($filePath)) {
            require_once $filePath;
            // @var array $config
            return $config ?? false;
        }

        return false;
    }

    /**
     * Normalize the filename to ensure it has the correct extension.
     * @param string $name
     * @return string
     */
    private function normalizeFilename(string $name): string
    {
        return str_ends_with($name, ".php") ? $name : $name . ".php";
    }

    /**
     * Load a file and mark it as loaded.
     * @param string $name
     * @param string $directory
     * @return mixed
     */
    private function loadFile(string $name, string $directory): mixed
    {
        $name = $this->normalizeFilename($name);

        if (!$this->isLoaded($name) && file_exists($directory . $name)) {
            $this->load($name);
            return require_once $directory . $name;
        }

        return false;
    }
}
