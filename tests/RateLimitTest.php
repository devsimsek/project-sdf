<?php

/**
 * SDF Rate-Limit Middleware tests.
 *
 * @package     Tests
 * @file        RateLimitTest.php
 * @author      devsimsek
 * @copyright   Copyright (c) 2022-2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Cache\Cache;
use SDF\Cache\FileDriver;
use SDF\Middleware\RateLimitMiddleware;
use ReflectionClass;

class RateLimitTest extends TestCase
{
    private RateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        Cache::setDriver(new FileDriver(['path' => sys_get_temp_dir() . '/sdf_cache_test/', 'prefix' => 'ratelimit_test_']));
        Cache::clear();
        $this->middleware = new RateLimitMiddleware(maxAttempts: 5, decaySeconds: 60);
    }

    protected function tearDown(): void
    {
        Cache::clear();
    }

    public function test_getAttempts_returns_zero_initially(): void
    {
        $ref = new ReflectionClass($this->middleware);
        $method = $ref->getMethod('getAttempts');

        $this->assertSame(0, $method->invoke($this->middleware, 'test:127.0.0.1|/'));
    }

    public function test_hit_increments_count(): void
    {
        $ref = new ReflectionClass($this->middleware);
        $hit = $ref->getMethod('hit');
        $get = $ref->getMethod('getAttempts');

        $key = 'test:127.0.0.1|/test';
        $hit->invoke($this->middleware, $key);
        $this->assertSame(1, $get->invoke($this->middleware, $key));
    }

    public function test_multiple_hits_accumulate(): void
    {
        $ref = new ReflectionClass($this->middleware);
        $hit = $ref->getMethod('hit');
        $get = $ref->getMethod('getAttempts');

        $key = 'test:127.0.0.1|/accum';
        for ($i = 0; $i < 3; $i++) {
            $hit->invoke($this->middleware, $key);
        }
        $this->assertSame(3, $get->invoke($this->middleware, $key));
    }

    public function test_retryAfter_returns_decay_when_no_data(): void
    {
        $ref = new ReflectionClass($this->middleware);
        $method = $ref->getMethod('retryAfter');

        $this->assertSame(60, $method->invoke($this->middleware, 'nonexistent'));
    }

    public function test_resolveKey_returns_sha1(): void
    {
        $ref = new ReflectionClass($this->middleware);
        $method = $ref->getMethod('resolveKey');

        $key = $method->invoke($this->middleware, $this->createMock(\SDF\Request::class));
        $this->assertStringStartsWith('ratelimit:', $key);
    }
}
