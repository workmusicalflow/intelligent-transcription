<?php

namespace Application\Event\Handler;

use Domain\Common\Event\DomainEvent;

/**
 * Classe de base abstraite pour les Event Handlers
 */
abstract class AbstractEventHandler implements EventHandlerInterface
{
    protected int $priority = 0;
    protected bool $async = false;
    
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    public function isAsync(): bool
    {
        return $this->async;
    }
    
    public function canHandle(DomainEvent $event): bool
    {
        return in_array(get_class($event), $this->getHandledEventTypes());
    }
    
    /**
     * Retourne les types d'événements que ce handler peut gérer
     */
    abstract public function getHandledEventTypes(): array;
}