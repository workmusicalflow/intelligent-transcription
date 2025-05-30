<?php

namespace Domain\EventSourcing;

use Domain\Common\Event\DomainEvent;

/**
 * Interface pour le dispatcher d'événements
 */
interface EventDispatcherInterface
{
    /**
     * Enregistre un listener pour un type d'événement
     */
    public function subscribe(string $eventType, callable $listener): void;
    
    /**
     * Dispatch un événement à tous les listeners enregistrés
     */
    public function dispatch(DomainEvent $event): void;
    
    /**
     * Dispatch plusieurs événements
     */
    public function dispatchMultiple(array $events): void;
}