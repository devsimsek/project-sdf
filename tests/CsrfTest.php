<?php

/**
 * SDF CSRF Middleware tests.
 *
 * @package     Tests
 * @file        CsrfTest.php
 * @author      devsimsek
 * @copyright   Copyright (c) 2022-2026, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SDF\Middleware\CsrfMiddleware;
use SDF\Session;

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];

        $ref = new ReflectionClass(Session::class);
        $prop = $ref->getProperty('instance');
        $prop->setValue(null, null);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function test_generates_token(): void
    {
        $token = CsrfMiddleware::generateToken();
        $this->assertSame(64, strlen($token));
    }

    public function test_token_is_stored_in_session(): void
    {
        $token = CsrfMiddleware::generateToken();
        $this->assertSame($token, Session::getInstance()->get('_csrf_token'));
    }

    public function test_token_returns_same_within_session(): void
    {
        $a = CsrfMiddleware::token();
        $b = CsrfMiddleware::token();
        $this->assertSame($a, $b);
    }

    public function test_field_returns_hidden_input(): void
    {
        $token = CsrfMiddleware::token();
        $field = CsrfMiddleware::field();
        $this->assertStringContainsString('_token', $field);
        $this->assertStringContainsString($token, $field);
    }
}
