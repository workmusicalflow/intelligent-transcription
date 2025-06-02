<?php

namespace App\Services\Translation;

use App\Services\Translation\Contracts\TranslationServiceInterface;
use App\Services\Translation\DTO\TranslationConfig;
use App\Services\Translation\Exceptions\TranslationServiceException;
use Psr\Log\LoggerInterface;

/**
 * Service de traduction hybride intelligent
 * Combine plusieurs services pour qualité optimale avec fallbacks
 */
class HybridTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private $openAIClient, // OpenAI\Client
        private LoggerInterface $logger,
        private TranslationCacheService $cache,
        private TranslationServiceFactory $factory
    ) {}

    /**
     * Traduire avec sélection intelligente et fallbacks automatiques
     */
    public function translateSegments(
        array $segments,
        string $targetLanguage,
        ?TranslationConfig $config = null
    ): array {
        $config = $config ?? new TranslationConfig();
        
        $this->logger->info('Starting hybrid translation', [
            'segments_count' => count($segments),
            'target_language' => $targetLanguage
        ]);

        // Stratégie: diviser en lots par complexité pour optimisation
        $segmentBatches = $this->categorizeSegmentsByComplexity($segments);
        $results = [];
        $errors = [];

        foreach ($segmentBatches as $batchType => $batch) {
            if (empty($batch['segments'])) continue;

            try {
                $batchResults = $this->translateBatch(
                    $batch['segments'],
                    $targetLanguage,
                    $config,
                    $batch['recommended_service']
                );

                $results = array_merge($results, $batchResults);

                $this->logger->info('Batch translation successful', [
                    'batch_type' => $batchType,
                    'segments_count' => count($batch['segments']),
                    'service_used' => $batch['recommended_service']
                ]);

            } catch (TranslationServiceException $e) {
                $this->logger->warning('Batch translation failed, trying fallback', [
                    'batch_type' => $batchType,
                    'primary_service' => $batch['recommended_service'],
                    'error' => $e->getMessage()
                ]);

                // Fallback automatique
                try {
                    $fallbackService = $this->getFallbackService($batch['recommended_service']);
                    $batchResults = $this->translateBatch(
                        $batch['segments'],
                        $targetLanguage,
                        $config,
                        $fallbackService
                    );

                    $results = array_merge($results, $batchResults);

                    $this->logger->info('Fallback translation successful', [
                        'batch_type' => $batchType,
                        'fallback_service' => $fallbackService
                    ]);

                } catch (TranslationServiceException $fallbackError) {
                    $errors[] = [
                        'batch_type' => $batchType,
                        'segments' => $batch['segments'],
                        'primary_error' => $e->getMessage(),
                        'fallback_error' => $fallbackError->getMessage()
                    ];
                }
            }
        }

        // Vérifier si on a des erreurs critiques
        if (!empty($errors) && empty($results)) {
            throw new TranslationServiceException(
                'All translation services failed',
                TranslationServiceException::CODE_API_ERROR,
                null,
                ['errors' => $errors]
            );
        }

        // Traiter erreurs partielles avec dernier recours
        if (!empty($errors)) {
            $this->handlePartialFailures($errors, $results, $targetLanguage, $config);
        }

        // Réordonner résultats selon ordre original
        usort($results, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));

        $this->logger->info('Hybrid translation completed', [
            'total_segments' => count($segments),
            'successful_segments' => count($results),
            'failed_segments' => count($errors)
        ]);

        return $results;
    }

    /**
     * Catégoriser segments par complexité pour optimisation
     */
    private function categorizeSegmentsByComplexity(array $segments): array
    {
        $batches = [
            'simple' => ['segments' => [], 'recommended_service' => 'whisper-1'],
            'complex' => ['segments' => [], 'recommended_service' => 'gpt-4o-mini'],
            'technical' => ['segments' => [], 'recommended_service' => 'gpt-4o-mini']
        ];

        foreach ($segments as $segment) {
            $text = $segment['text'] ?? '';
            $complexity = $this->analyzeSegmentComplexity($text);

            if ($complexity['is_technical']) {
                $batches['technical']['segments'][] = $segment;
            } elseif ($complexity['complexity_score'] > 0.6) {
                $batches['complex']['segments'][] = $segment;
            } else {
                $batches['simple']['segments'][] = $segment;
            }
        }

        return $batches;
    }

    /**
     * Analyser complexité d'un segment
     */
    private function analyzeSegmentComplexity(string $text): array
    {
        $complexity = 0;
        $isTechnical = false;

        // Indicateurs de complexité
        $indicators = [
            'long_words' => preg_match_all('/\b\w{10,}\b/', $text),
            'capitals' => preg_match_all('/[A-Z]{2,}/', $text),
            'numbers' => preg_match_all('/\d+/', $text),
            'punctuation' => preg_match_all('/[;:()[\]{}]/', $text),
            'special_chars' => preg_match_all('/[@#$%&*]/', $text)
        ];

        // Calculer score de complexité
        $textLength = max(strlen($text), 1);
        foreach ($indicators as $type => $count) {
            $weight = match($type) {
                'long_words' => 0.3,
                'capitals' => 0.2,
                'numbers' => 0.2,
                'punctuation' => 0.2,
                'special_chars' => 0.1
            };
            $complexity += ($count / $textLength) * 100 * $weight;
        }

        // Détecter contenu technique
        $technicalPatterns = [
            '/\b(API|HTTP|JSON|XML|SQL|URL|ID|UUID)\b/i',
            '/\b(server|client|database|endpoint|authentication)\b/i',
            '/\b\w+\.(com|org|net|php|js|html|css)\b/i'
        ];

        foreach ($technicalPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $isTechnical = true;
                break;
            }
        }

        return [
            'complexity_score' => min($complexity, 1.0),
            'is_technical' => $isTechnical,
            'indicators' => $indicators
        ];
    }

    /**
     * Traduire lot avec service spécifique
     */
    private function translateBatch(
        array $segments,
        string $targetLanguage,
        TranslationConfig $config,
        string $serviceType
    ): array {
        $service = $this->factory->createService($serviceType);
        return $service->translateSegments($segments, $targetLanguage, $config);
    }

    /**
     * Obtenir service de fallback
     */
    private function getFallbackService(string $primaryService): string
    {
        return match($primaryService) {
            'whisper-1' => 'gpt-4o-mini',
            'gpt-4o-mini' => 'gpt-4o-mini', // Même service avec retry
            default => 'gpt-4o-mini'
        };
    }

    /**
     * Gérer échecs partiels avec derniers recours
     */
    private function handlePartialFailures(
        array $errors,
        array &$results,
        string $targetLanguage,
        TranslationConfig $_config
    ): void {
        foreach ($errors as $error) {
            // Dernier recours: traduction simplifiée segment par segment
            foreach ($error['segments'] as $segment) {
                try {
                    $simpleConfig = new TranslationConfig(
                        preserveTimestamps: true,
                        strictTiming: false, // Assouplir contraintes
                        translationStyle: 'literal' // Style plus simple
                    );

                    $fallbackResult = $this->translateBatch(
                        [$segment],
                        $targetLanguage,
                        $simpleConfig,
                        'gpt-4o-mini'
                    );

                    $results = array_merge($results, $fallbackResult);

                } catch (\Exception $finalError) {
                    // Vraiment dernier recours: segment non traduit mais préservé
                    $results[] = [
                        'id' => $segment['id'] ?? count($results),
                        'text' => '[TRANSLATION_FAILED] ' . ($segment['text'] ?? ''),
                        'startTime' => $segment['startTime'] ?? 0,
                        'endTime' => $segment['endTime'] ?? 0,
                        'words' => $segment['words'] ?? [],
                        'translation_notes' => 'Translation failed: ' . $finalError->getMessage(),
                        'original_text' => $segment['text'] ?? '',
                        'service_used' => 'hybrid-fallback'
                    ];

                    $this->logger->error('Final fallback failed', [
                        'segment_id' => $segment['id'] ?? 'unknown',
                        'error' => $finalError->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Estimer coût avec optimisation hybride
     */
    public function estimateCost(array $segments, string $targetLanguage): float
    {
        $batches = $this->categorizeSegmentsByComplexity($segments);
        $totalCost = 0;

        foreach ($batches as $batch) {
            if (empty($batch['segments'])) continue;

            $service = $this->factory->createService($batch['recommended_service']);
            $totalCost += $service->estimateCost($batch['segments'], $targetLanguage);
        }

        // Ajouter marge pour fallbacks potentiels (10%)
        return $totalCost * 1.1;
    }

    /**
     * Capacités combinées du service hybride
     */
    public function getCapabilities(): array
    {
        return [
            'preserves_word_timestamps' => true,
            'preserves_segment_timestamps' => true,
            'supported_languages' => ['fr', 'es', 'de', 'it', 'pt', 'nl', 'sv', 'no', 'da', 'zh', 'ja', 'ar', 'hi'],
            'supports_emotional_context' => true,
            'supports_character_names' => true,
            'supports_technical_terms' => true,
            'supports_timing_adaptation' => true,
            'supports_batch_processing' => true,
            'has_intelligent_fallbacks' => true,
            'optimizes_by_complexity' => true,
            'max_segments_per_batch' => 100,
            'reliability_score' => 0.99, // Très haute fiabilité grâce aux fallbacks
            'cost_optimization' => true
        ];
    }
}