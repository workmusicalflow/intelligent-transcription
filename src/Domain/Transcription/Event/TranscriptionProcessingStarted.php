<?php

namespace Domain\Transcription\Event;

use DateTimeImmutable;
use Domain\Common\Event\DomainEvent;

final class TranscriptionProcessingStarted implements DomainEvent
{
    private string $transcriptionId;
    private string $processor;
    private ?string $preprocessedPath;
    private DateTimeImmutable $occurredAt;
    private string $eventId;
    
    public function __construct(
        string $transcriptionId,
        string $processor,
        ?string $preprocessedPath = null
    ) {
        $this->transcriptionId = $transcriptionId;
        $this->processor = $processor;
        $this->preprocessedPath = $preprocessedPath;
        $this->occurredAt = new DateTimeImmutable();
        $this->eventId = uniqid('event_', true);
    }
    
    public function transcriptionId(): string
    {
        return $this->transcriptionId;
    }
    
    public function processor(): string
    {
        return $this->processor;
    }
    
    public function preprocessedPath(): ?string
    {
        return $this->preprocessedPath;
    }
    
    public function eventId(): string
    {
        return $this->eventId;
    }
    
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
    
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'transcription_id' => $this->transcriptionId,
            'processor' => $this->processor,
            'preprocessed_path' => $this->preprocessedPath,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s')
        ];
    }
}