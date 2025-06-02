<?php

namespace App\Services\Translation;

use App\Services\Translation\Contracts\TranslationServiceInterface;
use App\Services\Translation\DTO\TranslationConfig;
use App\Services\Translation\Exceptions\TranslationServiceException;
use Psr\Log\LoggerInterface;

/**
 * Service de traduction Whisper-1 natif
 * Fallback pour langues principales avec préservation timestamps
 */
class WhisperTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private $openAIClient, // OpenAI\Client
        private LoggerInterface $logger,
        private TranslationCacheService $cache
    ) {}

    /**
     * Traduire segments via Whisper-1 API
     * Note: Nécessite fichier audio original pour fonctionner
     */
    public function translateSegments(
        array $segments,
        string $targetLanguage,
        ?TranslationConfig $config = null
    ): array {
        $config = $config ?? new TranslationConfig();
        
        // Whisper-1 nécessite fichier audio - limitation technique
        throw new TranslationServiceException(
            'Whisper-1 translation requires original audio file. ' .
            'This service is designed for direct audio-to-translation workflow. ' .
            'Use GPT-4o-mini service for segment-based translation.',
            TranslationServiceException::CODE_VALIDATION_ERROR,
            null,
            [
                'service' => 'whisper-1',
                'target_language' => $targetLanguage,
                'segments_count' => count($segments),
                'recommendation' => 'Use GPTTranslationService for this use case'
            ]
        );
    }

    /**
     * Traduire directement depuis fichier audio (méthode principale)
     */
    public function translateFromAudio(
        string $audioPath,
        string $targetLanguage,
        ?TranslationConfig $config = null
    ): array {
        $config = $config ?? new TranslationConfig();
        
        $this->logger->info('Starting Whisper-1 translation from audio', [
            'audio_path' => basename($audioPath),
            'target_language' => $targetLanguage,
            'config' => $config->toArray()
        ]);

        try {
            // Vérifier cache
            $cacheKey = $this->generateCacheKey($audioPath, $targetLanguage, $config);
            if ($cachedResult = $this->cache->get($cacheKey)) {
                $this->logger->info('Whisper translation cache hit');
                return $cachedResult;
            }

            // Préparer requête Whisper avec traduction
            $requestData = [
                'model' => 'whisper-1',
                'file' => new \CURLFile($audioPath, 'audio/*', basename($audioPath)),
                'response_format' => 'verbose_json',
                'timestamp_granularities' => ['segment'] // Whisper translation ne supporte pas word-level
            ];

            // Ajouter prompt contextuel si nécessaire
            if (!empty($config->characterNames) || !empty($config->technicalTerms)) {
                $prompt = $this->buildWhisperPrompt($config);
                $requestData['prompt'] = $prompt;
            }

            // Note: Whisper-1 traduit principalement vers l'anglais
            // Pour autres langues, utiliser GPT-4o-mini post-processing
            if ($targetLanguage !== 'en') {
                $this->logger->warning('Whisper-1 primarily translates to English', [
                    'requested_language' => $targetLanguage,
                    'recommendation' => 'Consider GPT-4o-mini service for non-English targets'
                ]);
            }

            // Appel API Whisper
            $response = $this->openAIClient->audio()->translations()->create($requestData);

            // Traiter réponse
            $segments = $this->processWhisperResponse($response, $targetLanguage);

            // Cache résultat
            $this->cache->set($cacheKey, $segments, 7200); // 2h TTL

            $this->logger->info('Whisper translation completed', [
                'segments_count' => count($segments),
                'target_language' => $targetLanguage
            ]);

            return $segments;

        } catch (\Exception $e) {
            $this->logger->error('Whisper translation failed', [
                'error' => $e->getMessage(),
                'audio_path' => basename($audioPath),
                'target_language' => $targetLanguage
            ]);

            throw new TranslationServiceException(
                'Whisper translation failed: ' . $e->getMessage(),
                TranslationServiceException::CODE_API_ERROR,
                $e
            );
        }
    }

    /**
     * Estimer coût Whisper translation
     */
    public function estimateCost(array $segments, string $targetLanguage): float
    {
        // Estimation basée sur durée audio (Whisper pricing: $0.006/minute)
        $totalDuration = 0;
        foreach ($segments as $segment) {
            $totalDuration += ($segment['endTime'] ?? 0) - ($segment['startTime'] ?? 0);
        }

        $minutes = $totalDuration / 60;
        return $minutes * 0.006;
    }

    /**
     * Capacités du service Whisper
     */
    public function getCapabilities(): array
    {
        return [
            'preserves_word_timestamps' => false, // Limitation Whisper translation
            'preserves_segment_timestamps' => true,
            'supported_languages' => ['en'], // Whisper traduit principalement vers anglais
            'supports_emotional_context' => false,
            'supports_character_names' => true,
            'supports_technical_terms' => true,
            'supports_timing_adaptation' => false,
            'supports_batch_processing' => false,
            'requires_audio_file' => true, // Différence clé
            'max_file_size_mb' => 25,
            'cost_per_minute' => 0.006
        ];
    }

    /**
     * Construire prompt contextuel pour Whisper
     */
    private function buildWhisperPrompt(TranslationConfig $config): string
    {
        $prompt = [];

        if (!empty($config->characterNames)) {
            $characters = implode(', ', $config->characterNames);
            $prompt[] = "Character names: {$characters}";
        }

        if (!empty($config->technicalTerms)) {
            $terms = implode(', ', $config->technicalTerms);
            $prompt[] = "Technical terms: {$terms}";
        }

        return implode('. ', $prompt);
    }

    /**
     * Traiter réponse Whisper en segments standardisés
     */
    private function processWhisperResponse($response, string $_targetLanguage): array
    {
        $segments = [];

        if (isset($response['segments'])) {
            foreach ($response['segments'] as $index => $segment) {
                $segments[] = [
                    'id' => $index,
                    'text' => $segment['text'] ?? '',
                    'startTime' => $segment['start'] ?? 0,
                    'endTime' => $segment['end'] ?? 0,
                    'duration' => ($segment['end'] ?? 0) - ($segment['start'] ?? 0),
                    'confidence' => isset($segment['avg_logprob']) ? exp($segment['avg_logprob']) : null,
                    'words' => [], // Whisper translation ne fournit pas word-level
                    'translation_notes' => 'Translated by Whisper-1 native API',
                    'original_text' => null, // Non disponible en mode traduction directe
                    'service_used' => 'whisper-1'
                ];
            }
        } else {
            // Fallback: créer segment unique si pas de segmentation
            $segments[] = [
                'id' => 0,
                'text' => $response['text'] ?? '',
                'startTime' => 0,
                'endTime' => $response['duration'] ?? 0,
                'duration' => $response['duration'] ?? 0,
                'confidence' => null,
                'words' => [],
                'translation_notes' => 'Single segment translation by Whisper-1',
                'original_text' => null,
                'service_used' => 'whisper-1'
            ];
        }

        return $segments;
    }

    /**
     * Générer clé cache pour traduction audio
     */
    private function generateCacheKey(string $audioPath, string $targetLanguage, TranslationConfig $config): string
    {
        $audioHash = hash_file('md5', $audioPath);
        $configHash = md5(serialize($config->toArray()));
        
        return "whisper_translation_{$audioHash}_{$targetLanguage}_{$configHash}";
    }
}