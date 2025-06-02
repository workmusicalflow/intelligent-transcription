<?php

namespace Domain\Dubbing\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;
use Domain\Transcription\ValueObject\Language;

/**
 * Résultat de transcription enrichie pour doublage révolutionnaire
 * 
 * Cette classe combine transcription, métadonnées et données word-level
 * pour créer la base parfaite du pipeline de doublage automatique
 */
final class DubbingTranscription extends ValueObject
{
    private string $text;
    private array $segments;
    private array $words;
    private AudioMetadata $metadata;
    private float $confidence;
    private Language $detectedLanguage;
    private array $speakerSegments;

    public function __construct(
        string $text,
        array $segments,
        array $words,
        AudioMetadata $metadata,
        float $confidence,
        Language $detectedLanguage,
        array $speakerSegments = []
    ) {
        $this->validateText($text);
        $this->validateConfidence($confidence);
        $this->validateSegments($segments);
        $this->validateWords($words);
        $this->validateSpeakerSegments($speakerSegments);

        $this->text = trim($text);
        $this->segments = $segments;
        $this->words = $words;
        $this->metadata = $metadata;
        $this->confidence = $confidence;
        $this->detectedLanguage = $detectedLanguage;
        $this->speakerSegments = $speakerSegments;
    }

    public static function fromWhisperResponse(
        array $whisperData,
        AudioMetadata $metadata
    ): self {
        $text = $whisperData['text'] ?? '';
        $segments = $whisperData['segments'] ?? [];
        $words = $whisperData['words'] ?? [];
        
        // Calculer la confiance moyenne
        $confidence = self::calculateAverageConfidence($segments);
        
        // Détecter la langue
        $detectedLanguage = Language::fromCode($whisperData['language'] ?? 'en');
        
        // Grouper les segments par locuteur si possible
        $speakerSegments = self::groupSegmentsBySpeaker($segments);

        return new self(
            $text,
            $segments,
            $words,
            $metadata,
            $confidence,
            $detectedLanguage,
            $speakerSegments
        );
    }

    public function text(): string
    {
        return $this->text;
    }

    public function segments(): array
    {
        return $this->segments;
    }

    public function words(): array
    {
        return $this->words;
    }

    public function metadata(): AudioMetadata
    {
        return $this->metadata;
    }

    public function confidence(): float
    {
        return $this->confidence;
    }

    public function detectedLanguage(): Language
    {
        return $this->detectedLanguage;
    }

    public function speakerSegments(): array
    {
        return $this->speakerSegments;
    }

    public function hasWordTimestamps(): bool
    {
        return !empty($this->words);
    }

    public function hasMultipleSpeakers(): bool
    {
        return count($this->speakerSegments) > 1;
    }

    public function getWordCount(): int
    {
        return count($this->words) ?: str_word_count($this->text);
    }

    public function getDuration(): float
    {
        if (empty($this->segments)) {
            return $this->metadata->duration();
        }

        $lastSegment = end($this->segments);
        return $lastSegment['end'] ?? 0.0;
    }

    public function getSpeechRate(): float
    {
        $duration = $this->getDuration();
        $wordCount = $this->getWordCount();
        
        return $duration > 0 ? ($wordCount / ($duration / 60)) : 0.0;
    }

    public function getSegmentAt(float $timestamp): ?array
    {
        foreach ($this->segments as $segment) {
            $start = $segment['start'] ?? 0;
            $end = $segment['end'] ?? 0;
            
            if ($timestamp >= $start && $timestamp <= $end) {
                return $segment;
            }
        }
        
        return null;
    }

    public function getWordsInRange(float $startTime, float $endTime): array
    {
        if (empty($this->words)) {
            return [];
        }

        return array_filter($this->words, function($word) use ($startTime, $endTime) {
            $wordStart = $word['start'] ?? 0;
            $wordEnd = $word['end'] ?? 0;
            
            return $wordStart >= $startTime && $wordEnd <= $endTime;
        });
    }

    public function getTextBetween(float $startTime, float $endTime): string
    {
        $words = $this->getWordsInRange($startTime, $endTime);
        
        if (!empty($words)) {
            return implode(' ', array_column($words, 'word'));
        }

        // Fallback sur les segments
        $text = '';
        foreach ($this->segments as $segment) {
            $segmentStart = $segment['start'] ?? 0;
            $segmentEnd = $segment['end'] ?? 0;
            
            if ($segmentStart >= $startTime && $segmentEnd <= $endTime) {
                $text .= ($segment['text'] ?? '') . ' ';
            }
        }
        
        return trim($text);
    }

