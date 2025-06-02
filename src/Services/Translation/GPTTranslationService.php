<?php

namespace App\Services\Translation;

use App\Services\Translation\Contracts\TranslationServiceInterface;
use App\Services\Translation\DTO\TranslationConfig;
use App\Services\Translation\Exceptions\TranslationServiceException;
use Psr\Log\LoggerInterface;

/**
 * Service de traduction GPT-4o-mini avec préservation timestamps
 * Spécialisé pour Anglais → Français avec synchronisation doublage
 */
class GPTTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private $openAIClient, // OpenAI\Client
        private LoggerInterface $logger,
        private TranslationCacheService $cache
    ) {}

    /**
     * Traduire des segments avec préservation des timestamps pour doublage
     *
     * @param array $segments Segments avec word-level timestamps
     * @param string $targetLanguage Code langue cible (fr, es, de, etc.)
     * @param TranslationConfig|null $config Configuration optionnelle
     * @return array Segments traduits avec timestamps préservés
     * @throws TranslationServiceException
     */
    public function translateSegments(
        array $segments,
        string $targetLanguage,
        ?TranslationConfig $config = null
    ): array {
        $config = $config ?? new TranslationConfig();
        
        $this->logger->info('Starting GPT translation', [
            'segments_count' => count($segments),
            'target_language' => $targetLanguage,
            'config' => $config->toArray()
        ]);

        try {
            // Vérifier cache pour traductions similaires
            $cacheKey = $this->generateCacheKey($segments, $targetLanguage, $config);
            if ($cachedResult = $this->cache->get($cacheKey)) {
                $this->logger->info('Translation cache hit', ['cache_key' => $cacheKey]);
                return $cachedResult;
            }

            // Construire prompt intelligent pour GPT-4o-mini
            $systemPrompt = $this->buildIntelligentPrompt($targetLanguage, $config);
            $userContent = $this->formatSegmentsForTranslation($segments);

            // Appel API GPT-4o-mini
            $response = $this->openAIClient->chat()->completions()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userContent]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.3, // Consistance élevée pour traduction
                'max_tokens' => $this->calculateOptimalTokens($segments)
            ]);

            // Parser et valider la réponse
            $translatedSegments = $this->parseAndValidateResponse(
                $response->choices[0]->message->content,
                $segments
            );

            // Mettre en cache le résultat
            $this->cache->set($cacheKey, $translatedSegments, 3600); // 1h TTL

            $this->logger->info('Translation completed successfully', [
                'original_segments' => count($segments),
                'translated_segments' => count($translatedSegments)
            ]);

            return $translatedSegments;

        } catch (\Exception $e) {
            $this->logger->error('Translation failed', [
                'error' => $e->getMessage(),
                'segments_count' => count($segments),
                'target_language' => $targetLanguage
            ]);

            throw new TranslationServiceException(
                'Translation API temporarily unavailable: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Construire prompt intelligent pour préservation timestamps + qualité contextuelle
     */
    private function buildIntelligentPrompt(string $targetLanguage, TranslationConfig $config): string
    {
        $languageNames = [
            'fr' => 'French',
            'es' => 'Spanish',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese'
        ];

        $targetLanguageName = $languageNames[$targetLanguage] ?? $targetLanguage;

        $prompt = "You are an expert translator specializing in dubbing and voice-over work for films, TV shows, and multimedia content.

CRITICAL MISSION: Translate English audio segments to {$targetLanguageName} while preserving perfect synchronization for dubbing.

CORE REQUIREMENTS:
1. PRESERVE EXACT TIMESTAMPS: Keep all startTime and endTime values unchanged
2. PRESERVE WORD-LEVEL DATA: Keep the original 'words' array untouched for dubbing synchronization
3. MAINTAIN NATURAL FLOW: Ensure translations sound natural when spoken aloud
4. ADAPT LENGTH: Adjust translation length to fit timing constraints for dubbing
5. PRESERVE EMOTION: Maintain emotional tone and intensity of the original

SPECIFIC INSTRUCTIONS:";

        // Contraintes temporelles
        if ($config->strictTiming) {
            $prompt .= "\n- STRICT TIMING: Prioritize fitting exact duration over literal accuracy";
            $prompt .= "\n- For segments <1s: Use very short translations (1-2 words max)";
            $prompt .= "\n- For segments >5s: Can use more elaborate expressions";
        }

        // Contexte émotionnel
        if (!empty($config->emotionalContext)) {
            $emotions = implode(', ', $config->emotionalContext);
            $prompt .= "\n- EMOTIONAL CONTEXT: The content contains these emotions: {$emotions}";
            $prompt .= "\n- Preserve and enhance emotional expression in {$targetLanguageName}";
        }

        // Noms de personnages
        if (!empty($config->characterNames)) {
            $characters = implode(', ', $config->characterNames);
            $prompt .= "\n- CHARACTER NAMES: Keep these names unchanged: {$characters}";
        }

        // Termes techniques
        if (!empty($config->technicalTerms)) {
            $terms = implode(', ', $config->technicalTerms);
            $prompt .= "\n- TECHNICAL TERMS: Translate appropriately: {$terms}";
        }

        // Format de sortie
        $prompt .= "\n\nOUTPUT FORMAT (JSON):
Return a JSON array with this EXACT structure for each segment:
[
    {
        \"id\": segment_id_number,
        \"text\": \"translated_text_in_{$targetLanguage}\",
        \"startTime\": original_start_time_unchanged,
        \"endTime\": original_end_time_unchanged,
        \"words\": original_words_array_unchanged,
        \"translation_notes\": \"brief_adaptation_explanation\"
    }
]

QUALITY CHECKLIST:
✓ Timestamps perfectly preserved
✓ Natural {$targetLanguageName} expression
✓ Dubbing-appropriate length
✓ Emotional tone maintained
✓ Character names preserved
✓ Technical accuracy ensured";

        return $prompt;
    }

    /**
     * Formater les segments pour envoi à GPT
     */
    private function formatSegmentsForTranslation(array $segments): string
    {
        $formatted = "SEGMENTS TO TRANSLATE:\n\n";
        
        foreach ($segments as $segment) {
            $duration = round($segment['endTime'] - $segment['startTime'], 2);
            $wordCount = isset($segment['words']) ? count($segment['words']) : str_word_count($segment['text']);
            
            $formatted .= "Segment {$segment['id']}:\n";
            $formatted .= "Text: \"{$segment['text']}\"\n";
            $formatted .= "Duration: {$duration}s ({$wordCount} words)\n";
            $formatted .= "Timing: {$segment['startTime']}s → {$segment['endTime']}s\n";
            
            if (isset($segment['words']) && !empty($segment['words'])) {
                $formatted .= "Word-level timing available for dubbing sync\n";
            }
            
            $formatted .= "\n";
        }

        return $formatted;
    }

    /**
     * Parser et valider réponse GPT-4o-mini
     */
    private function parseAndValidateResponse(string $jsonResponse, array $originalSegments): array
    {
        $decoded = json_decode($jsonResponse, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TranslationServiceException('Invalid JSON response from translation API');
        }

        if (!is_array($decoded)) {
            throw new TranslationServiceException('Expected array response from translation API');
        }

        $validated = [];
        foreach ($decoded as $index => $segment) {
            // Valider structure
            $required = ['id', 'text', 'startTime', 'endTime'];
            foreach ($required as $field) {
                if (!isset($segment[$field])) {
                    throw new TranslationServiceException("Missing required field: {$field}");
                }
            }

            // Valider préservation timestamps
            $original = $originalSegments[$index] ?? null;
            if ($original) {
                if (abs($segment['startTime'] - $original['startTime']) > 0.001) {
                    throw new TranslationServiceException('Timestamp preservation failed for startTime');
                }
                if (abs($segment['endTime'] - $original['endTime']) > 0.001) {
                    throw new TranslationServiceException('Timestamp preservation failed for endTime');
                }
                
                // Préserver word-level data original pour doublage
                $segment['words'] = $original['words'] ?? [];
            }

            $validated[] = $segment;
        }

        return $validated;
    }

    /**
     * Estimer coût de traduction basé sur les tokens
     */
    public function estimateCost(array $segments, string $targetLanguage): float
    {
        $totalText = '';
        foreach ($segments as $segment) {
            $totalText .= $segment['text'] . ' ';
        }

        // Estimation tokens (approximative: 1 token ≈ 4 caractères)
        $estimatedTokens = strlen($totalText) / 4;
        
        // Facteur prompt système + réponse JSON (approximatif x2.5)
        $totalTokens = $estimatedTokens * 2.5;

        // Prix GPT-4o-mini: $0.075/1M tokens
        return ($totalTokens / 1000000) * 0.075;
    }

    /**
     * Calculer tokens optimaux pour la requête
     */
    private function calculateOptimalTokens(array $segments): int
    {
        $baseTokens = 500; // Prompt système
        
        foreach ($segments as $segment) {
            $baseTokens += strlen($segment['text']) / 2; // Estimation généreuse
        }

        return min($baseTokens * 2, 4000); // Cap à 4k tokens
    }

    /**
     * Générer clé cache basée sur contenu + config
     */
    private function generateCacheKey(array $segments, string $targetLanguage, TranslationConfig $config): string
    {
        $content = '';
        foreach ($segments as $segment) {
            $content .= $segment['text'];
        }
        
        $configHash = md5(serialize($config->toArray()));
        return 'gpt_translation_' . md5($content . $targetLanguage . $configHash);
    }

    /**
     * Obtenir capacités du service
     */
    public function getCapabilities(): array
    {
        return [
            'preserves_word_timestamps' => true,
            'preserves_segment_timestamps' => true,
            'supported_languages' => ['fr', 'es', 'de', 'it', 'pt', 'nl', 'sv', 'no', 'da'],
            'supports_emotional_context' => true,
            'supports_character_names' => true,
            'supports_technical_terms' => true,
            'supports_timing_adaptation' => true,
            'supports_batch_processing' => true,
            'max_segments_per_batch' => 50,
            'cost_per_minute' => 0.008 // Estimation basée sur durée audio typique
        ];
    }
}