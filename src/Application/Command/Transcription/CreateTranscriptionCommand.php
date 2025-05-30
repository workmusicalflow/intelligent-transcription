<?php

namespace Application\Command\Transcription;

use Application\Command\AbstractCommand;

/**
 * Command pour créer une nouvelle transcription
 */
final class CreateTranscriptionCommand extends AbstractCommand
{
    public function __construct(
        private readonly string $userId,
        private readonly string $originalFilename,
        private readonly string $filePath,
        private readonly string $mimeType,
        private readonly int $fileSize,
        private readonly string $language,
        private readonly bool $isPriority = false,
        private readonly ?float $duration = null,
        private readonly ?string $youtubeUrl = null,
        private readonly ?string $youtubeTitle = null,
        private readonly ?string $youtubeVideoId = null
    ) {
        parent::__construct();
        $this->validate();
    }
    
    public function validate(): void
    {
        parent::validate();
        
        if (empty($this->userId)) {
            throw new \InvalidArgumentException('User ID is required');
        }
        
        if (empty($this->originalFilename)) {
            throw new \InvalidArgumentException('Original filename is required');
        }
        
        if (empty($this->filePath)) {
            throw new \InvalidArgumentException('File path is required');
        }
        
        if (!file_exists($this->filePath)) {
            throw new \InvalidArgumentException('File does not exist: ' . $this->filePath);
        }
        
        if (empty($this->mimeType)) {
            throw new \InvalidArgumentException('MIME type is required');
        }
        
        if ($this->fileSize <= 0) {
            throw new \InvalidArgumentException('File size must be positive');
        }
        
        if (empty($this->language)) {
            throw new \InvalidArgumentException('Language is required');
        }
        
        if ($this->duration !== null && $this->duration < 0) {
            throw new \InvalidArgumentException('Duration must be positive');
        }
        
        // Validation spécifique YouTube
        if ($this->isYouTubeSource()) {
            if (empty($this->youtubeUrl)) {
                throw new \InvalidArgumentException('YouTube URL is required for YouTube source');
            }
            if (empty($this->youtubeVideoId)) {
                throw new \InvalidArgumentException('YouTube video ID is required for YouTube source');
            }
        }
    }
    
    protected function getPayload(): array
    {
        return [
            'user_id' => $this->userId,
            'original_filename' => $this->originalFilename,
            'file_path' => $this->filePath,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'language' => $this->language,
            'is_priority' => $this->isPriority,
            'duration' => $this->duration,
            'youtube_url' => $this->youtubeUrl,
            'youtube_title' => $this->youtubeTitle,
            'youtube_video_id' => $this->youtubeVideoId,
            'is_youtube_source' => $this->isYouTubeSource()
        ];
    }
    
    // Getters
    public function getUserId(): string { return $this->userId; }
    public function getOriginalFilename(): string { return $this->originalFilename; }
    public function getFilePath(): string { return $this->filePath; }
    public function getMimeType(): string { return $this->mimeType; }
    public function getFileSize(): int { return $this->fileSize; }
    public function getLanguage(): string { return $this->language; }
    public function isPriority(): bool { return $this->isPriority; }
    public function getDuration(): ?float { return $this->duration; }
    public function getYoutubeUrl(): ?string { return $this->youtubeUrl; }
    public function getYoutubeTitle(): ?string { return $this->youtubeTitle; }
    public function getYoutubeVideoId(): ?string { return $this->youtubeVideoId; }
    
    // Méthodes utilitaires
    public function isYouTubeSource(): bool
    {
        return !empty($this->youtubeUrl);
    }
    
    public function getFileSizeInMB(): float
    {
        return round($this->fileSize / (1024 * 1024), 2);
    }
}