    public function enrichWithSpeakerDetection(array $speakerMapping): self
    {
        $enrichedSpeakerSegments = [];
        
        foreach ($speakerMapping as $segmentIndex => $speakerName) {
            if (isset($this->segments[$segmentIndex])) {
                $segment = $this->segments[$segmentIndex];
                $segment['speaker'] = $speakerName;
                $enrichedSpeakerSegments[$speakerName][] = $segment;
            }
        }

        return new self(
            $this->text,
            $this->segments,
            $this->words,
            $this->metadata->withSpeakers(array_keys($enrichedSpeakerSegments)),
            $this->confidence,
            $this->detectedLanguage,
            $enrichedSpeakerSegments
        );
    }

    public function getOptimalSegmentationForDubbing(): array
    {
        if (!$this->hasWordTimestamps()) {
            return $this->segments;
        }

        // Optimiser la segmentation pour le doublage
        $optimizedSegments = [];
        $currentSegment = null;
        $maxSegmentDuration = 5.0; // 5 secondes max par segment pour le doublage
        
        foreach ($this->words as $word) {
            $wordStart = $word['start'] ?? 0;
            $wordEnd = $word['end'] ?? 0;
            
            if ($currentSegment === null) {
                $currentSegment = [
                    'start' => $wordStart,
                    'end' => $wordEnd,
                    'text' => $word['word'] ?? '',
                    'words' => [$word],
                    'confidence' => $word['confidence'] ?? 1.0
                ];
            } else {
                $segmentDuration = $wordEnd - $currentSegment['start'];
                
                // Créer un nouveau segment si durée max atteinte ou pause détectée
                if ($segmentDuration > $maxSegmentDuration || 
                    $this->isPauseDetected($currentSegment['end'], $wordStart)) {
                    
                    $optimizedSegments[] = $currentSegment;
                    $currentSegment = [
                        'start' => $wordStart,
                        'end' => $wordEnd,
                        'text' => $word['word'] ?? '',
                        'words' => [$word],
                        'confidence' => $word['confidence'] ?? 1.0
                    ];
                } else {
                    // Étendre le segment actuel
                    $currentSegment['end'] = $wordEnd;
                    $currentSegment['text'] .= ' ' . ($word['word'] ?? '');
                    $currentSegment['words'][] = $word;
                }
            }
        }
        
        if ($currentSegment !== null) {
            $optimizedSegments[] = $currentSegment;
        }
        
        return $optimizedSegments;
    }

    public function getQualityMetrics(): array
    {
        return [
            'overallConfidence' => $this->confidence,
            'wordTimestampCoverage' => $this->hasWordTimestamps() ? 1.0 : 0.0,
            'segmentCount' => count($this->segments),
            'wordCount' => $this->getWordCount(),
            'speechRate' => $this->getSpeechRate(),
            'speechRateCategory' => $this->metadata->getSpeechRateCategory(),
            'dubbingReadiness' => $this->calculateDubbingReadiness(),
            'qualityScore' => $this->calculateQualityScore()
        ];
    }

    public function calculateDubbingReadiness(): float
    {
        $score = 0.0;
        
        // Confiance globale (30%)
        $score += $this->confidence * 0.3;
        
        // Présence de word timestamps (40%)
        if ($this->hasWordTimestamps()) {
            $score += 0.4;
        }
        
        // Qualité des métadonnées (20%)
        if ($this->metadata->isHighQualitySource()) {
            $score += 0.2;
        }
        
        // Débit de parole optimal (10%)
        $speechRate = $this->getSpeechRate();
        if ($speechRate >= 120 && $speechRate <= 180) {
            $score += 0.1;
        }
        
        return min($score, 1.0);
    }

    private function calculateQualityScore(): float
    {
        $scores = [];
        
        // Score de confiance
        $scores[] = $this->confidence;
        
        // Score de complétude
        $completeness = $this->hasWordTimestamps() ? 1.0 : 0.7;
        $scores[] = $completeness;
        
        // Score de cohérence temporelle
        $temporalConsistency = $this->calculateTemporalConsistency();
        $scores[] = $temporalConsistency;
        
        return array_sum($scores) / count($scores);
    }

