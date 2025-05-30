<?php

namespace Domain\Analytics\ValueObject;

use InvalidArgumentException;

/**
 * Value Object représentant un résumé
 */
final class Summary
{
    private string $content;
    private array $keyPoints;
    private \DateTimeImmutable $generatedAt;
    
    private function __construct(string $content, array $keyPoints)
    {
        if (empty(trim($content))) {
            throw new InvalidArgumentException('Summary content cannot be empty');
        }
        
        $this->content = $content;
        $this->keyPoints = $keyPoints;
        $this->generatedAt = new \DateTimeImmutable();
    }
    
    public static function create(string $content, array $keyPoints = []): self
    {
        return new self($content, $keyPoints);
    }
    
    public function content(): string
    {
        return $this->content;
    }
    
    public function keyPoints(): array
    {
        return $this->keyPoints;
    }
    
    public function generatedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }
    
    public function wordCount(): int
    {
        return str_word_count($this->content);
    }
    
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'key_points' => $this->keyPoints,
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
            'word_count' => $this->wordCount()
        ];
    }
}