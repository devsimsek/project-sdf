<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;
use SDF\Events\EventDispatcher;
use SDF\Events\ListenerProvider;

class TestEvent
{
    public bool $handled = false;
    public array $calls = [];
}

class StoppableTestEvent implements StoppableEventInterface
{
    public bool $stop = false;
    public bool $handled = false;

    public function isPropagationStopped(): bool
    {
        return $this->stop;
    }
}

class ChildTestEvent extends TestEvent
{
}

class EventTest extends TestCase
{
    public function test_dispatch_calls_listeners(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new TestEvent();
        $provider->addListener(TestEvent::class, function ($e) {
            $e->handled = true;
        });

        $dispatcher->dispatch($event);
        $this->assertTrue($event->handled);
    }

    public function test_multiple_listeners(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new TestEvent();
        $provider->addListener(TestEvent::class, function ($e) {
            $e->calls[] = 'first';
        });
        $provider->addListener(TestEvent::class, function ($e) {
            $e->calls[] = 'second';
        });

        $dispatcher->dispatch($event);
        $this->assertSame(['first', 'second'], $event->calls);
    }

    public function test_listener_priority(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new TestEvent();
        $provider->addListener(TestEvent::class, function ($e) {
            $e->calls[] = 'low';
        }, 10);
        $provider->addListener(TestEvent::class, function ($e) {
            $e->calls[] = 'high';
        }, 0);

        $dispatcher->dispatch($event);
        $this->assertSame(['high', 'low'], $event->calls);
    }

    public function test_stoppable_event(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new StoppableTestEvent();
        $provider->addListener(StoppableTestEvent::class, function ($e) {
            $e->handled = true;
            $e->stop = true;
        });
        $provider->addListener(StoppableTestEvent::class, function ($e) {
            $e->calls[] = 'should_not_run';
        });

        $dispatcher->dispatch($event);
        $this->assertTrue($event->handled);
        $this->assertEmpty($event->calls ?? []);
    }

    public function test_dispatch_returns_event(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new TestEvent();
        $result = $dispatcher->dispatch($event);
        $this->assertSame($event, $result);
    }

    public function test_subclass_matching(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new ChildTestEvent();
        $provider->addListener(TestEvent::class, function ($e) {
            $e->handled = true;
        });

        $dispatcher->dispatch($event);
        $this->assertTrue($event->handled);
    }

    public function test_no_listeners_does_not_throw(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new TestEvent();
        $result = $dispatcher->dispatch($event);
        $this->assertSame($event, $result);
    }

    public function test_default_provider_created(): void
    {
        $dispatcher = new EventDispatcher();
        $ref = new \ReflectionProperty($dispatcher, 'provider');
        $ref->setAccessible(true);
        $provider = $ref->getValue($dispatcher);
        $this->assertInstanceOf(ListenerProvider::class, $provider);
    }

    public function test_pre_stopped_event_not_dispatched(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new StoppableTestEvent();
        $event->stop = true;
        $provider->addListener(StoppableTestEvent::class, function ($e) {
            $e->handled = true;
        });

        $dispatcher->dispatch($event);
        $this->assertFalse($event->handled);
    }

    public function test_add_listener_after_dispatch(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $provider->addListener(TestEvent::class, function ($e) {
            $e->calls[] = 'first';
        });

        $event = new TestEvent();
        $dispatcher->dispatch($event);
        $this->assertSame(['first'], $event->calls);
    }
}
