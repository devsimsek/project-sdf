<?php

declare(strict_types=1);

namespace SDF\Events;

use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    private array $listeners = [];

    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventClass][$priority][] = $listener;
        ksort($this->listeners[$eventClass]);
    }

    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = get_class($event);
        $matched = [];

        foreach ($this->listeners as $registeredClass => $priorities) {
            if ($registeredClass === $eventClass || is_subclass_of($event, $registeredClass)) {
                foreach ($priorities as $listeners) {
                    array_push($matched, ...$listeners);
                }
            }
        }

        return $matched;
    }
}
