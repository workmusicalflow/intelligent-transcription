<?php

namespace Domain\Common\Event;

/**
 * Classe de base pour tous les événements du domaine
 */
abstract class BaseEvent implements DomainEvent
{
    private string $eventId;
    private string $aggregateId;
    private int $eventVersion;
    private \DateTimeImmutable $occurredAt;
    private array $metadata;
    
    public function __construct(
        string $aggregateId,
        int $eventVersion = 1,
        array $metadata = []
    ) {
        $this->aggregateId = $aggregateId;
        $this->eventVersion = $eventVersion;
        $this->occurredAt = new \DateTimeImmutable();
        $this->metadata = $metadata;
        $this->eventId = $this->generateEventId();
    }
    
    public function eventId(): string
    {
        return $this->eventId;
    }
    
    public function aggregateId(): string
    {
        return $this->aggregateId;
    }
    
    public function eventVersion(): int
    {
        return $this->eventVersion;
    }
    
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
    
    public function metadata(): array
    {
        return $this->metadata;
    }
    
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->eventName(),
            'aggregate_id' => $this->aggregateId,
            'event_version' => $this->eventVersion,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'payload' => $this->payload()
        ];
    }
    
    /**
     * Génère un ID unique pour l'événement
     */
    private function generateEventId(): string
    {
        return sprintf(
            '%s-%s-%s',
            $this->eventName(),
            $this->aggregateId,
            uniqid('', true)
        );
    }
    
    /**
     * Nom de l'événement (ex: "transcription.created")
     */
    abstract public function eventName(): string;
    
    /**
     * Données spécifiques de l'événement
     */
    abstract public function payload(): array;
}