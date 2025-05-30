<?php

namespace Domain\Transcription\Service;

use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Transcription\ValueObject\Language;

/**
 * Résultat d'une transcription
 */
final class TranscriptionResult
{
    private TranscribedText $text;
    private Language $detectedLanguage;
    private array $metadata;
    private float $confidence;
    
    public function __construct(
        TranscribedText $text,
        Language $detectedLanguage,
        float $confidence = 1.0,
        array $metadata = []
    ) {
        $this->text = $text;
        $this->detectedLanguage = $detectedLanguage;
        $this->confidence = $this->validateConfidence($confidence);
        $this->metadata = $metadata;
    }
    
    private function validateConfidence(float $confidence): float
    {
        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new \InvalidArgumentException('Confidence must be between 0.0 and 1.0');
        }
        return $confidence;
    }
    
    public function text(): TranscribedText
    {
        return $this->text;
    }
    
    public function detectedLanguage(): Language
    {
        return $this->detectedLanguage;
    }
    
    public function confidence(): float
    {
        return $this->confidence;
    }
    
    public function metadata(): array
    {
        return $this->metadata;
    }
    
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
    
    public function hasHighConfidence(): bool
    {
        return $this->confidence >= 0.8;
    }
    
    public function toArray(): array
    {
        return [
            'text' => $this->text->toArray(),
            'detected_language' => $this->detectedLanguage->toArray(),
            'confidence' => $this->confidence,
            'metadata' => $this->metadata
        ];
    }
}