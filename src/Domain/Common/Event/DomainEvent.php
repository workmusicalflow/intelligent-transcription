<?php

namespace Domain\Common\Event;

use DateTimeImmutable;

abstract class DomainEvent
{
    private DateTimeImmutable $occurredAt;
    private string $aggregateId;
    private string $eventId;
    
    public function __construct(string $aggregateId)
    {
        $this->aggregateId = $aggregateId;
        $this->occurredAt = new DateTimeImmutable();
        $this->eventId = uniqid('event_', true);
    }
    
    public function aggregateId(): string
    {
        return $this->aggregateId;
    }
    
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
    
    public function eventId(): string
    {
        return $this->eventId;
    }
    
    abstract public function eventName(): string;
    
    abstract public function payload(): array;
    
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->eventName(),
            'aggregate_id' => $this->aggregateId,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s.u'),
            'payload' => $this->payload()
        ];
    }
}