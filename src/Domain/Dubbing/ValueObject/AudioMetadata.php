<?php

namespace Domain\Dubbing\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;

/**
 * Métadonnées audio enrichies pour optimisation de doublage
 * 
 * Cette classe capture toutes les informations nécessaires pour
 * optimiser la synchronisation et la qualité du doublage automatique
 */
final class AudioMetadata extends ValueObject
{
    private string $sourceLanguage;
    private string $targetLanguage;
    private float $duration;
    private float $averageSpeechRate;
    private string $contentType;
    private array $speakers;
    private array $technicalTerms;
    private float $noiseLevel;
    private array $emotionalTones;
    private array $pausePatterns;
    
    // Contraintes de synchronisation
    private bool $hasBackgroundMusic;
    private array $silenceRegions;
    private float $compressionRatio;

    private const VALID_CONTENT_TYPES = [
        'dialogue', 'narration', 'news', 'podcast', 
        'interview', 'lecture', 'presentation', 'conference'
    ];

    private const VALID_EMOTIONS = [
        'neutral', 'happy', 'sad', 'angry', 'excited', 
        'calm', 'concerned', 'joyful', 'serious', 'playful',
        'melancholy', 'enthusiastic', 'worried', 'confident'
    ];

    public function __construct(
        string $sourceLanguage,
        string $targetLanguage,
        float $duration,
        float $averageSpeechRate,
        string $contentType = 'dialogue',
        array $speakers = [],
        array $technicalTerms = [],
        float $noiseLevel = 0.0,
        array $emotionalTones = ['neutral'],
        array $pausePatterns = [],
        bool $hasBackgroundMusic = false,
        array $silenceRegions = [],
        float $compressionRatio = 1.0
    ) {
        $this->validateLanguage($sourceLanguage, 'source');
        $this->validateLanguage($targetLanguage, 'target');
        $this->validateDuration($duration);
        $this->validateSpeechRate($averageSpeechRate);
        $this->validateContentType($contentType);
        $this->validateNoiseLevel($noiseLevel);
        $this->validateEmotionalTones($emotionalTones);
        $this->validateCompressionRatio($compressionRatio);
        $this->validateSilenceRegions($silenceRegions);

        $this->sourceLanguage = $sourceLanguage;
        $this->targetLanguage = $targetLanguage;
        $this->duration = $duration;
        $this->averageSpeechRate = $averageSpeechRate;
        $this->contentType = $contentType;
        $this->speakers = array_map('trim', $speakers);
        $this->technicalTerms = array_map('trim', $technicalTerms);
        $this->noiseLevel = $noiseLevel;
        $this->emotionalTones = $emotionalTones;
        $this->pausePatterns = $pausePatterns;
        $this->hasBackgroundMusic = $hasBackgroundMusic;
        $this->silenceRegions = $silenceRegions;
        $this->compressionRatio = $compressionRatio;
    }

    public static function fromWhisperData(array $whisperData, string $targetLanguage): self
    {
        $sourceLanguage = $whisperData['language'] ?? 'en';
        $duration = $whisperData['duration'] ?? 0.0;
        $segments = $whisperData['segments'] ?? [];
        
        // Calcul du débit de parole
        $totalWords = 0;
        $emotionalTones = ['neutral'];
        $pausePatterns = [];
        
        foreach ($segments as $segment) {
            $totalWords += str_word_count($segment['text'] ?? '');
            
            // Analyse de confiance pour détecter la qualité
            if (isset($segment['avg_logprob']) && $segment['avg_logprob'] < -1.0) {
                $emotionalTones[] = 'uncertain';
            }
            
            // Détection de pauses
            if (isset($segment['no_speech_prob']) && $segment['no_speech_prob'] > 0.5) {
                $pausePatterns[] = [
                    'start' => $segment['start'] ?? 0,
                    'duration' => ($segment['end'] ?? 0) - ($segment['start'] ?? 0),
                    'type' => 'silence'
                ];
            }
        }
        
        $averageSpeechRate = $duration > 0 ? ($totalWords / ($duration / 60)) : 0;
        
        // Détection automatique du type de contenu
        $contentType = self::detectContentType($whisperData);
        
        // Détection de musique de fond (basée sur compression ratio)
        $hasBackgroundMusic = false;
        $compressionRatio = 1.0;
        if (!empty($segments)) {
            $ratios = array_filter(array_column($segments, 'compression_ratio'), function($r) {
                return $r !== null && $r > 0;
            });
            if (!empty($ratios)) {
                $avgCompressionRatio = array_sum($ratios) / count($ratios);
                $compressionRatio = max(0.1, $avgCompressionRatio); // Minimum 0.1
                $hasBackgroundMusic = $avgCompressionRatio > 2.0;
            }
        }

        return new self(
            sourceLanguage: $sourceLanguage,
            targetLanguage: $targetLanguage,
            duration: $duration,
            averageSpeechRate: $averageSpeechRate,
            contentType: $contentType,
            emotionalTones: array_unique($emotionalTones),
            pausePatterns: $pausePatterns,
            hasBackgroundMusic: $hasBackgroundMusic,
            compressionRatio: $compressionRatio
        );
    }

