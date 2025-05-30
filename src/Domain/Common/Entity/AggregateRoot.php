<?php

namespace Domain\Common\Entity;

use Domain\Common\Event\DomainEvent;

abstract class AggregateRoot
{
    private array $domainEvents = [];
    
    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
    
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
    
    public function hasDomainEvents(): bool
    {
        return count($this->domainEvents) > 0;
    }
    
    abstract public function id(): string;
}