    private function calculateTemporalConsistency(): float
    {
        if (count($this->segments) < 2) {
            return 1.0;
        }
        
        $inconsistencies = 0;
        $totalTransitions = 0;
        
        for ($i = 1; $i < count($this->segments); $i++) {
            $prevEnd = $this->segments[$i - 1]['end'] ?? 0;
            $currentStart = $this->segments[$i]['start'] ?? 0;
            
            $totalTransitions++;
            
            // Vérifier les chevauchements ou écarts trop importants
            if ($currentStart < $prevEnd || ($currentStart - $prevEnd) > 2.0) {
                $inconsistencies++;
            }
        }
        
        return $totalTransitions > 0 ? 1.0 - ($inconsistencies / $totalTransitions) : 1.0;
    }

    private function isPauseDetected(float $previousEnd, float $currentStart): bool
    {
        $gap = $currentStart - $previousEnd;
        return $gap > 0.5; // Pause de plus de 500ms
    }

    private static function calculateAverageConfidence(array $segments): float
    {
        if (empty($segments)) {
            return 0.0;
        }

        $totalConfidence = 0.0;
        $count = 0;

        foreach ($segments as $segment) {
            if (isset($segment['avg_logprob'])) {
                $totalConfidence += exp($segment['avg_logprob']);
                $count++;
            }
        }

        return $count > 0 ? $totalConfidence / $count : 0.0;
    }

    private static function groupSegmentsBySpeaker(array $segments): array
    {
        // Pour l'instant, retourne un seul groupe "default"
        // À implémenter avec une vraie détection de locuteurs
        return ['default' => $segments];
    }

    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'segments' => $this->segments,
            'words' => $this->words,
            'metadata' => $this->metadata->toArray(),
            'confidence' => $this->confidence,
            'detectedLanguage' => $this->detectedLanguage->code(),
            'speakerSegments' => $this->speakerSegments,
            'capabilities' => [
                'hasWordTimestamps' => $this->hasWordTimestamps(),
                'hasMultipleSpeakers' => $this->hasMultipleSpeakers(),
                'dubbingReady' => $this->calculateDubbingReadiness() > 0.8
            ],
            'statistics' => [
                'wordCount' => $this->getWordCount(),
                'segmentCount' => count($this->segments),
                'duration' => $this->getDuration(),
                'speechRate' => $this->getSpeechRate()
            ],
            'qualityMetrics' => $this->getQualityMetrics()
        ];
    }

    private function validateText(string $text): void
    {
        if (empty(trim($text))) {
            throw InvalidArgumentException::forEmptyValue('transcription text');
        }
    }

    private function validateConfidence(float $confidence): void
    {
        if ($confidence < 0.0 || $confidence > 1.0) {
            throw InvalidArgumentException::forOutOfRange(
                'confidence',
                $confidence,
                0.0,
                1.0
            );
        }
    }

    private function validateSegments(array $segments): void
    {
        foreach ($segments as $index => $segment) {
            if (!is_array($segment)) {
                throw new InvalidArgumentException("Segment at index {$index} must be an array");
            }
            
            if (!isset($segment['start']) || !isset($segment['end'])) {
                throw new InvalidArgumentException("Segment at index {$index} must have start and end timestamps");
            }
            
            if ($segment['start'] >= $segment['end']) {
                throw new InvalidArgumentException("Segment at index {$index} start must be before end");
            }
        }
    }

    private function validateWords(array $words): void
    {
        foreach ($words as $index => $word) {
            if (!is_array($word)) {
                throw new InvalidArgumentException("Word at index {$index} must be an array");
            }
            
            if (isset($word['start']) && isset($word['end']) && $word['start'] >= $word['end']) {
                throw new InvalidArgumentException("Word at index {$index} start must be before end");
            }
        }
    }

    private function validateSpeakerSegments(array $speakerSegments): void
    {
        foreach ($speakerSegments as $speaker => $segments) {
            if (!is_string($speaker)) {
                throw new InvalidArgumentException("Speaker identifier must be a string");
            }
            
            if (!is_array($segments)) {
                throw new InvalidArgumentException("Speaker segments must be an array");
            }
        }
    }

    public function __toString(): string
    {
        return sprintf(
            'DubbingTranscription[%d words, %d segments, %.1f%% confidence, %s]',
            $this->getWordCount(),
            count($this->segments),
            $this->confidence * 100,
            $this->detectedLanguage->code()
        );
    }
}