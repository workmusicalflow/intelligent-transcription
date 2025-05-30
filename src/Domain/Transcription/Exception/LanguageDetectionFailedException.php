<?php

namespace Domain\Transcription\Exception;

class LanguageDetectionFailedException extends TranscriptionException
{
    public static function detectionFailed(string $reason): self
    {
        return new self(sprintf('Language detection failed: %s', $reason));
    }
    
    public static function noSpeechDetected(): self
    {
        return new self('No speech detected in audio file');
    }
    
    public static function ambiguousLanguage(array $detectedLanguages): self
    {
        return new self(
            sprintf('Ambiguous language detection. Possible languages: %s', implode(', ', $detectedLanguages))
        );
    }
}