<?php

namespace App\Services\Translation;

use App\Services\Translation\Contracts\TranslationServiceInterface;
use App\Services\Translation\DTO\TranslationConfig;
use App\Services\Translation\Exceptions\TranslationServiceException;
use Psr\Log\LoggerInterface;

/**
 * Factory pour créer services de traduction optimaux
 * Sélection intelligente basée sur langue, volume et qualité
 */
class TranslationServiceFactory
{
    private array $availableServices = [];

    public function __construct(
        private $openAIClient, // OpenAI\Client
        private LoggerInterface $logger,
        private TranslationCacheService $cache
    ) {
        $this->initializeServices();
    }

    /**
     * Créer service optimal pour langue et contexte donnés
     */
    public function createOptimalService(
        string $targetLanguage,
        array $segments,
        ?TranslationConfig $config = null
    ): TranslationServiceInterface {
        $config = $config ?? new TranslationConfig();
        
        // Analyser contexte pour sélection optimale
        $context = $this->analyzeTranslationContext($segments, $targetLanguage, $config);
        
        $serviceType = $this->selectBestService($context);
        
        $this->logger->info('Selected translation service', [
            'service_type' => $serviceType,
            'target_language' => $targetLanguage,
            'segments_count' => count($segments),
            'context' => $context
        ]);

        return $this->createService($serviceType);
    }

    /**
     * Créer service par nom
     */
    public function createService(string $serviceType): TranslationServiceInterface
    {
        return match ($serviceType) {
            'gpt-4o-mini' => new GPTTranslationService(
                $this->openAIClient,
                $this->logger,
                $this->cache
            ),
            'whisper-1' => new WhisperTranslationService(
                $this->openAIClient,
                $this->logger,
                $this->cache
            ),
            'hybrid' => new HybridTranslationService(
                $this->openAIClient,
                $this->logger,
                $this->cache,
                $this
            ),
            default => throw TranslationServiceException::unsupportedLanguage($serviceType)
        };
    }

