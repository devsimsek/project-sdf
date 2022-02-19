<?php

namespace SDF;

class Loader
{

    protected static array $isLoaded = [];

    /**
     * Load view.
     * @param string $name
     * @param array|object $params
     * @param string $directory
     * @return bool|null
     */
    public function view(string $name, array|object $params = [], string $directory = SDF_APP_VIEW): bool|object
    {
        if (!isset(self::$isLoaded[strtolower($name)]) or !isset(self::$isLoaded[strtolower($name) . '.php'])) {
            if (!str_ends_with($name, '.php')) {
                $name .= '.php';
            }
            if (file_exists($directory . $name)) {
                if (!empty($params) and !is_array($params)) {
                    $params = get_object_vars($params);
                }
                extract($params);
                if (!$this->isLoaded($name)) {
                    $this->load($name);
                    return require $directory . $name;
                }
            }
        }
        return false;
    }

    /**
     * Load Helper
     * @param string $name
     * @param string $directory
     * @return bool|null
     */
    public function helper(string $name, string $directory = SDF_APP_HELP): bool|object
    {
        if (!isset(self::$isLoaded[strtolower($name)]) or !isset(self::$isLoaded[strtolower($name) . '.php'])) {
            if (!str_ends_with($name, '.php')) {
                $name .= '.php';
            }
            if (file_exists($directory . $name)) {
                if (!$this->isLoaded($name)) {
                    $this->load($name);
                    return require $directory . $name;
                }
            }
        }
        return false;
    }

    /**
     * Load Model
     * @param string $name
     * @param string $directory
     * @return bool|object
     */
    public function model(string $name, string $directory = SDF_APP_MODL): bool|object
    {
        if (!isset(self::$isLoaded[strtolower($name)]) or !isset(self::$isLoaded[strtolower($name) . '.php'])) {
            if (!str_ends_with($name, '.php')) {
                $name .= '.php';
            }
            if (file_exists($directory . $name)) {
                if (!$this->isLoaded($name)) {
                    $this->load($name);
                    require $directory . $name;
                    $name = strtolower(str_replace('.php', '', $name));
                    $model = ucfirst($name);
                    return $this->$name = new $model;
                }
            }
        }
        return false;
    }

    /**
     * Load Library
     * @param string $name
     * @param array|object $params
     * @param string $directory
     * @return bool|object
     */
    public function library(string $name, array|object $params = [], string $directory = SDF_APP_LIB): bool|object
    {
        if (!isset(self::$isLoaded[strtolower($name)]) or !isset(self::$isLoaded[strtolower($name) . '.php'])) {
            if (!str_ends_with($name, '.php')) {
                $name .= '.php';
            }
            if (file_exists($directory . $name)) {
                if (!$this->isLoaded($name)) {
                    $this->load($name);
                    if (!empty($params) and !is_array($params)) {
                        $params = get_object_vars($params);
                    }
                    require $directory . $name;
                    $name = ucfirst(strtolower(str_replace('.php', '', $name)));
                    $object = strtolower($name);
                    return $this->$object = new $name(...$params);
                }
            }
        }
        return false;
    }

    /**
     * Load File
     * @param string $name
     * @param string $directory
     * @return false|mixed
     */
    public function file(string $name, string $directory = SDF_DIR): mixed
    {
        if (!isset(self::$isLoaded[strtolower($name)]) or !isset(self::$isLoaded[strtolower($name) . '.php'])) {
            if (!str_ends_with($name, '.php')) {
                $name .= '.php';
            }
            if (file_exists($directory . $name)) {
                if (!$this->isLoaded($name)) {
                    $this->load($name);
                    return require $directory . $name;
                }
            }
        }
        return false;
    }

    /**
     * Load Config File
     * @param string $file
     * @param string $directory
     * @return bool|array
     */
    public function config(string $file, string $directory = SDF_APP_CONF): bool|array
    {
        if (file_exists(SDF_APP . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $file)) {
            require SDF_APP . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $file;
            if (isset($config)) {
                return $config;
            }
            return false;
        }
        return false;
    }

    /**
     * A function that return's the file is loaded or not.
     * @param string $name
     * @return bool
     */
    public function isLoaded(string $name): bool
    {
        return array_key_exists($name, self::$isLoaded);
    }

    private function load(string $name)
    {
        self::$isLoaded[strtolower($name)] = $name;
    }

    /**
     * @return array
     */
    public static function getIsLoaded(): array
    {
        return self::$isLoaded;
    }
}