<?php

/**
 * SDF Swagger/OpenAPI integration tests.
 *
 * @package     Tests
 * @file        SwaggerTest.php
 * @author      devsimsek
 * @copyright   Copyright (c) 2022-2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Swagger\SwaggerGenerator;

/**
 * Tests for the SDF Swagger/OpenAPI integration.
 *
 * @covers \SDF\Swagger\SwaggerGenerator
 * @covers \SDF\Swagger\SwaggerController
 */
class SwaggerTest extends TestCase
{
    /**
     * Generator output is valid JSON with openapi, info, servers, paths keys.
     */
    public function test_generator_returns_valid_json(): void
    {
        $generator = new SwaggerGenerator(
            title: 'Test API',
            apiVersion: '2.0.0',
            serverUrl: 'http://test.dev',
        );

        $json = $generator->generate();

        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertArrayHasKey('openapi', $data);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('servers', $data);
        $this->assertArrayHasKey('paths', $data);
    }

    /**
     * Custom title and version appear in the generated spec.
     */
    public function test_generator_sets_title_and_version(): void
    {
        $generator = new SwaggerGenerator(
            title: 'My Custom API',
            apiVersion: '3.1.0',
            serverUrl: 'http://example.com',
        );

        $data = $generator->generateArray();

        $this->assertSame('My Custom API', $data['info']['title']);
        $this->assertSame('3.1.0', $data['info']['version']);
    }

    /**
     * generateArray() returns an associative array with openapi version.
     */
    public function test_generator_array_output(): void
    {
        $generator = new SwaggerGenerator();
        $array = $generator->generateArray();

        $this->assertIsArray($array);
        $this->assertSame('3.0.0', $array['openapi']);
    }

    /**
     * Server URL passed to the constructor appears in the output.
     */
    public function test_generator_adds_server_url(): void
    {
        $generator = new SwaggerGenerator(
            serverUrl: 'https://api.example.com/v2',
        );

        $data = $generator->generateArray();

        $this->assertArrayHasKey('servers', $data);
        $this->assertSame('https://api.example.com/v2', $data['servers'][0]['url']);
    }

    /**
     * Default title/version are used when no arguments are given.
     */
    public function test_generator_uses_defaults_when_not_specified(): void
    {
        $generator = new SwaggerGenerator();
        $data = $generator->generateArray();

        $this->assertSame('SDF API', $data['info']['title']);
        $this->assertSame('1.0.0', $data['info']['version']);
    }

    /**
     * Paths key is always an array (empty by default).
     */
    public function test_generator_paths_is_array(): void
    {
        $generator = new SwaggerGenerator();
        $data = $generator->generateArray();

        $this->assertIsArray($data['paths']);
    }

    /**
     * CLI script contains the swagger:generate command handler.
     */
    public function test_cli_has_swagger_command(): void
    {
        $source = file_get_contents(SDF_DIR . '../sdf/cli');
        $this->assertStringContainsString('case "swagger":', $source);
        $this->assertStringContainsString('private function handleSwagger', $source);
        $this->assertStringContainsString('case "generate":', $source);
    }

    /**
     * Generated spec contains all required OpenAPI 3.x fields.
     */
    public function test_generated_json_is_valid_openapi(): void
    {
        $generator = new SwaggerGenerator();
        $data = $generator->generateArray();

        // OpenAPI 3.x required fields
        $this->assertArrayHasKey('openapi', $data);
        $this->assertMatchesRegularExpression('/^3\.\d+\.\d+$/', $data['openapi']);

        // Info object must have title + version
        $this->assertArrayHasKey('title', $data['info']);
        $this->assertArrayHasKey('version', $data['info']);
    }
}
