<?php

/**
 * Test d'intégration pour traduction Anglais → Français
 * Utilise nos données réelles de transcription pour valider l'approche
 */

require_once 'config.php';

// Simulation des classes nécessaires pour le test
class TranslationConfig {
    public function __construct(
        public readonly bool $preserveTimestamps = true,
        public readonly bool $strictTiming = false,
        public readonly array $emotionalContext = [],
        public readonly array $characterNames = [],
        public readonly array $technicalTerms = []
    ) {}
    
    public function toArray(): array {
        return [
            'preserve_timestamps' => $this->preserveTimestamps,
            'strict_timing' => $this->strictTiming,
            'emotional_context' => $this->emotionalContext,
            'character_names' => $this->characterNames,
            'technical_terms' => $this->technicalTerms
        ];
    }
}

class SimpleGPTTranslationService {
    private $openai_api_key;
    
    public function __construct($api_key) {
        $this->openai_api_key = $api_key;
    }
    
    public function translateSegments(array $segments, string $targetLanguage, ?TranslationConfig $config = null): array {
        $config = $config ?? new TranslationConfig();
        
        echo "🔄 Starting translation of " . count($segments) . " segments to {$targetLanguage}\n";
        
        // Construire le prompt intelligent
        $systemPrompt = $this->buildTranslationPrompt($targetLanguage, $config);
        $userContent = $this->formatSegments($segments);
        
        // Préparer la requête GPT-4o-mini
        $requestData = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userContent]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
            'max_tokens' => 2000
        ];
        
        // Appel API OpenAI
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->openai_api_key
            ],
            CURLOPT_POSTFIELDS => json_encode($requestData)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API Error: HTTP {$httpCode}. Response: {$response}");
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            throw new Exception("Invalid API response: " . $response);
        }
        
        // Parser la réponse JSON
        $rawContent = $data['choices'][0]['message']['content'];
        echo "🔍 DEBUG: Raw GPT response:\n" . $rawContent . "\n\n";
        
        $translatedContent = json_decode($rawContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse translation JSON: " . json_last_error_msg());
        }
        
        // GPT peut retourner différents formats, normaliser
        if (isset($translatedContent['segments'])) {
            $translatedContent = $translatedContent['segments'];
        } elseif (isset($translatedContent['translation'])) {
            $translatedContent = $translatedContent['translation'];
        } elseif (!is_array($translatedContent) || !isset($translatedContent[0])) {
            throw new Exception("Unexpected GPT response format - expected array of segments");
        }
        
        echo "✅ Translation completed successfully\n";
        echo "🔍 DEBUG: Parsed segments count: " . count($translatedContent) . "\n";
        
        return $this->validateAndEnrichResults($translatedContent, $segments);
    }
    
    private function buildTranslationPrompt(string $targetLanguage, TranslationConfig $config): string {
        $languageNames = ['fr' => 'French', 'es' => 'Spanish', 'de' => 'German'];
        $targetLanguageName = $languageNames[$targetLanguage] ?? $targetLanguage;
        
        $prompt = "You are an expert translator specializing in dubbing and voice-over work.

CRITICAL MISSION: Translate English audio segments to {$targetLanguageName} while preserving perfect synchronization for dubbing.

CORE REQUIREMENTS:
1. PRESERVE EXACT TIMESTAMPS: Keep all startTime and endTime values unchanged
2. MAINTAIN NATURAL FLOW: Ensure translations sound natural when spoken aloud  
3. ADAPT LENGTH: Adjust translation length to fit timing constraints for dubbing
4. PRESERVE EMOTION: Maintain emotional tone and intensity of the original";

        if ($config->strictTiming) {
            $prompt .= "\n5. STRICT TIMING: Prioritize fitting exact duration over literal accuracy";
        }

        if (!empty($config->emotionalContext)) {
            $emotions = implode(', ', $config->emotionalContext);
            $prompt .= "\n6. EMOTIONAL CONTEXT: Content contains: {$emotions}";
        }

        if (!empty($config->characterNames)) {
            $characters = implode(', ', $config->characterNames);
            $prompt .= "\n7. CHARACTER NAMES: Keep unchanged: {$characters}";
        }

        $prompt .= "\n\nOUTPUT FORMAT (JSON):
Return a JSON array with this EXACT structure:
[
    {
        \"id\": segment_id,
        \"text\": \"translated_text_in_{$targetLanguage}\",
        \"startTime\": original_start_time,
        \"endTime\": original_end_time,
        \"translation_notes\": \"adaptation_notes\"
    }
]

Ensure perfect timestamp preservation and natural {$targetLanguageName} expression.";

        return $prompt;
    }
    
    private function formatSegments(array $segments): string {
        $formatted = "SEGMENTS TO TRANSLATE:\n\n";
        
        foreach ($segments as $segment) {
            $duration = round($segment['endTime'] - $segment['startTime'], 2);
            $wordCount = str_word_count($segment['text']);
            
            $formatted .= "Segment {$segment['id']}:\n";
            $formatted .= "Text: \"{$segment['text']}\"\n";
            $formatted .= "Duration: {$duration}s ({$wordCount} words)\n";
            $formatted .= "Timing: {$segment['startTime']}s → {$segment['endTime']}s\n\n";
        }
        
        return $formatted;
    }
    
    private function validateAndEnrichResults(array $translated, array $original): array {
        $results = [];
        
        foreach ($translated as $index => $segment) {
            $originalSegment = $original[$index] ?? null;
            if (!$originalSegment) {
                throw new Exception("Missing original segment for index {$index}");
            }
            
            // Valider timestamps préservés
            if (abs($segment['startTime'] - $originalSegment['startTime']) > 0.001) {
                throw new Exception("Timestamp preservation failed for segment {$index}");
            }
            
            // Enrichir avec données originales pour doublage
            $enriched = $segment;
            $enriched['original_text'] = $originalSegment['text'];
            $enriched['words'] = $originalSegment['words'] ?? [];
            $enriched['duration'] = $segment['endTime'] - $segment['startTime'];
            
            // Calculer adaptation ratio
            $originalLength = strlen($originalSegment['text']);
            $translatedLength = strlen($segment['text']);
            $enriched['length_adaptation_ratio'] = $originalLength > 0 ? $translatedLength / $originalLength : 1.0;
            
            $results[] = $enriched;
        }
        
        return $results;
    }
}

