<?php

namespace SDF;

/**
 * smskSoft SDF Controller
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF Core
 * @file        Controller.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2022, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/core#router
 * @since       Version 1.0
 * @filesource
 */
class Controller extends Core
{
    /**
     * Loader class instance
     * @var Loader
     */
    public object $load;
    /**
     * Fuse, A brand new View Engine
     * @var mixed|object
     */
    private Fuse $fuse;

    public function __construct()
    {
        // To access loaded models,
        // libraries and so.
        $this->load = &self::core_loadClass("Loader");
        $this->fuse = &self::core_loadClass("Fuse");
    }

    /**
     * Returns Application's configuration.
     * @param string|null $key
     * @return false|mixed
     */
    public function get_config(string $key = null): mixed
    {
        return self::core_getConfig("app", $key);
    }

    /**
     * Loads custom configuration
     * @param string $config
     * @param string|null $key
     * @return array
     */
    public function load_config(string $config, string $key = null): array
    {
        return self::core_getConfig($config, $key);
    }
}
