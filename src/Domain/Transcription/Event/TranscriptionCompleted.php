<?php

namespace Domain\Transcription\Event;

use Domain\Common\Event\BaseEvent;

final class TranscriptionCompleted extends BaseEvent
{
    private int $wordCount;
    private float $duration;
    private int $processingTimeSeconds;
    
    public function __construct(
        string $transcriptionId,
        int $wordCount,
        float $duration,
        int $processingTimeSeconds,
        array $metadata = []
    ) {
        parent::__construct($transcriptionId, 1, $metadata);
        $this->wordCount = $wordCount;
        $this->duration = $duration;
        $this->processingTimeSeconds = $processingTimeSeconds;
    }
    
    public function eventName(): string
    {
        return 'transcription.completed';
    }
    
    public function payload(): array
    {
        return [
            'word_count' => $this->wordCount,
            'duration' => $this->duration,
            'processing_time_seconds' => $this->processingTimeSeconds,
            'words_per_minute' => $this->duration > 0 ? round(($this->wordCount / $this->duration) * 60) : 0
        ];
    }
    
    public function wordCount(): int
    {
        return $this->wordCount;
    }
    
    public function duration(): float
    {
        return $this->duration;
    }
    
    public function processingTimeSeconds(): int
    {
        return $this->processingTimeSeconds;
    }
}