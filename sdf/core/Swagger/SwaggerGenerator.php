<?php

declare(strict_types=1);

namespace SDF\Swagger;

use OpenApi\Annotations\Info;
use OpenApi\Annotations\Server;
use OpenApi\Generator;

/**
 * smskSoft SDF Swagger Generator
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF\Swagger
 * @file        SwaggerGenerator.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/swagger
 * @since       Version 2.0
 * @filesource
 */
class SwaggerGenerator
{
    /** @var string|null Custom API title. */
    private ?string $title = null;

    /** @var string|null Custom API version. */
    private ?string $apiVersion = null;

    /** @var string|null Custom server URL. */
    private ?string $serverUrl = null;

    /** @var string|null Custom description. */
    private ?string $description = null;

    /** @var string|null OpenAPI spec version override. */
    private ?string $specVersion = null;

    /** @var array<int, string> Additional directories to scan. */
    private array $extraPaths = [];

    /**
     * @param string|null $title      API title (default: "SDF API").
     * @param string|null $apiVersion API version (default: "1.0.0").
     * @param string|null $serverUrl  Server URL (default: inferred from request or "http://localhost").
     * @param string|null $description API description.
     */
    public function __construct(
        ?string $title = null,
        ?string $apiVersion = null,
        ?string $serverUrl = null,
        ?string $description = null,
    ) {
        $this->title = $title;
        $this->apiVersion = $apiVersion;
        $this->serverUrl = $serverUrl;
        $this->description = $description;
    }

    /**
     * Add extra directories to scan beyond the default controllers dir.
     *
     * @param string ...$paths
     * @return $this
     */
    public function addPaths(string ...$paths): self
    {
        $this->extraPaths = array_merge($this->extraPaths, $paths);
        return $this;
    }

    /**
     * Set the OpenAPI spec version.
     *
     * @param string $version e.g. "3.0.0" or "3.1.0".
     * @return $this
     */
    public function setSpecVersion(string $version): self
    {
        $this->specVersion = $version;
        return $this;
    }

    /**
     * Generate the OpenAPI spec as a JSON string.
     *
     * Scans app/controllers/ (and any extra paths) for PHP 8 #[OA\...] attributes.
     *
     * @return string JSON-encoded OpenAPI spec.
     */
    public function generate(): string
    {
        $paths = [SDF_APP_CONT];
        if (!empty($this->extraPaths)) {
            $paths = array_merge($paths, $this->extraPaths);
        }

        $generator = new Generator();

        if ($this->specVersion !== null) {
            $generator->setVersion($this->specVersion);
        }

        $openapi = $generator->scan($paths);

        // Ensure openapi version
        if ($openapi->openapi === Generator::UNDEFINED) {
            $openapi->openapi = '3.0.0';
        }

        // Ensure Info annotation (may be UNDEFINED when no #[OA\Info] attribute exists)
        $info = $openapi->info;
        if (!is_object($info) || $info === Generator::UNDEFINED) {
            $info = new Info([]);
            $openapi->info = $info;
        }
        $info->title = $this->title ?? ($info->title !== Generator::UNDEFINED ? $info->title : 'SDF API');
        $info->version = $this->apiVersion ?? ($info->version !== Generator::UNDEFINED ? $info->version : '1.0.0');
        if ($this->description !== null) {
            $info->description = $this->description;
        }

        // Ensure Paths
        if (!is_array($openapi->paths) || $openapi->paths === Generator::UNDEFINED) {
            $openapi->paths = [];
        }

        // Set server URL
        $serverUrl = $this->serverUrl ?? $this->detectServerUrl();
        $openapi->servers = [
            new Server([
                'url' => $serverUrl,
                'description' => 'SDF API server',
            ]),
        ];

        return $openapi->toJson();
    }

    /**
     * Generate the spec and decode to an array.
     *
     * @return array
     */
    public function generateArray(): array
    {
        return json_decode($this->generate(), true);
    }

    /**
     * Detect the server URL from the current request.
     *
     * @return string
     */
    private function detectServerUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }
}
