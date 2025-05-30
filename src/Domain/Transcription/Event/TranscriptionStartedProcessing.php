<?php

namespace Domain\Transcription\Event;

use Domain\Common\Event\DomainEvent;

final class TranscriptionStartedProcessing extends DomainEvent
{
    private string $processorType;
    private ?string $preprocessedPath;
    
    public function __construct(
        string $transcriptionId,
        string $processorType = 'whisper',
        ?string $preprocessedPath = null
    ) {
        parent::__construct($transcriptionId);
        $this->processorType = $processorType;
        $this->preprocessedPath = $preprocessedPath;
    }
    
    public function eventName(): string
    {
        return 'transcription.started_processing';
    }
    
    public function payload(): array
    {
        return [
            'processor_type' => $this->processorType,
            'preprocessed_path' => $this->preprocessedPath
        ];
    }
    
    public function processorType(): string
    {
        return $this->processorType;
    }
    
    public function preprocessedPath(): ?string
    {
        return $this->preprocessedPath;
    }
}