<?php

namespace Domain\Analytics\Event;

use Domain\Common\Event\DomainEvent;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Analytics\ValueObject\SummaryStyle;

/**
 * Événement déclenché quand un résumé est généré
 */
final class SummaryGenerated implements DomainEvent
{
    private string $eventId;
    private \DateTimeImmutable $occurredAt;
    
    public function __construct(
        private readonly TranscriptionId $transcriptionId,
        private readonly string $summary,
        private readonly SummaryStyle $style
    ) {
        $this->eventId = uniqid('event_', true);
        $this->occurredAt = new \DateTimeImmutable();
    }
    
    public function transcriptionId(): TranscriptionId
    {
        return $this->transcriptionId;
    }
    
    public function summary(): string
    {
        return $this->summary;
    }
    
    public function style(): SummaryStyle
    {
        return $this->style;
    }
    
    public function eventId(): string
    {
        return $this->eventId;
    }
    
    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
    
    public function toArray(): array
    {
        return [
            'transcription_id' => $this->transcriptionId->value(),
            'summary' => $this->summary,
            'style' => $this->style->value(),
            'event_id' => $this->eventId(),
            'occurred_at' => $this->occurredAt()->format('c')
        ];
    }
}