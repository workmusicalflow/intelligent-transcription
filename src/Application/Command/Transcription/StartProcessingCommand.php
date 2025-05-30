<?php

namespace Application\Command\Transcription;

use Application\Command\AbstractCommand;

/**
 * Command pour démarrer le traitement d'une transcription
 */
final class StartProcessingCommand extends AbstractCommand
{
    public function __construct(
        private readonly string $transcriptionId,
        private readonly ?string $processedFilePath = null,
        private readonly ?array $processingOptions = null
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
        
        if ($this->processedFilePath !== null && !file_exists($this->processedFilePath)) {
            throw new \InvalidArgumentException('Processed file does not exist: ' . $this->processedFilePath);
        }
    }
    
    protected function getPayload(): array
    {
        return [
            'transcription_id' => $this->transcriptionId,
            'processed_file_path' => $this->processedFilePath,
            'processing_options' => $this->processingOptions
        ];
    }
    
    // Getters
    public function getTranscriptionId(): string { return $this->transcriptionId; }
    public function getProcessedFilePath(): ?string { return $this->processedFilePath; }
    public function getProcessingOptions(): ?array { return $this->processingOptions; }
    
    // Méthodes utilitaires
    public function hasProcessedFile(): bool
    {
        return !empty($this->processedFilePath);
    }
    
    public function getProcessingOption(string $key, mixed $default = null): mixed
    {
        return $this->processingOptions[$key] ?? $default;
    }
}