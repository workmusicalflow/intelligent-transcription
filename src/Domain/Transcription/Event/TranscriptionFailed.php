<?php

namespace Domain\Transcription\Event;

use Domain\Common\Event\BaseEvent;

final class TranscriptionFailed extends BaseEvent
{
    private string $reason;
    private string $errorCode;
    private ?array $context;
    
    public function __construct(
        string $transcriptionId,
        string $reason,
        string $errorCode = 'UNKNOWN_ERROR',
        ?array $context = null,
        array $metadata = []
    ) {
        parent::__construct($transcriptionId, 1, $metadata);
        $this->reason = $reason;
        $this->errorCode = $errorCode;
        $this->context = $context;
    }
    
    public function eventName(): string
    {
        return 'transcription.failed';
    }
    
    public function payload(): array
    {
        return [
            'reason' => $this->reason,
            'error_code' => $this->errorCode,
            'context' => $this->context
        ];
    }
    
    public function reason(): string
    {
        return $this->reason;
    }
    
    public function errorCode(): string
    {
        return $this->errorCode;
    }
    
    public function context(): ?array
    {
        return $this->context;
    }
}