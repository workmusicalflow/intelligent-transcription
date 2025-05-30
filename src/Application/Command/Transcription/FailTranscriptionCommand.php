<?php

namespace Application\Command\Transcription;

use Application\Command\AbstractCommand;

/**
 * Command pour marquer une transcription comme échouée
 */
final class FailTranscriptionCommand extends AbstractCommand
{
    public function __construct(
        private readonly string $transcriptionId,
        private readonly string $failureReason,
        private readonly ?string $errorCode = null,
        private readonly ?array $errorDetails = null
    ) {
        parent::__construct();
        $this->validate();
    }
    
    public function validate(): void
    {
        parent::validate();
        
        if (empty($this->transcriptionId)) {
            throw new \InvalidArgumentException('Transcription ID is required');
        }
        
        if (empty($this->failureReason)) {
            throw new \InvalidArgumentException('Failure reason is required');
        }
    }
    
    protected function getPayload(): array
    {
        return [
            'transcription_id' => $this->transcriptionId,
            'failure_reason' => $this->failureReason,
            'error_code' => $this->errorCode,
            'error_details' => $this->errorDetails
        ];
    }
    
    // Getters
    public function getTranscriptionId(): string { return $this->transcriptionId; }
    public function getFailureReason(): string { return $this->failureReason; }
    public function getErrorCode(): ?string { return $this->errorCode; }
    public function getErrorDetails(): ?array { return $this->errorDetails; }
    
    // Méthodes utilitaires
    public function hasErrorCode(): bool
    {
        return !empty($this->errorCode);
    }
    
    public function hasErrorDetails(): bool
    {
        return !empty($this->errorDetails);
    }
}