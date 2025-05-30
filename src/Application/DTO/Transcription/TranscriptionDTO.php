<?php

namespace Application\DTO\Transcription;

use Application\DTO\AbstractDTO;

/**
 * DTO pour représenter une transcription complète
 */
final class TranscriptionDTO extends AbstractDTO
{
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $originalFilename,
        private readonly string $language,
        private readonly string $status,
        private readonly ?string $text = null,
        private readonly ?string $youtubeUrl = null,
        private readonly ?string $youtubeTitle = null,
        private readonly ?array $segments = null,
        private readonly ?float $duration = null,
        private readonly ?float $fileSize = null,
        private readonly ?string $price = null,
        private readonly ?string $failureReason = null,
        private readonly ?\DateTimeImmutable $createdAt = null,
        private readonly ?\DateTimeImmutable $completedAt = null
    ) {
        $this->validate();
    }
    
    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'] ?? throw new \InvalidArgumentException('Missing id'),
            userId: $data['user_id'] ?? throw new \InvalidArgumentException('Missing user_id'),
            originalFilename: $data['original_filename'] ?? throw new \InvalidArgumentException('Missing original_filename'),
            language: $data['language'] ?? throw new \InvalidArgumentException('Missing language'),
            status: $data['status'] ?? throw new \InvalidArgumentException('Missing status'),
            text: $data['text'] ?? null,
            youtubeUrl: $data['youtube_url'] ?? null,
            youtubeTitle: $data['youtube_title'] ?? null,
            segments: $data['segments'] ?? null,
            duration: isset($data['duration']) ? (float) $data['duration'] : null,
            fileSize: isset($data['file_size']) ? (float) $data['file_size'] : null,
            price: $data['price'] ?? null,
            failureReason: $data['failure_reason'] ?? null,
            createdAt: isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            completedAt: isset($data['completed_at']) ? new \DateTimeImmutable($data['completed_at']) : null
        );
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'original_filename' => $this->originalFilename,
            'language' => $this->language,
            'status' => $this->status,
            'text' => $this->text,
            'youtube_url' => $this->youtubeUrl,
            'youtube_title' => $this->youtubeTitle,
            'segments' => $this->segments,
            'duration' => $this->duration,
            'file_size' => $this->fileSize,
            'price' => $this->price,
            'failure_reason' => $this->failureReason,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'is_youtube' => !empty($this->youtubeUrl),
            'is_completed' => $this->status === 'completed',
            'has_failure' => !empty($this->failureReason)
        ];
    }
    
    public function validate(): void
    {
        parent::validate();
        
        $this->validateRequired('id', $this->id);
        $this->validateRequired('userId', $this->userId);
        $this->validateRequired('originalFilename', $this->originalFilename);
        $this->validateRequired('language', $this->language);
        $this->validateRequired('status', $this->status);
        
        // Validation du statut
        $validStatuses = ['pending', 'processing', 'completed', 'failed'];
        if (!in_array($this->status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$this->status}");
        }
        
        // Validation de la durée si présente
        if ($this->duration !== null && $this->duration < 0) {
            throw new \InvalidArgumentException('Duration must be positive');
        }
        
        // Validation de la taille de fichier si présente
        if ($this->fileSize !== null && $this->fileSize <= 0) {
            throw new \InvalidArgumentException('File size must be positive');
        }
    }
    
    // Getters pour accès aux propriétés
    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getOriginalFilename(): string { return $this->originalFilename; }
    public function getLanguage(): string { return $this->language; }
    public function getStatus(): string { return $this->status; }
    public function getText(): ?string { return $this->text; }
    public function getYoutubeUrl(): ?string { return $this->youtubeUrl; }
    public function getYoutubeTitle(): ?string { return $this->youtubeTitle; }
    public function getSegments(): ?array { return $this->segments; }
    public function getDuration(): ?float { return $this->duration; }
    public function getFileSize(): ?float { return $this->fileSize; }
    public function getPrice(): ?string { return $this->price; }
    public function getFailureReason(): ?string { return $this->failureReason; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    
    // Méthodes utilitaires
    public function isYoutubeSource(): bool { return !empty($this->youtubeUrl); }
    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isFailed(): bool { return $this->status === 'failed'; }
    public function isPending(): bool { return $this->status === 'pending'; }
    public function isProcessing(): bool { return $this->status === 'processing'; }
}