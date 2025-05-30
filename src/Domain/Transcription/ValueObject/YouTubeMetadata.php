<?php

namespace Domain\Transcription\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;

final class YouTubeMetadata extends ValueObject
{
    private string $videoId;
    private string $originalUrl;
    private ?string $title;
    private ?int $duration;
    private ?string $description;
    private ?string $channelName;
    
    public function __construct(
        string $videoId,
        string $originalUrl,
        ?string $title = null,
        ?int $duration = null,
        ?string $description = null,
        ?string $channelName = null
    ) {
        $this->validateVideoId($videoId);
        $this->validateUrl($originalUrl);
        
        $this->videoId = $videoId;
        $this->originalUrl = $originalUrl;
        $this->title = $title;
        $this->duration = $duration;
        $this->description = $description;
        $this->channelName = $channelName;
    }
    
    public static function fromUrl(string $url): self
    {
        $videoId = self::extractVideoId($url);
        return new self($videoId, $url);
    }
    
    public static function create(
        string $videoId,
        string $originalUrl,
        ?string $title = null,
        ?int $duration = null,
        ?string $description = null,
        ?string $channelName = null
    ): self {
        return new self($videoId, $originalUrl, $title, $duration, $description, $channelName);
    }
    
    private function validateVideoId(string $videoId): void
    {
        // YouTube video ID is 11 characters long
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
            throw InvalidArgumentException::forInvalidValue(
                'YouTube video ID',
                $videoId,
                '11-character alphanumeric string'
            );
        }
    }
    
    private function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw InvalidArgumentException::forInvalidValue('YouTube URL', $url, 'valid URL');
        }
        
        if (!$this->isYouTubeUrl($url)) {
            throw InvalidArgumentException::forInvalidValue(
                'URL',
                $url,
                'YouTube URL (youtube.com or youtu.be)'
            );
        }
    }
    
    private function isYouTubeUrl(string $url): bool
    {
        return strpos($url, 'youtube.com') !== false || 
               strpos($url, 'youtu.be') !== false;
    }
    
    private static function extractVideoId(string $url): string
    {
        // Support different YouTube URL formats
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        throw InvalidArgumentException::forInvalidValue(
            'YouTube URL',
            $url,
            'valid YouTube URL with video ID'
        );
    }
    
    public function videoId(): string
    {
        return $this->videoId;
    }
    
    public function originalUrl(): string
    {
        return $this->originalUrl;
    }
    
    public function title(): ?string
    {
        return $this->title;
    }
    
    public function duration(): ?int
    {
        return $this->duration;
    }
    
    public function durationInMinutes(): ?float
    {
        return $this->duration ? round($this->duration / 60, 2) : null;
    }
    
    public function description(): ?string
    {
        return $this->description;
    }
    
    public function channelName(): ?string
    {
        return $this->channelName;
    }
    
    public function thumbnailUrl(string $quality = 'hqdefault'): string
    {
        // quality options: default, mqdefault, hqdefault, sddefault, maxresdefault
        return "https://img.youtube.com/vi/{$this->videoId}/{$quality}.jpg";
    }
    
    public function watchUrl(): string
    {
        return "https://www.youtube.com/watch?v={$this->videoId}";
    }
    
    public function embedUrl(): string
    {
        return "https://www.youtube.com/embed/{$this->videoId}";
    }
    
    public function isShort(): bool
    {
        return $this->duration !== null && $this->duration <= 60; // 60 seconds
    }
    
    public function withMetadata(
        ?string $title = null,
        ?int $duration = null,
        ?string $description = null,
        ?string $channelName = null
    ): self {
        return new self(
            $this->videoId,
            $this->originalUrl,
            $title ?? $this->title,
            $duration ?? $this->duration,
            $description ?? $this->description,
            $channelName ?? $this->channelName
        );
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }
    
    public function toArray(): array
    {
        return [
            'video_id' => $this->videoId,
            'original_url' => $this->originalUrl,
            'title' => $this->title,
            'duration' => $this->duration,
            'duration_minutes' => $this->durationInMinutes(),
            'description' => $this->description,
            'channel_name' => $this->channelName,
            'thumbnail_url' => $this->thumbnailUrl(),
            'watch_url' => $this->watchUrl(),
            'embed_url' => $this->embedUrl(),
            'is_short' => $this->isShort()
        ];
    }
    
    public function __toString(): string
    {
        return $this->originalUrl;
    }
}