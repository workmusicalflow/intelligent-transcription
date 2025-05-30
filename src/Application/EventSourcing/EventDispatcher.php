<?php

namespace Application\EventSourcing;

use Domain\EventSourcing\EventDispatcherInterface;
use Domain\Common\Event\DomainEvent;

/**
 * Implémentation du dispatcher d'événements
 */
class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];
    
    public function subscribe(string $eventType, callable $listener): void
    {
        if (!isset($this->listeners[$eventType])) {
            $this->listeners[$eventType] = [];
        }
        
        $this->listeners[$eventType][] = $listener;
    }
    
    public function dispatch(DomainEvent $event): void
    {
        $eventType = get_class($event);
        
        // Dispatch aux listeners spécifiques
        if (isset($this->listeners[$eventType])) {
            foreach ($this->listeners[$eventType] as $listener) {
                call_user_func($listener, $event);
            }
        }
        
        // Dispatch aux listeners génériques (qui écoutent tous les événements)
        if (isset($this->listeners['*'])) {
            foreach ($this->listeners['*'] as $listener) {
                call_user_func($listener, $event);
            }
        }
    }
    
    public function dispatchMultiple(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}