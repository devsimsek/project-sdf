<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SDF\Flash;
use SDF\Session;

class FlashTest extends TestCase
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

    public function test_flash_set_and_get_across_requests(): void
    {
        // First request: set flash
        $flash = new Flash();
        $flash->set('success', 'Operation completed');

        // Simulate next request: new Flash instance ages data
        $flash2 = new Flash();
        $this->assertSame('Operation completed', $flash2->get('success'));
    }

    public function test_flash_get_consumes_message(): void
    {
        $flash = new Flash();
        $flash->set('key', 'value');

        $flash2 = new Flash();
        $this->assertSame('value', $flash2->get('key'));
        $this->assertNull($flash2->get('key'));
    }

    public function test_flash_get_returns_default_on_miss(): void
    {
        $flash = new Flash();
        $this->assertNull($flash->get('missing'));
        $this->assertSame('fallback', $flash->get('missing', 'fallback'));
    }

    public function test_flash_has_without_consuming(): void
    {
        $flash = new Flash();
        $flash->set('key', 'value');

        $flash2 = new Flash();
        $this->assertTrue($flash2->has('key'));
        $this->assertTrue($flash2->has('key')); // has does not consume
        $this->assertSame('value', $flash2->get('key'));
    }

    public function test_flash_all_returns_all_messages(): void
    {
        $flash = new Flash();
        $flash->set('a', 1);
        $flash->set('b', 2);

        $flash2 = new Flash();
        $this->assertSame(['a' => 1, 'b' => 2], $flash2->all());
    }

    public function test_flash_now_available_immediately(): void
    {
        $flash = new Flash();
        $flash->now('alert', 'Instant message');
        $this->assertSame('Instant message', $flash->get('alert'));
    }

    public function test_flash_now_does_not_persist(): void
    {
        $flash = new Flash();
        $flash->now('alert', 'Instant');

        $flash2 = new Flash();
        $this->assertNull($flash2->get('alert'));
    }

    public function test_flash_keep_preserves_message(): void
    {
        $flash = new Flash();
        $flash->set('key', 'value');

        $flash2 = new Flash();
        $flash2->keep('key'); // move back to new

        $flash3 = new Flash();
        $this->assertSame('value', $flash3->get('key'));
    }

    public function test_flash_set_is_fluent(): void
    {
        $flash = new Flash();
        $result = $flash->set('key', 'value');
        $this->assertSame($flash, $result);
    }

    public function test_flash_get_non_aged_message_returns_default(): void
    {
        // Message stored in _new should not be accessible via get()
        // (it hasn't been aged yet - only available next request)
        $flash = new Flash();
        $flash->set('key', 'value');
        $this->assertNull($flash->get('key'));
    }

    public function test_flash_accepts_session_instance(): void
    {
        $session = Session::getInstance();
        $flash = new Flash($session);
        $flash->set('key', 'value');

        $flash2 = new Flash($session);
        $this->assertSame('value', $flash2->get('key'));
    }

    private function resetSessionInstance(): void
    {
        $ref = new ReflectionClass(Session::class);
        $prop = $ref->getProperty('instance');
        $prop->setValue(null, null);
    }
}