    public function sourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function targetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function duration(): float
    {
        return $this->duration;
    }

    public function averageSpeechRate(): float
    {
        return $this->averageSpeechRate;
    }

    public function contentType(): string
    {
        return $this->contentType;
    }

    public function speakers(): array
    {
        return $this->speakers;
    }

    public function technicalTerms(): array
    {
        return $this->technicalTerms;
    }

    public function noiseLevel(): float
    {
        return $this->noiseLevel;
    }

    public function emotionalTones(): array
    {
        return $this->emotionalTones;
    }

    public function pausePatterns(): array
    {
        return $this->pausePatterns;
    }

    public function hasBackgroundMusic(): bool
    {
        return $this->hasBackgroundMusic;
    }

    public function silenceRegions(): array
    {
        return $this->silenceRegions;
    }

    public function compressionRatio(): float
    {
        return $this->compressionRatio;
    }

    public function hasSpeakers(): bool
    {
        return !empty($this->speakers);
    }

    public function hasTechnicalTerms(): bool
    {
        return !empty($this->technicalTerms);
    }

    public function isHighQualitySource(): bool
    {
        return $this->noiseLevel < 0.2 && 
               $this->compressionRatio < 2.0 &&
               $this->averageSpeechRate > 100 &&
               $this->averageSpeechRate < 200;
    }

    public function isDubbingOptimal(): bool
    {
        return $this->isHighQualitySource() && 
               !$this->hasBackgroundMusic &&
               count($this->pausePatterns) > 0;
    }

    public function getSpeechRateCategory(): string
    {
        if ($this->averageSpeechRate < 120) {
            return 'slow';
        } elseif ($this->averageSpeechRate < 160) {
            return 'normal';
        } elseif ($this->averageSpeechRate < 200) {
            return 'fast';
        } else {
            return 'very_fast';
        }
    }

    public function getPrimaryEmotion(): string
    {
        return $this->emotionalTones[0] ?? 'neutral';
    }

    public function withSpeakers(array $speakers): self
    {
        return new self(
            $this->sourceLanguage,
            $this->targetLanguage,
            $this->duration,
            $this->averageSpeechRate,
            $this->contentType,
            $speakers,
            $this->technicalTerms,
            $this->noiseLevel,
            $this->emotionalTones,
            $this->pausePatterns,
            $this->hasBackgroundMusic,
            $this->silenceRegions,
            $this->compressionRatio
        );
    }

