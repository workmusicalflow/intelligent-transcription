<?php

namespace Domain\Dubbing\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;

/**
 * Configuration pour un projet de doublage révolutionnaire
 * 
 * Cette classe encapsule toute la configuration nécessaire pour
 * créer un doublage audio parfaitement synchronisé avec GPT-4o-mini-TTS
 */
final class DubbingConfig extends ValueObject
{
    private string $targetLanguage;
    private string $voicePreset;
    private string $emotionalInstructions;
    private bool $enableStreaming;
    private bool $preserveEmotions;
    private bool $enableMultiSpeaker;
    private array $customPrompts;
    private int $qualityThreshold;
    private bool $autoSync;
    
    // Contraintes temporelles GPT-4o-mini
    private bool $nativeSpeedControl;
    private bool $strictTiming;
    private float $silencePadding;
    private string $responseFormat;

    private const VALID_VOICES = [
        'alloy', 'ash', 'ballad', 'coral', 'echo', 
        'fable', 'nova', 'onyx', 'sage', 'shimmer'
    ];

    private const VALID_LANGUAGES = [
        'fr', 'en', 'es', 'de', 'it', 'pt', 'ru', 
        'ja', 'ko', 'zh', 'ar', 'hi', 'nl', 'sv'
    ];

    private const VALID_FORMATS = ['wav', 'mp3', 'opus'];

    public function __construct(
        string $targetLanguage,
        string $voicePreset = 'coral',
        string $emotionalInstructions = 'Match the emotional tone and speak at natural conversational pace',
        bool $enableStreaming = true,
        bool $preserveEmotions = true,
        bool $enableMultiSpeaker = false,
        array $customPrompts = [],
        int $qualityThreshold = 85,
        bool $autoSync = true,
        bool $nativeSpeedControl = true,
        bool $strictTiming = true,
        float $silencePadding = 50.0,
        string $responseFormat = 'wav'
    ) {
        $this->validateTargetLanguage($targetLanguage);
        $this->validateVoicePreset($voicePreset);
        $this->validateQualityThreshold($qualityThreshold);
        $this->validateSilencePadding($silencePadding);
        $this->validateResponseFormat($responseFormat);

        $this->targetLanguage = $targetLanguage;
        $this->voicePreset = $voicePreset;
        $this->emotionalInstructions = trim($emotionalInstructions);
        $this->enableStreaming = $enableStreaming;
        $this->preserveEmotions = $preserveEmotions;
        $this->enableMultiSpeaker = $enableMultiSpeaker;
        $this->customPrompts = $customPrompts;
        $this->qualityThreshold = $qualityThreshold;
        $this->autoSync = $autoSync;
        $this->nativeSpeedControl = $nativeSpeedControl;
        $this->strictTiming = $strictTiming;
        $this->silencePadding = $silencePadding;
        $this->responseFormat = $responseFormat;
    }

    public static function createDefault(string $targetLanguage): self
    {
        return new self($targetLanguage);
    }

    public static function createHighQuality(string $targetLanguage): self
    {
        return new self(
            targetLanguage: $targetLanguage,
            voicePreset: 'coral',
            emotionalInstructions: 'Match the emotional tone precisely with natural conversational flow and perfect timing',
            enableStreaming: true,
            preserveEmotions: true,
            enableMultiSpeaker: true,
            qualityThreshold: 95,
            strictTiming: true,
            silencePadding: 25.0,
            responseFormat: 'wav'
        );
    }

    public static function createDialogueOptimized(string $targetLanguage): self
    {
        return new self(
            targetLanguage: $targetLanguage,
            voicePreset: 'nova',
            emotionalInstructions: 'Use natural conversational speech patterns with appropriate pauses and emotional expressiveness',
            enableStreaming: true,
            preserveEmotions: true,
            enableMultiSpeaker: true,
            customPrompts: [
                'dialogue_context' => 'This is film/TV dialogue with natural speech patterns',
                'pause_preservation' => 'Maintain natural pauses and breath patterns',
                'emotion_matching' => 'Match the emotional intensity of the original performance'
            ],
            qualityThreshold: 90,
            strictTiming: true,
            silencePadding: 75.0
        );
    }

    public function targetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function voicePreset(): string
    {
        return $this->voicePreset;
    }

    public function emotionalInstructions(): string
    {
        return $this->emotionalInstructions;
    }

    public function enableStreaming(): bool
    {
        return $this->enableStreaming;
    }

    public function preserveEmotions(): bool
    {
        return $this->preserveEmotions;
    }

    public function enableMultiSpeaker(): bool
    {
        return $this->enableMultiSpeaker;
    }

