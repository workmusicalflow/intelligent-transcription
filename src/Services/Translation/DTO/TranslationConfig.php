<?php

namespace App\Services\Translation\DTO;

/**
 * Configuration pour le service de traduction
 * Optimisée pour doublage avec préservation timestamps
 */
class TranslationConfig
{
    public function __construct(
        public readonly bool $preserveTimestamps = true,
        public readonly bool $strictTiming = false,
        public readonly array $emotionalContext = [],
        public readonly array $characterNames = [],
        public readonly array $technicalTerms = [],
        public readonly string $contentType = 'dialogue', // dialogue, narration, news
        public readonly bool $adaptLengthForDubbing = true,
        public readonly float $maxDurationDeviation = 0.2, // 20% max
        public readonly string $translationStyle = 'natural', // natural, literal, creative
        public readonly bool $enableCache = true
    ) {}

    /**
     * Configuration optimisée pour doublage anglais → français
     */
    public static function forEnglishToFrenchDubbing(array $overrides = []): self
    {
        $defaults = [
            'preserveTimestamps' => true,
            'strictTiming' => true,
            'adaptLengthForDubbing' => true,
            'translationStyle' => 'natural',
            'contentType' => 'dialogue',
            'maxDurationDeviation' => 0.15 // Plus strict pour français
        ];

        return new self(...array_merge($defaults, $overrides));
    }

    /**
     * Configuration pour contenu médical/technique
     */
    public static function forTechnicalContent(array $technicalTerms, array $overrides = []): self
    {
        $defaults = [
            'technicalTerms' => $technicalTerms,
            'translationStyle' => 'literal',
            'strictTiming' => false, // Plus de flexibilité pour précision
            'contentType' => 'technical'
        ];

        return new self(...array_merge($defaults, $overrides));
    }

    /**
     * Configuration pour contenu émotionnel (film, série)
     */
    public static function forEmotionalContent(array $emotions, array $characters = []): self
    {
        return new self(
            emotionalContext: $emotions,
            characterNames: $characters,
            contentType: 'dialogue',
            translationStyle: 'creative',
            adaptLengthForDubbing: true,
            strictTiming: true
        );
    }

    public function toArray(): array
    {
        return [
            'preserve_timestamps' => $this->preserveTimestamps,
            'strict_timing' => $this->strictTiming,
            'emotional_context' => $this->emotionalContext,
            'character_names' => $this->characterNames,
            'technical_terms' => $this->technicalTerms,
            'content_type' => $this->contentType,
            'adapt_length_for_dubbing' => $this->adaptLengthForDubbing,
            'max_duration_deviation' => $this->maxDurationDeviation,
            'translation_style' => $this->translationStyle,
            'enable_cache' => $this->enableCache
        ];
    }

    public function withEmotionalContext(array $emotions): self
    {
        return new self(
            $this->preserveTimestamps,
            $this->strictTiming,
            $emotions,
            $this->characterNames,
            $this->technicalTerms,
            $this->contentType,
            $this->adaptLengthForDubbing,
            $this->maxDurationDeviation,
            $this->translationStyle,
            $this->enableCache
        );
    }

    public function withCharacters(array $characters): self
    {
        return new self(
            $this->preserveTimestamps,
            $this->strictTiming,
            $this->emotionalContext,
            $characters,
            $this->technicalTerms,
            $this->contentType,
            $this->adaptLengthForDubbing,
            $this->maxDurationDeviation,
            $this->translationStyle,
            $this->enableCache
        );
    }
}