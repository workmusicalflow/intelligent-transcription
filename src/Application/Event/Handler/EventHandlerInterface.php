<?php

namespace Application\Event\Handler;

use Domain\Common\Event\DomainEvent;

/**
 * Interface pour tous les Event Handlers
 */
interface EventHandlerInterface
{
    /**
     * Gère un événement domain
     */
    public function handle(DomainEvent $event): void;
    
    /**
     * Indique si ce handler peut gérer l'événement donné
     */
    public function canHandle(DomainEvent $event): bool;
    
    /**
     * Retourne la priorité du handler (plus bas = plus prioritaire)
     */
    public function getPriority(): int;
    
    /**
     * Indique si ce handler doit être exécuté de manière asynchrone
     */
    public function isAsync(): bool;
}