<?php

namespace Domain\Transcription\Entity;

use DateTimeImmutable;
use Domain\Common\Entity\AggregateRoot;
use Domain\Common\ValueObject\UserId;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\ValueObject\YouTubeMetadata;
use Domain\Transcription\Event\TranscriptionCreated;
use Domain\Transcription\Event\TranscriptionStartedProcessing;
use Domain\Transcription\Event\TranscriptionCompleted;
use Domain\Transcription\Event\TranscriptionFailed;
use Domain\Transcription\Exception\InvalidTranscriptionStateException;

final class Transcription extends AggregateRoot
{
    private TranscriptionId $id;
    private UserId $userId;
    private AudioFile $audioFile;
    private Language $language;
    private TranscriptionStatus $status;
    private ?TranscribedText $text;
    private ?YouTubeMetadata $youtubeMetadata;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $startedAt;
    private ?DateTimeImmutable $completedAt;
    private ?string $failureReason;
    private array $metadata;
    
    private function __construct(
        TranscriptionId $id,
        UserId $userId,
        AudioFile $audioFile,
        Language $language,
        ?YouTubeMetadata $youtubeMetadata = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->audioFile = $audioFile;
        $this->language = $language;
        $this->status = TranscriptionStatus::PENDING();
        $this->text = null;
        $this->youtubeMetadata = $youtubeMetadata;
        $this->createdAt = new DateTimeImmutable();
        $this->startedAt = null;
        $this->completedAt = null;
        $this->failureReason = null;
        $this->metadata = [];
        
        $this->recordEvent(new TranscriptionCreated(
            $id->value(),
            $userId,
            $audioFile,
            $language,
            $youtubeMetadata?->originalUrl()
        ));
    }
    
    public static function createFromFile(
        AudioFile $audioFile,
        Language $language,
        UserId $userId
    ): self {
        return new self(
            TranscriptionId::generate(),
            $userId,
            $audioFile,
            $language
        );
    }
    
    public static function createFromYouTube(
        AudioFile $audioFile,
        YouTubeMetadata $youtubeMetadata,
        Language $language,
        UserId $userId
    ): self {
        return new self(
            TranscriptionId::generate(),
            $userId,
            $audioFile,
            $language,
            $youtubeMetadata
        );
    }
    
    public function startProcessing(?string $preprocessedPath = null): void
    {
        if (!$this->status->isPending()) {
            throw InvalidTranscriptionStateException::cannotStartProcessingNonPendingTranscription($this->status);
        }
        
        $this->status = TranscriptionStatus::PROCESSING();
        $this->startedAt = new DateTimeImmutable();
        
        if ($preprocessedPath) {
            $this->audioFile = $this->audioFile->withPreprocessedPath($preprocessedPath);
        }
        
        $this->recordEvent(new TranscriptionStartedProcessing(
            $this->id->value(),
            'whisper',
            $preprocessedPath
        ));
    }
    
    public function complete(TranscribedText $text, array $metadata = []): void
    {
        if (!$this->status->isProcessing()) {
            throw InvalidTranscriptionStateException::cannotCompleteNonProcessingTranscription($this->status);
        }
        
        $this->status = TranscriptionStatus::COMPLETED();
        $this->text = $text;
        $this->metadata = array_merge($this->metadata, $metadata);
        $this->completedAt = new DateTimeImmutable();
        
        $processingTime = $this->startedAt ? 
            $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp() : 0;
        
        $this->recordEvent(new TranscriptionCompleted(
            $this->id->value(),
            $text->wordCount(),
            $text->duration() ?? 0,
            $processingTime
        ));
    }
    
    public function fail(string $reason, string $errorCode = 'UNKNOWN_ERROR', ?array $context = null): void
    {
        if ($this->status->isCompleted()) {
            throw InvalidTranscriptionStateException::cannotFailCompletedTranscription();
        }
        
        $this->status = TranscriptionStatus::FAILED();
        $this->failureReason = $reason;
        $this->completedAt = new DateTimeImmutable();
        
        $this->recordEvent(new TranscriptionFailed(
            $this->id->value(),
            $reason,
            $errorCode,
            $context
        ));
    }
    
    public function cancel(): void
    {
        if ($this->status->isFinished()) {
            return; // Already finished, nothing to cancel
        }
        
        $this->status = TranscriptionStatus::CANCELLED();
        $this->completedAt = new DateTimeImmutable();
    }
    
    public function retry(): void
    {
        if (!$this->status->isFailed() && !$this->status->isCancelled()) {
            throw InvalidTranscriptionStateException::invalidStatusTransition(
                $this->status,
                TranscriptionStatus::PENDING()
            );
        }
        
        $this->status = TranscriptionStatus::PENDING();
        $this->text = null;
        $this->failureReason = null;
        $this->startedAt = null;
        $this->completedAt = null;
        $this->metadata = [];
    }
    
    // Getters
    public function id(): string
    {
        return $this->id->value();
    }
    
    public function transcriptionId(): TranscriptionId
    {
        return $this->id;
    }
    
    public function userId(): UserId
    {
        return $this->userId;
    }
    
    public function audioFile(): AudioFile
    {
        return $this->audioFile;
    }
    
    public function language(): Language
    {
        return $this->language;
    }
    
    public function status(): TranscriptionStatus
    {
        return $this->status;
    }
    
    public function text(): ?TranscribedText
    {
        return $this->text;
    }
    
    public function youtubeMetadata(): ?YouTubeMetadata
    {
        return $this->youtubeMetadata;
    }
    
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }
    
    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }
    
    public function failureReason(): ?string
    {
        return $this->failureReason;
    }
    
    public function metadata(): array
    {
        return $this->metadata;
    }
    
    public function isYouTubeSource(): bool
    {
        return $this->youtubeMetadata !== null;
    }
    
    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }
    
    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }
    
    public function isPending(): bool
    {
        return $this->status->isPending();
    }
    
    public function isProcessing(): bool
    {
        return $this->status->isProcessing();
    }
    
    public function processingDuration(): ?int
    {
        if (!$this->startedAt || !$this->completedAt) {
            return null;
        }
        
        return $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }
    
    public function getPreviewText(int $length = 100): string
    {
        if (!$this->text) {
            return '';
        }
        
        return $this->text->excerpt($length);
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'user_id' => $this->userId->value(),
            'audio_file' => $this->audioFile->toArray(),
            'language' => $this->language->toArray(),
            'status' => $this->status->toArray(),
            'text' => $this->text?->toArray(),
            'youtube_metadata' => $this->youtubeMetadata?->toArray(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'started_at' => $this->startedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'failure_reason' => $this->failureReason,
            'metadata' => $this->metadata,
            'is_youtube_source' => $this->isYouTubeSource(),
            'processing_duration' => $this->processingDuration()
        ];
    }
}