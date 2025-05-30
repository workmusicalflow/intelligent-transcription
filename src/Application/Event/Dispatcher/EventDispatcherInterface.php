<?php

namespace Application\Event\Dispatcher;

use Domain\Common\Event\DomainEvent;

/**
 * Interface pour dispatcher les Domain Events
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch un événement vers tous les handlers enregistrés
     */
    public function dispatch(DomainEvent $event): void;
    
    /**
     * Dispatch plusieurs événements
     */
    public function dispatchAll(array $events): void;
    
    /**
     * Enregistre un handler pour un type d'événement
     */
    public function subscribe(string $eventClass, callable $handler): void;
    
    /**
     * Retire un handler pour un type d'événement
     */
    public function unsubscribe(string $eventClass, callable $handler): void;
    
    /**
     * Obtient tous les handlers enregistrés pour un type d'événement
     */
    public function getHandlers(string $eventClass): array;
    
    /**
     * Vérifie si des handlers sont enregistrés pour un type d'événement
     */
    public function hasHandlers(string $eventClass): bool;
}