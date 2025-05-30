<?php

namespace Application\Query\Transcription;

use Application\Query\AbstractQuery;

/**
 * Query pour récupérer une transcription par son ID
 */
final class GetTranscriptionQuery extends AbstractQuery
{
    public function __construct(
        private readonly string $transcriptionId,
        private readonly ?string $userId = null,
        private readonly bool $includeSegments = true,
        private readonly bool $includeMetadata = true
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
    }
    
    protected function getParameters(): array
    {
        return [
            'transcription_id' => $this->transcriptionId,
            'user_id' => $this->userId,
            'include_segments' => $this->includeSegments,
            'include_metadata' => $this->includeMetadata
        ];
    }
    
    // Getters
    public function getTranscriptionId(): string { return $this->transcriptionId; }
    public function getUserId(): ?string { return $this->userId; }
    public function shouldIncludeSegments(): bool { return $this->includeSegments; }
    public function shouldIncludeMetadata(): bool { return $this->includeMetadata; }
}