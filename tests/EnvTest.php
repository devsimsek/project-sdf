<?php

/**
 * SDF Environment (.env) tests.
 *
 * @package     Tests
 * @file        EnvTest.php
 * @author      devsimsek
 * @copyright   Copyright (c) 2022-2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Env;

class EnvTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        Env::reset();
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'env_');
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpFile);
        Env::reset();
    }

    public function test_loads_simple_key_value(): void
    {
        file_put_contents($this->tmpFile, "FOO=bar");
        Env::load($this->tmpFile);
        $this->assertSame('bar', Env::get('FOO'));
    }

    public function test_loads_quoted_value(): void
    {
        file_put_contents($this->tmpFile, 'FOO="bar baz"');
        Env::load($this->tmpFile);
        $this->assertSame('bar baz', Env::get('FOO'));
    }

    public function test_loads_single_quoted_value(): void
    {
        file_put_contents($this->tmpFile, "FOO='bar baz'");
        Env::load($this->tmpFile);
        $this->assertSame('bar baz', Env::get('FOO'));
    }

    public function test_strips_inline_comment(): void
    {
        file_put_contents($this->tmpFile, 'FOO=bar # this is a comment');
        Env::load($this->tmpFile);
        $this->assertSame('bar', Env::get('FOO'));
    }

    public function test_skips_comment_lines(): void
    {
        file_put_contents($this->tmpFile, "# comment\nFOO=bar");
        Env::load($this->tmpFile);
        $this->assertSame('bar', Env::get('FOO'));
    }

    public function test_resolves_placeholder(): void
    {
        file_put_contents($this->tmpFile, "APP_NAME=MyApp\nFOO=hello-\${APP_NAME}");
        Env::load($this->tmpFile);
        $this->assertSame('hello-MyApp', Env::get('FOO'));
    }

    public function test_returns_default_on_missing(): void
    {
        $this->assertSame('default', Env::get('MISSING', 'default'));
    }

    public function test_set_puts_value(): void
    {
        Env::set('DYNAMIC', 'value');
        $this->assertSame('value', Env::get('DYNAMIC'));
    }

    public function test_has_returns_bool(): void
    {
        Env::set('EXISTS', 'yes');
        $this->assertTrue(Env::has('EXISTS'));
        $this->assertFalse(Env::has('NONEXIST'));
    }

    public function test_global_env_helper(): void
    {
        Env::set('HELPER_TEST', 'works');
        $this->assertSame('works', \env('HELPER_TEST'));
    }

    public function test_skips_missing_file(): void
    {
        Env::load('/tmp/__nonexistent_env_file__');
        $this->assertNull(Env::get('ANYTHING'));
    }
}
