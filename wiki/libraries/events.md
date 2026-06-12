# Events

SDF provides a PSR-14 compliant event system (`SDF\Events`). It consists of an `EventDispatcher` and a `ListenerProvider` — plain PHP objects with no dependencies on the framework container.

## Creating an event class

An event is any plain object. Implement `Psr\EventDispatcher\StoppableEventInterface` if you want listeners to halt propagation.

```php
<?php
use Psr\EventDispatcher\StoppableEventInterface;

class UserSignedUp
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
    ) {}
}

class UserSignedUpStoppable implements StoppableEventInterface
{
    private bool $stopped = false;

    public function __construct(
        public readonly int $userId,
    ) {}

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }
}
```

## Registering listeners

Use `ListenerProvider::addListener()` to attach a callable to an event class. Accepts a priority — lower numbers run first (default `0`).

```php
<?php
use SDF\Events\ListenerProvider;

$provider = new ListenerProvider();

$provider->addListener(UserSignedUp::class, function (UserSignedUp $event): void {
    // send welcome email
}, priority: 10);

$provider->addListener(UserSignedUp::class, function (UserSignedUp $event): void {
    // log signup
}, priority: 0);  // runs first
```

## Dispatching events

Pass the event to `EventDispatcher::dispatch()`. The same event object is returned after all listeners have run.

```php
<?php
use SDF\Events\EventDispatcher;

$dispatcher = new EventDispatcher($provider);  // or new EventDispatcher() for default provider

$event = new UserSignedUp(42, 'user@example.com');
$dispatcher->dispatch($event);
```

If no `ListenerProvider` is provided to the constructor, a default empty one is created automatically. You can add listeners to it at any time:

```php
<?php
$dispatcher = new EventDispatcher();
// $dispatcher->provider is not publicly accessible —
// pass a shared provider instance if you need external access
```

## Priority system

Registered listeners are grouped and sorted by priority (`ksort`). Lower numeric values execute first.

| Priority | Order |
|----------|-------|
| -10      | 1st   |
| 0        | 2nd   |
| 10       | 3rd   |

## Subclass matching

`getListenersForEvent()` also returns listeners registered for parent classes / interfaces, so you can listen on a base event type and receive all subtypes.

## Complete example

```php
<?php
use SDF\Events\EventDispatcher;
use SDF\Events\ListenerProvider;

// Event
class OrderPlaced
{
    public function __construct(
        public readonly int $orderId,
        public readonly float $total,
    ) {}
}

// Listeners
$provider = new ListenerProvider();
$provider->addListener(OrderPlaced::class, function (OrderPlaced $event): void {
    echo "Order #{$event->orderId} received\n";
}, priority: 0);
$provider->addListener(OrderPlaced::class, function (OrderPlaced $event): void {
    echo "Charging \${$event->total}\n";
}, priority: 5);

// Dispatch
$dispatcher = new EventDispatcher($provider);
$dispatcher->dispatch(new OrderPlaced(1001, 49.99));

// Output:
// Order #1001 received
// Charging $49.99
```
