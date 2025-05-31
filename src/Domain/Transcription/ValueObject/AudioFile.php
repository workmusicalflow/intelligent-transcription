<?php

namespace Domain\Transcription\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;

final class AudioFile extends ValueObject
{
    private const SUPPORTED_FORMATS = [
        'mp3', 'mp4', 'm4a', 'wav', 'flac', 'ogg', 'webm', 'aac'
    ];
    
    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25MB (limite Whisper)
    
    private string $path;
    private string $originalName;
    private string $mimeType;
    private int $size;
    private ?float $duration;
    private ?string $preprocessedPath;
    
    public function __construct(
        string $path,
        string $originalName,
        string $mimeType,
        int $size,
        ?float $duration = null,
        ?string $preprocessedPath = null
    ) {
        $this->validatePath($path);
        $this->validateFormat($originalName);
        $this->validateSize($size);
        
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->duration = $duration;
        $this->preprocessedPath = $preprocessedPath;
    }
    
    public static function fromPath(string $path): self
    {
        if (!file_exists($path)) {
            throw InvalidArgumentException::forInvalidValue('file path', $path, 'existing file');
        }
        
        $originalName = basename($path);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $size = filesize($path);
        
        return new self($path, $originalName, $mimeType, $size);
    }
    
    public static function create(
        string $path,
        string $originalName,
        string $mimeType,
        int $size,
        ?float $duration = null
    ): self {
        return new self($path, $originalName, $mimeType, $size, $duration);
    }
    
    public static function fromContent(string $content, string $originalName): self
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'audio_');
        file_put_contents($tempPath, $content);
        
        $size = strlen($content);
        $mimeType = 'application/octet-stream'; // Default, could be improved
        
        return new self($tempPath, $originalName, $mimeType, $size);
    }
    
    private function validatePath(string $path): void
    {
        if (empty(trim($path))) {
            throw InvalidArgumentException::forEmptyValue('file path');
        }
    }
    
    private function validateFormat(string $fileName): void
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            throw InvalidArgumentException::forInvalidValue(
                'file format',
                $extension,
                implode(', ', self::SUPPORTED_FORMATS)
            );
        }
    }
    
    private function validateSize(int $size): void
    {
        if ($size <= 0) {
            throw InvalidArgumentException::forInvalidValue('file size', $size, 'positive integer');
        }
        
        if ($size > self::MAX_FILE_SIZE) {
            throw InvalidArgumentException::forTooLongValue(
                'file size',
                self::MAX_FILE_SIZE
            );
        }
    }
    
    public function path(): string
    {
        return $this->path;
    }
    
    public function originalName(): string
    {
        return $this->originalName;
    }
    
    public function mimeType(): string
    {
        return $this->mimeType;
    }
    
    public function size(): int
    {
        return $this->size;
    }
    
    public function sizeInMB(): float
    {
        return round($this->size / (1024 * 1024), 2);
    }
    
    public function duration(): ?float
    {
        return $this->duration;
    }
    
    public function durationInMinutes(): ?float
    {
        return $this->duration ? round($this->duration / 60, 2) : null;
    }
    
    public function extension(): string
    {
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }
    
    public function isValid(): bool
    {
        return file_exists($this->path) && 
               in_array($this->extension(), self::SUPPORTED_FORMATS) &&
               $this->size <= self::MAX_FILE_SIZE;
    }
    
    public function needsPreprocessing(): bool
    {
        // Les formats qui pourraient nécessiter un preprocessing
        return in_array($this->extension(), ['mp4', 'webm', 'm4a']);
    }
    
    public function hasPreprocessedVersion(): bool
    {
        return $this->preprocessedPath !== null && file_exists($this->preprocessedPath);
    }
    
    public function preprocessedPath(): ?string
    {
        return $this->preprocessedPath;
    }
    
    public function withPreprocessedPath(string $preprocessedPath): self
    {
        return new self(
            $this->path,
            $this->originalName,
            $this->mimeType,
            $this->size,
            $this->duration,
            $preprocessedPath
        );
    }
    
    public function withDuration(float $duration): self
    {
        return new self(
            $this->path,
            $this->originalName,
            $this->mimeType,
            $this->size,
            $duration,
            $this->preprocessedPath
        );
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }
    
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'size_mb' => $this->sizeInMB(),
            'duration' => $this->duration,
            'duration_minutes' => $this->durationInMinutes(),
            'extension' => $this->extension(),
            'preprocessed_path' => $this->preprocessedPath,
            'needs_preprocessing' => $this->needsPreprocessing(),
            'is_valid' => $this->isValid()
        ];
    }
    
    public static function getSupportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }
    
    public static function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }
}