<?php

namespace Domain\Transcription\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;

final class TranscribedText extends ValueObject
{
    private string $content;
    private array $segments;
    
    public function __construct(string $content, array $segments = [])
    {
        $trimmedContent = trim($content);
        
        if (empty($trimmedContent)) {
            throw InvalidArgumentException::forEmptyValue('transcribed text');
        }
        
        $this->content = $trimmedContent;
        $this->segments = $this->validateSegments($segments);
    }
    
    private function validateSegments(array $segments): array
    {
        $validatedSegments = [];
        
        foreach ($segments as $segment) {
            if (!is_array($segment)) {
                continue;
            }
            
            // Validation basique des segments Whisper
            if (isset($segment['text']) && isset($segment['start']) && isset($segment['end'])) {
                $validatedSegments[] = [
                    'text' => (string) $segment['text'],
                    'start' => (float) $segment['start'],
                    'end' => (float) $segment['end'],
                    'tokens' => $segment['tokens'] ?? [],
                    'temperature' => $segment['temperature'] ?? null,
                    'avg_logprob' => $segment['avg_logprob'] ?? null,
                    'compression_ratio' => $segment['compression_ratio'] ?? null,
                    'no_speech_prob' => $segment['no_speech_prob'] ?? null
                ];
            }
        }
        
        return $validatedSegments;
    }
    
    public static function fromContent(string $content, array $segments = []): self
    {
        return new self($content, $segments);
    }
    
    public function content(): string
    {
        return $this->content;
    }
    
    public function segments(): array
    {
        return $this->segments;
    }
    
    public function wordCount(): int
    {
        return str_word_count($this->content);
    }
    
    public function characterCount(): int
    {
        return mb_strlen($this->content);
    }
    
    public function duration(): ?float
    {
        if (empty($this->segments)) {
            return null;
        }
        
        $lastSegment = end($this->segments);
        return $lastSegment['end'] ?? null;
    }
    
    public function excerpt(int $length = 100): string
    {
        if (mb_strlen($this->content) <= $length) {
            return $this->content;
        }
        
        return mb_substr($this->content, 0, $length) . '...';
    }
    
    public function hasSegments(): bool
    {
        return !empty($this->segments);
    }
    
    public function getSegmentAt(float $timestamp): ?array
    {
        foreach ($this->segments as $segment) {
            if ($timestamp >= $segment['start'] && $timestamp <= $segment['end']) {
                return $segment;
            }
        }
        
        return null;
    }
    
    public function getTextBetween(float $startTime, float $endTime): string
    {
        $text = '';
        
        foreach ($this->segments as $segment) {
            if ($segment['start'] >= $startTime && $segment['end'] <= $endTime) {
                $text .= $segment['text'] . ' ';
            }
        }
        
        return trim($text);
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }
    
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'segments' => $this->segments,
            'word_count' => $this->wordCount(),
            'character_count' => $this->characterCount(),
            'duration' => $this->duration()
        ];
    }
    
    public function __toString(): string
    {
        return $this->content;
    }
}