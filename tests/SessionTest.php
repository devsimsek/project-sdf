<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SDF\Session;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $this->resetSessionInstance();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $this->resetSessionInstance();
    }

    public function test_session_set_and_get(): void
    {
        $session = new Session();
        $session->set('name', 'Alice');
        $this->assertSame('Alice', $session->get('name'));
    }

    public function test_session_get_returns_default_on_miss(): void
    {
        $session = new Session();
        $this->assertNull($session->get('missing'));
        $this->assertSame('fallback', $session->get('missing', 'fallback'));
    }

    public function test_session_has(): void
    {
        $session = new Session();
        $this->assertFalse($session->has('key'));
        $session->set('key', 'value');
        $this->assertTrue($session->has('key'));
    }

    public function test_session_remove(): void
    {
        $session = new Session();
        $session->set('key', 'value');
        $session->remove('key');
        $this->assertFalse($session->has('key'));
    }

    public function test_session_clear(): void
    {
        $session = new Session();
        $session->set('a', 1);
        $session->set('b', 2);
        $session->clear();
        $this->assertSame([], $_SESSION);
    }

    public function test_session_set_is_fluent(): void
    {
        $session = new Session();
        $result = $session->set('key', 'value');
        $this->assertSame($session, $result);
    }

    public function test_session_id(): void
    {
        $session = new Session();
        $id = $session->id();
        $this->assertIsString($id);
        $this->assertNotEmpty($id);
    }

    public function test_session_regenerate(): void
    {
        $session = new Session();
        $oldId = $session->id();
        $session->regenerate();
        $this->assertNotSame($oldId, $session->id());
    }

    public function test_session_destroy_clears_data(): void
    {
        $session = new Session();
        $session->set('key', 'value');
        $session->destroy();
        // After destroy, session is empty; start a new one to verify
        session_start();
        $this->assertArrayNotHasKey('key', $_SESSION);
    }

    public function test_session_getInstance_returns_singleton(): void
    {
        $a = Session::getInstance();
        $b = Session::getInstance();
        $this->assertSame($a, $b);
    }

    public function test_session_allows_null_false_and_zero_values(): void
    {
        $session = new Session();
        $session->set('null', null);
        $session->set('false', false);
        $session->set('zero', 0);

        $this->assertTrue($session->has('null'));
        $this->assertNull($session->get('null'));
        $this->assertFalse($session->get('false'));
        $this->assertSame(0, $session->get('zero'));
    }

    private function resetSessionInstance(): void
    {
        $ref = new ReflectionClass(Session::class);
        $prop = $ref->getProperty('instance');
        $prop->setValue(null, null);
    }
}