    public function customPrompts(): array
    {
        return $this->customPrompts;
    }

    public function qualityThreshold(): int
    {
        return $this->qualityThreshold;
    }

    public function autoSync(): bool
    {
        return $this->autoSync;
    }

    public function nativeSpeedControl(): bool
    {
        return $this->nativeSpeedControl;
    }

    public function strictTiming(): bool
    {
        return $this->strictTiming;
    }

    public function silencePadding(): float
    {
        return $this->silencePadding;
    }

    public function responseFormat(): string
    {
        return $this->responseFormat;
    }

    public function withVoice(string $voicePreset): self
    {
        $this->validateVoicePreset($voicePreset);
        
        return new self(
            $this->targetLanguage,
            $voicePreset,
            $this->emotionalInstructions,
            $this->enableStreaming,
            $this->preserveEmotions,
            $this->enableMultiSpeaker,
            $this->customPrompts,
            $this->qualityThreshold,
            $this->autoSync,
            $this->nativeSpeedControl,
            $this->strictTiming,
            $this->silencePadding,
            $this->responseFormat
        );
    }

    public function withCustomPrompts(array $prompts): self
    {
        return new self(
            $this->targetLanguage,
            $this->voicePreset,
            $this->emotionalInstructions,
            $this->enableStreaming,
            $this->preserveEmotions,
            $this->enableMultiSpeaker,
            array_merge($this->customPrompts, $prompts),
            $this->qualityThreshold,
            $this->autoSync,
            $this->nativeSpeedControl,
            $this->strictTiming,
            $this->silencePadding,
            $this->responseFormat
        );
    }

    public function isOptimizedForDubbing(): bool
    {
        return $this->nativeSpeedControl && 
               $this->strictTiming && 
               $this->preserveEmotions &&
               $this->qualityThreshold >= 85;
    }

    public function isStreamingReady(): bool
    {
        return $this->enableStreaming && $this->responseFormat === 'wav';
    }

    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }

    public function toArray(): array
    {
        return [
            'targetLanguage' => $this->targetLanguage,
            'voicePreset' => $this->voicePreset,
            'emotionalInstructions' => $this->emotionalInstructions,
            'enableStreaming' => $this->enableStreaming,
            'preserveEmotions' => $this->preserveEmotions,
            'enableMultiSpeaker' => $this->enableMultiSpeaker,
            'customPrompts' => $this->customPrompts,
            'qualityThreshold' => $this->qualityThreshold,
            'autoSync' => $this->autoSync,
            'nativeSpeedControl' => $this->nativeSpeedControl,
            'strictTiming' => $this->strictTiming,
            'silencePadding' => $this->silencePadding,
            'responseFormat' => $this->responseFormat,
            'capabilities' => [
                'optimizedForDubbing' => $this->isOptimizedForDubbing(),
                'streamingReady' => $this->isStreamingReady(),
                'multiSpeakerSupport' => $this->enableMultiSpeaker,
                'emotionalControl' => $this->preserveEmotions
            ]
        ];
    }

    private function validateTargetLanguage(string $language): void
    {
        if (!in_array($language, self::VALID_LANGUAGES, true)) {
            throw InvalidArgumentException::forInvalidChoice(
                'target language',
                $language,
                self::VALID_LANGUAGES
            );
        }
    }

    private function validateVoicePreset(string $voice): void
    {
        if (!in_array($voice, self::VALID_VOICES, true)) {
            throw InvalidArgumentException::forInvalidChoice(
                'voice preset',
                $voice,
                self::VALID_VOICES
            );
        }
    }

    private function validateQualityThreshold(int $threshold): void
    {
        if ($threshold < 0 || $threshold > 100) {
            throw InvalidArgumentException::forOutOfRange(
                'quality threshold',
                $threshold,
                0,
                100
            );
        }
    }

    private function validateSilencePadding(float $padding): void
    {
        if ($padding < 0 || $padding > 1000) {
            throw InvalidArgumentException::forOutOfRange(
                'silence padding',
                $padding,
                0.0,
                1000.0
            );
        }
    }

    private function validateResponseFormat(string $format): void
    {
        if (!in_array($format, self::VALID_FORMATS, true)) {
            throw InvalidArgumentException::forInvalidChoice(
                'response format',
                $format,
                self::VALID_FORMATS
            );
        }
    }

    public function __toString(): string
    {
        return sprintf(
            'DubbingConfig[%s->%s, voice=%s, quality=%d%%, streaming=%s]',
            'source',
            $this->targetLanguage,
            $this->voicePreset,
            $this->qualityThreshold,
            $this->enableStreaming ? 'ON' : 'OFF'
        );
    }
}