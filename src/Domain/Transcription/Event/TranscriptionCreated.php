<?php

namespace Domain\Transcription\Event;

use Domain\Common\Event\BaseEvent;
use Domain\Common\ValueObject\UserId;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;

final class TranscriptionCreated extends BaseEvent
{
    private UserId $userId;
    private AudioFile $audioFile;
    private Language $language;
    private ?string $youtubeUrl;
    
    public function __construct(
        string $transcriptionId,
        UserId $userId,
        AudioFile $audioFile,
        Language $language,
        ?string $youtubeUrl = null,
        array $metadata = []
    ) {
        parent::__construct($transcriptionId, 1, $metadata);
        $this->userId = $userId;
        $this->audioFile = $audioFile;
        $this->language = $language;
        $this->youtubeUrl = $youtubeUrl;
    }
    
    public function eventName(): string
    {
        return 'transcription.created';
    }
    
    public function payload(): array
    {
        return [
            'user_id' => $this->userId->value(),
            'audio_file' => $this->audioFile->toArray(),
            'language' => $this->language->code(),
            'youtube_url' => $this->youtubeUrl,
            'source' => $this->youtubeUrl ? 'youtube' : 'file'
        ];
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
    
    public function youtubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }
    
    public function isYouTubeSource(): bool
    {
        return $this->youtubeUrl !== null;
    }
}