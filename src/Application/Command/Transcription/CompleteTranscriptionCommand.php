<?php

namespace Application\Command\Transcription;

use Application\Command\AbstractCommand;

/**
 * Command pour finaliser une transcription avec le résultat
 */
final class CompleteTranscriptionCommand extends AbstractCommand
{
    public function __construct(
        private readonly string $transcriptionId,
        private readonly string $transcribedText,
        private readonly ?array $segments = null,
        private readonly ?float $actualDuration = null,
        private readonly ?string $detectedLanguage = null,
        private readonly ?array $metadata = null
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
        
        if (empty($this->transcribedText)) {
            throw new \InvalidArgumentException('Transcribed text is required');
        }
        
        if ($this->actualDuration !== null && $this->actualDuration < 0) {
            throw new \InvalidArgumentException('Duration must be positive');
        }
        
        if ($this->segments !== null) {
            $this->validateSegments($this->segments);
        }
    }
    
    protected function getPayload(): array
    {
        return [
            'transcription_id' => $this->transcriptionId,
            'transcribed_text' => $this->transcribedText,
            'segments' => $this->segments,
            'actual_duration' => $this->actualDuration,
            'detected_language' => $this->detectedLanguage,
            'metadata' => $this->metadata,
            'word_count' => $this->getWordCount(),
            'character_count' => $this->getCharacterCount()
        ];
    }
    
    private function validateSegments(array $segments): void
    {
        foreach ($segments as $index => $segment) {
            if (!is_array($segment)) {
                throw new \InvalidArgumentException("Segment {$index} must be an array");
            }
            
            if (!isset($segment['text']) || empty($segment['text'])) {
                throw new \InvalidArgumentException("Segment {$index} must have text");
            }
            
            if (isset($segment['start']) && isset($segment['end'])) {
                if ($segment['start'] < 0 || $segment['end'] < 0) {
                    throw new \InvalidArgumentException("Segment {$index} timestamps must be positive");
                }
                
                if ($segment['start'] >= $segment['end']) {
                    throw new \InvalidArgumentException("Segment {$index} start must be before end");
                }
            }
        }
    }
    
    // Getters
    public function getTranscriptionId(): string { return $this->transcriptionId; }
    public function getTranscribedText(): string { return $this->transcribedText; }
    public function getSegments(): ?array { return $this->segments; }
    public function getActualDuration(): ?float { return $this->actualDuration; }
    public function getDetectedLanguage(): ?string { return $this->detectedLanguage; }
    public function getMetadata(): ?array { return $this->metadata; }
    
    // Méthodes utilitaires
    public function getWordCount(): int
    {
        return str_word_count($this->transcribedText);
    }
    
    public function getCharacterCount(): int
    {
        return mb_strlen($this->transcribedText);
    }
    
    public function hasSegments(): bool
    {
        return !empty($this->segments);
    }
    
    public function getSegmentCount(): int
    {
        return count($this->segments ?? []);
    }
    
    public function getTextExcerpt(int $maxLength = 100): string
    {
        if (mb_strlen($this->transcribedText) <= $maxLength) {
            return $this->transcribedText;
        }
        
        return mb_substr($this->transcribedText, 0, $maxLength) . '...';
    }
}