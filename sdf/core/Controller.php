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
 * @url         https://github.com/devsimsek/project-sdf/wiki/core.md#controller
 * @since       Version 1.0
 * @filesource
 */
class Controller
{
    use CoreUtilities;


    /**
     * Loader class instance
     * @var Loader
     */
    public Loader $load;

    /**
     * Fuse, A brand new View Engine
     * @var Fuse
     */
    private Fuse $fuse;

    /**
     * Request class instance
     * @var Request
     */
    public Request $request;

    /**
     * Response class instance
     * @var Response
     */
    public Response $response;

    public function __construct()
    {
        // To access loaded models,
        // libraries and so.
        $this->load = &self::coreLoadClass("Loader");
        $this->fuse = &self::coreLoadClass("Fuse");
        $this->request = &self::coreLoadClass("Request");
        $this->response = &self::coreLoadClass("Response");
    }

    /**
     * Get the Fuse instance.
     */
    public function getFuse(): Fuse
    {
        return $this->fuse;
    }

    /**
     * Returns Application's configuration.
     * @param string|null $key
     * @return false|mixed
     */
    public function getConfig(?string $key = null): mixed
    {
        return self::coreGetConfig("app", $key);
    }

    /**
     * Loads custom configuration
     * @param string $config
     * @param string|null $key
     * @return array
     */
    public function loadConfig(string $config, ?string $key = null): array
    {
        return self::coreGetConfig($config, $key);
    }
}