    public function withTechnicalTerms(array $terms): self
    {
        return new self(
            $this->sourceLanguage,
            $this->targetLanguage,
            $this->duration,
            $this->averageSpeechRate,
            $this->contentType,
            $this->speakers,
            $terms,
            $this->noiseLevel,
            $this->emotionalTones,
            $this->pausePatterns,
            $this->hasBackgroundMusic,
            $this->silenceRegions,
            $this->compressionRatio
        );
    }

    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }

    public function toArray(): array
    {
        return [
            'sourceLanguage' => $this->sourceLanguage,
            'targetLanguage' => $this->targetLanguage,
            'duration' => $this->duration,
            'averageSpeechRate' => $this->averageSpeechRate,
            'contentType' => $this->contentType,
            'speakers' => $this->speakers,
            'technicalTerms' => $this->technicalTerms,
            'noiseLevel' => $this->noiseLevel,
            'emotionalTones' => $this->emotionalTones,
            'pausePatterns' => $this->pausePatterns,
            'hasBackgroundMusic' => $this->hasBackgroundMusic,
            'silenceRegions' => $this->silenceRegions,
            'compressionRatio' => $this->compressionRatio,
            'analysis' => [
                'qualityScore' => $this->isHighQualitySource() ? 'high' : 'medium',
                'dubbingOptimal' => $this->isDubbingOptimal(),
                'speechRateCategory' => $this->getSpeechRateCategory(),
                'primaryEmotion' => $this->getPrimaryEmotion(),
                'speakerCount' => count($this->speakers),
                'technicalTermCount' => count($this->technicalTerms)
            ]
        ];
    }

    private static function detectContentType(array $whisperData): string
    {
        $text = $whisperData['text'] ?? '';
        $segments = $whisperData['segments'] ?? [];
        
        // Analyse basique du contenu
        if (preg_match('/\b(mesdames|messieurs|chers|audience|public)\b/i', $text)) {
            return 'presentation';
        }
        
        if (preg_match('/\b(interview|question|réponse|pourriez-vous)\b/i', $text)) {
            return 'interview';
        }
        
        if (preg_match('/\b(aujourd\'hui|actualité|nouvelles|information)\b/i', $text)) {
            return 'news';
        }
        
        // Si beaucoup de courtes phrases, probablement du dialogue
        $shortSegments = array_filter($segments, function($s) {
            return str_word_count($s['text'] ?? '') < 10;
        });
        
        if (count($shortSegments) > count($segments) * 0.6) {
            return 'dialogue';
        }
        
        return 'narration';
    }

    private function validateLanguage(string $language, string $type): void
    {
        if (empty(trim($language))) {
            throw InvalidArgumentException::forEmptyValue("{$type} language");
        }
        
        if (strlen($language) !== 2) {
            throw new InvalidArgumentException("Language code must be 2 characters long");
        }
    }

    private function validateDuration(float $duration): void
    {
        if ($duration <= 0) {
            throw InvalidArgumentException::forNegativeValue('duration');
        }
        
        if ($duration > 86400) { // 24 heures max
            throw new InvalidArgumentException("Duration cannot exceed 24 hours");
        }
    }

    private function validateSpeechRate(float $rate): void
    {
        if ($rate < 0) {
            throw InvalidArgumentException::forNegativeValue('speech rate');
        }
        
        if ($rate > 500) { // 500 mots/minute est irréaliste
            throw new InvalidArgumentException("Speech rate cannot exceed 500 words per minute");
        }
    }

    private function validateContentType(string $type): void
    {
        if (!in_array($type, self::VALID_CONTENT_TYPES, true)) {
            throw InvalidArgumentException::forInvalidChoice(
                'content type',
                $type,
                self::VALID_CONTENT_TYPES
            );
        }
    }

    private function validateNoiseLevel(float $level): void
    {
        if ($level < 0.0 || $level > 1.0) {
            throw InvalidArgumentException::forOutOfRange(
                'noise level',
                $level,
                0.0,
                1.0
            );
        }
    }

    private function validateEmotionalTones(array $tones): void
    {
        foreach ($tones as $tone) {
            if (!in_array($tone, self::VALID_EMOTIONS, true)) {
                throw InvalidArgumentException::forInvalidChoice(
                    'emotional tone',
                    $tone,
                    self::VALID_EMOTIONS
                );
            }
        }
    }

    private function validateCompressionRatio(float $ratio): void
    {
        if ($ratio <= 0) {
            throw InvalidArgumentException::forNegativeValue('compression ratio');
        }
    }

    private function validateSilenceRegions(array $regions): void
    {
        foreach ($regions as $region) {
            if (!is_array($region) || count($region) !== 2) {
                throw new InvalidArgumentException("Silence regions must be arrays of [start, end]");
            }
            
            [$start, $end] = $region;
            
            if (!is_numeric($start) || !is_numeric($end)) {
                throw new InvalidArgumentException("Silence region timestamps must be numeric");
            }
            
            if ($start >= $end) {
                throw new InvalidArgumentException("Silence region start must be before end");
            }
        }
    }

    public function __toString(): string
    {
        return sprintf(
            'AudioMetadata[%s->%s, %.1fs, %.0f wpm, %s]',
            $this->sourceLanguage,
            $this->targetLanguage,
            $this->duration,
            $this->averageSpeechRate,
            $this->contentType
        );
    }
}