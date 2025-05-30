<?php

namespace Infrastructure\Event;

use Domain\Common\Event\DomainEvent;
use Application\Event\EventDispatcherInterface;
use Application\Event\Handler\EventHandlerInterface;

/**
 * Implémentation simple d'un dispatcher d'événements
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, EventHandlerInterface[]>
     */
    private array $handlers = [];
    
    /**
     * Enregistre un handler pour un type d'événement
     */
    public function subscribe(string $eventName, EventHandlerInterface $handler): void
    {
        if (!isset($this->handlers[$eventName])) {
            $this->handlers[$eventName] = [];
        }
        
        $this->handlers[$eventName][] = $handler;
    }
    
    /**
     * Dispatch un événement à tous ses handlers
     */
    public function dispatch(DomainEvent $event): void
    {
        $eventName = $event->eventName();
        
        if (!isset($this->handlers[$eventName])) {
            return; // Aucun handler pour cet événement
        }
        
        foreach ($this->handlers[$eventName] as $handler) {
            try {
                $handler->handle($event);
            } catch (\Exception $e) {
                // Log l'erreur mais continue avec les autres handlers
                error_log(sprintf(
                    "Error handling event %s: %s",
                    $eventName,
                    $e->getMessage()
                ));
            }
        }
    }
    
    /**
     * Dispatch plusieurs événements
     * 
     * @param DomainEvent[] $events
     */
    public function dispatchMultiple(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
    
    /**
     * Retourne la liste des handlers pour un événement
     */
    public function getHandlers(string $eventName): array
    {
        return $this->handlers[$eventName] ?? [];
    }
    
    /**
     * Réinitialise tous les handlers
     */
    public function clear(): void
    {
        $this->handlers = [];
    }
}