    /**
     * Obtenir tous services disponibles avec leurs capacités
     */
    public function getAvailableServices(): array
    {
        $services = [];
        
        foreach ($this->availableServices as $type => $config) {
            try {
                $service = $this->createService($type);
                $services[$type] = [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'capabilities' => $service->getCapabilities(),
                    'recommended_for' => $config['recommended_for']
                ];
            } catch (\Exception $e) {
                $this->logger->warning('Translation service unavailable', [
                    'service_type' => $type,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $services;
    }

    /**
     * Analyser contexte de traduction
     */
    private function analyzeTranslationContext(
        array $segments,
        string $targetLanguage,
        TranslationConfig $config
    ): array {
        $totalDuration = 0;
        $totalCharacters = 0;
        $hasWordTimestamps = false;
        $complexityScore = 0;

        foreach ($segments as $segment) {
            $duration = ($segment['endTime'] ?? 0) - ($segment['startTime'] ?? 0);
            $totalDuration += $duration;
            $totalCharacters += strlen($segment['text'] ?? '');
            
            if (!empty($segment['words'])) {
                $hasWordTimestamps = true;
            }

            // Calculer complexité (termes techniques, noms propres, etc.)
            $text = $segment['text'] ?? '';
            $complexityScore += $this->calculateTextComplexity($text);
        }

        return [
            'segments_count' => count($segments),
            'total_duration' => $totalDuration,
            'total_characters' => $totalCharacters,
            'average_segment_duration' => count($segments) > 0 ? $totalDuration / count($segments) : 0,
            'has_word_timestamps' => $hasWordTimestamps,
            'target_language' => $targetLanguage,
            'complexity_score' => $complexityScore / max(count($segments), 1),
            'strict_timing' => $config->strictTiming,
            'has_emotional_context' => !empty($config->emotionalContext),
            'has_character_names' => !empty($config->characterNames),
            'has_technical_terms' => !empty($config->technicalTerms)
        ];
    }

    /**
     * Sélectionner meilleur service basé sur contexte
     */
    private function selectBestService(array $context): string
    {
        // Règles de sélection intelligente
        
        // 1. Langues complexes ou rares → GPT-4o-mini (meilleure compréhension)
        $complexLanguages = ['zh', 'ja', 'ar', 'hi', 'th', 'ko'];
        if (in_array($context['target_language'], $complexLanguages)) {
            return 'gpt-4o-mini';
        }

        // 2. Contenu très court (< 30s) → GPT-4o-mini (meilleur pour contexte limité)
        if ($context['total_duration'] < 30) {
            return 'gpt-4o-mini';
        }

        // 3. Contenu très technique ou émotionnel → GPT-4o-mini
        if ($context['complexity_score'] > 0.7 || $context['has_emotional_context']) {
            return 'gpt-4o-mini';
        }

        // 4. Timing strict avec word-level → GPT-4o-mini (préservation optimale)
        if ($context['strict_timing'] && $context['has_word_timestamps']) {
            return 'gpt-4o-mini';
        }

        // 5. Contenu long et simple vers langues principales → Whisper-1 (économique)
        $primaryLanguages = ['fr', 'es', 'de', 'it', 'pt'];
        if ($context['total_duration'] > 300 && 
            $context['complexity_score'] < 0.3 && 
            in_array($context['target_language'], $primaryLanguages)) {
            return 'whisper-1';
        }

        // 6. Défaut: GPT-4o-mini (meilleur équilibre qualité/flexibilité)
        return 'gpt-4o-mini';
    }

    /**
     * Calculer complexité du texte
     */
    private function calculateTextComplexity(string $text): float
    {
        if (empty($text)) return 0;

        $complexity = 0;
        
        // Mots longs (indicateur technique)
        $words = explode(' ', $text);
        $longWords = array_filter($words, fn($w) => strlen($w) > 8);
        $complexity += count($longWords) / max(count($words), 1) * 0.3;

        // Majuscules (noms propres, acronymes)
        $uppercaseMatches = preg_match_all('/[A-Z]{2,}/', $text);
        $complexity += $uppercaseMatches / max(strlen($text), 1) * 100 * 0.2;

        // Ponctuation complexe
        $complexPunctuation = preg_match_all('/[;:()[\]{}"]/', $text);
        $complexity += $complexPunctuation / max(strlen($text), 1) * 100 * 0.2;

        // Chiffres et symboles (contenu technique)
        $numbersAndSymbols = preg_match_all('/[0-9%$€£¥@#&]/', $text);
        $complexity += $numbersAndSymbols / max(strlen($text), 1) * 100 * 0.3;

        return min($complexity, 1.0); // Normaliser entre 0 et 1
    }

    /**
     * Initialiser services disponibles
     */
    private function initializeServices(): void
    {
        $this->availableServices = [
            'gpt-4o-mini' => [
                'name' => 'GPT-4o-mini Translation',
                'description' => 'Service de traduction intelligent avec préservation timestamps',
                'recommended_for' => [
                    'Contenu émotionnel ou créatif',
                    'Langues complexes',
                    'Timing strict',
                    'Contexte technique spécialisé'
                ]
            ],
            'whisper-1' => [
                'name' => 'Whisper-1 Translation',
                'description' => 'Traduction native OpenAI avec timestamps préservés',
                'recommended_for' => [
                    'Contenu long et simple',
                    'Langues principales européennes',
                    'Budget optimisé',
                    'Transcription + traduction simultanée'
                ]
            ],
            'hybrid' => [
                'name' => 'Hybrid Translation Service',
                'description' => 'Combinaison intelligente de plusieurs services',
                'recommended_for' => [
                    'Contenu mixte complexe/simple',
                    'Qualité maximum requise',
                    'Fallback automatique',
                    'Projets critiques'
                ]
            ]
        ];
    }
}