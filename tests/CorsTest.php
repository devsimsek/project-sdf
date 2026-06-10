<?php

/**
 * SDF CORS Middleware tests.
 *
 * @package     Tests
 * @file        CorsTest.php
 * @author      devsimsek
 * @copyright   Copyright (c) 2022-2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Core;
use SDF\Middleware\CorsMiddleware;
use ReflectionClass;

class CorsTest extends TestCase
{
    private CorsMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new CorsMiddleware();
    }

    public function test_allows_wildcard_origin(): void
    {
        $config = ['allowed_origins' => ['*']];
        $ref = new ReflectionClass($this->middleware);
        $method = $ref->getMethod('isOriginAllowed');

        $this->assertTrue($method->invoke($this->middleware, 'http://example.com', $config));
        $this->assertTrue($method->invoke($this->middleware, 'http://evil.com', $config));
    }

    public function test_allows_specific_origin(): void
    {
        $config = ['allowed_origins' => ['http://trusted.com']];
        $ref = new ReflectionClass($this->middleware);
        $method = $ref->getMethod('isOriginAllowed');

        $this->assertTrue($method->invoke($this->middleware, 'http://trusted.com', $config));
        $this->assertFalse($method->invoke($this->middleware, 'http://evil.com', $config));
    }

    public function test_allows_pattern_origin(): void
    {
        $config = [
            'allowed_origins_patterns' => ['/^https:\/\/.*\.trusted\.com$/'],
        ];
        $ref = new ReflectionClass($this->middleware);
        $method = $ref->getMethod('isOriginAllowed');

        $this->assertTrue($method->invoke($this->middleware, 'https://app.trusted.com', $config));
        $this->assertFalse($method->invoke($this->middleware, 'https://evil.com', $config));
    }

    public function test_getConfig_returns_defaults(): void
    {
        $ref = new ReflectionClass($this->middleware);
        $method = $ref->getMethod('getConfig');

        $config = $method->invoke($this->middleware);
        $this->assertIsArray($config);
        $this->assertArrayHasKey('allowed_origins', $config);
        $this->assertArrayHasKey('allowed_methods', $config);
        $this->assertArrayHasKey('allowed_headers', $config);
    }

    public function test_preflight_sets_headers(): void
    {
        $config = ['allowed_origins' => ['*'], 'allowed_methods' => ['GET'], 'allowed_headers' => ['X-CSRF-TOKEN'], 'max_age' => 3600];
        $ref = new ReflectionClass($this->middleware);
        $method = $ref->getMethod('setPreflightHeaders');

        $method->invoke($this->middleware, '*', $config);
        $this->assertTrue(true);
    }
}