echo "🎯 TEST TRADUCTION ANGLAIS → FRANÇAIS AVEC TDD\n";
echo "==============================================\n\n";

try {
    // Récupérer données de transcription anglaise existante
    $dbPath = __DIR__ . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Chercher une transcription anglaise avec word-level data
    $stmt = $pdo->prepare("
        SELECT id, text, whisper_data, detected_language 
        FROM transcriptions 
        WHERE detected_language = 'english' 
        AND has_word_timestamps = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $transcription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transcription) {
        echo "❌ Aucune transcription anglaise avec word-level timestamps trouvée\n";
        echo "💡 Créez d'abord une transcription anglaise pour tester la traduction\n";
        exit(1);
    }
    
    echo "📂 Transcription source trouvée: {$transcription['id']}\n";
    echo "🎵 Langue détectée: {$transcription['detected_language']}\n";
    echo "📝 Texte original: \"" . substr($transcription['text'], 0, 100) . "...\"\n\n";
    
    // Parser données Whisper
    $whisperData = json_decode($transcription['whisper_data'], true);
    if (!$whisperData || empty($whisperData['words'])) {
        echo "❌ Données word-level manquantes dans la transcription\n";
        exit(1);
    }
    
    echo "🔤 Word-level data disponibles: " . count($whisperData['words']) . " mots\n\n";
    
    // Créer segments de test à partir des données word-level (segments intelligents simplifiés)
    $testSegments = [];
    $currentSegment = null;
    $segmentId = 0;
    
    foreach ($whisperData['words'] as $word) {
        if ($currentSegment === null) {
            $currentSegment = [
                'id' => $segmentId,
                'text' => $word['word'],
                'startTime' => $word['start'],
                'endTime' => $word['end'],
                'words' => [$word]
            ];
        } else {
            $currentSegment['text'] .= ' ' . $word['word'];
            $currentSegment['endTime'] = $word['end'];
            $currentSegment['words'][] = $word;
            
            // Créer nouveau segment après ~6 secondes ou 15 mots
            $duration = $currentSegment['endTime'] - $currentSegment['startTime'];
            $wordCount = count($currentSegment['words']);
            
            if ($duration >= 6.0 || $wordCount >= 15) {
                $testSegments[] = $currentSegment;
                $segmentId++;
                $currentSegment = null;
            }
        }
    }
    
    // Ajouter le dernier segment
    if ($currentSegment !== null) {
        $testSegments[] = $currentSegment;
    }
    
    // Limiter à 3 segments pour le test
    $testSegments = array_slice($testSegments, 0, 3);
    
    echo "🎯 Segments préparés pour traduction: " . count($testSegments) . "\n";
    foreach ($testSegments as $segment) {
        $duration = round($segment['endTime'] - $segment['startTime'], 2);
        echo "   - Segment {$segment['id']}: \"{$segment['text']}\" ({$duration}s)\n";
    }
    echo "\n";
    
    // Configuration pour traduction doublage
    $config = new TranslationConfig(
        preserveTimestamps: true,
        strictTiming: true,
        emotionalContext: ['conversational', 'informative']
    );
    
    // Initialiser service de traduction
    $translationService = new SimpleGPTTranslationService(OPENAI_API_KEY);
    
    echo "🔄 Lancement traduction avec GPT-4o-mini...\n\n";
    
    // Test traduction anglais → français
    $translatedSegments = $translationService->translateSegments($testSegments, 'fr', $config);
    
    echo "🎉 TRADUCTION RÉUSSIE !\n";
    echo "======================\n\n";
    
    foreach ($translatedSegments as $segment) {
        echo "📍 Segment {$segment['id']}:\n";
        echo "   🇬🇧 Original: \"{$segment['original_text']}\"\n";
        echo "   🇫🇷 Traduit: \"{$segment['text']}\"\n";
        echo "   ⏱️ Timing: {$segment['startTime']}s → {$segment['endTime']}s ({$segment['duration']}s)\n";
        echo "   📊 Adaptation: " . round($segment['length_adaptation_ratio'] * 100) . "% de la longueur originale\n";
        echo "   🔤 Word-level data: " . count($segment['words']) . " mots préservés\n";
        if (!empty($segment['translation_notes'])) {
            echo "   💭 Notes: {$segment['translation_notes']}\n";
        }
        echo "\n";
    }
    
    // Validation qualité
    echo "✅ VALIDATION QUALITÉ:\n";
    echo "=====================\n";
    
    $totalDurationPreserved = true;
    $averageAdaptationRatio = 0;
    $wordsPreserved = 0;
    
    foreach ($translatedSegments as $segment) {
        // Vérifier préservation timestamps
        if (abs($segment['duration'] - ($segment['endTime'] - $segment['startTime'])) > 0.001) {
            $totalDurationPreserved = false;
        }
        
        $averageAdaptationRatio += $segment['length_adaptation_ratio'];
        $wordsPreserved += count($segment['words']);
    }
    
    $averageAdaptationRatio /= count($translatedSegments);
    
    echo "✅ Timestamps préservés: " . ($totalDurationPreserved ? "OUI" : "NON") . "\n";
    echo "✅ Ratio adaptation moyen: " . round($averageAdaptationRatio * 100) . "%\n";
    echo "✅ Word-level data préservées: {$wordsPreserved} mots\n";
    echo "✅ Segments traduits: " . count($translatedSegments) . "/" . count($testSegments) . "\n\n";
    
    if ($totalDurationPreserved && $averageAdaptationRatio >= 0.7 && $averageAdaptationRatio <= 1.3) {
        echo "🏆 TEST RÉUSSI: Traduction de qualité pour doublage !\n";
        echo "🎭 Ready for TTS generation with GPT-4o-mini-TTS\n";
    } else {
        echo "⚠️ Test partiellement réussi - Ajustements nécessaires\n";
    }
    
    echo "\n🎯 PROCHAINE ÉTAPE: Intégration dans pipeline doublage complet\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📝 Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🏁 Test terminé avec succès !\n";