<?php

/**
 * API v2 - Créer une traduction
 * POST /api/v2/translations/create
 * 
 * Endpoint principal pour créer des traductions avec préservation timestamps
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Seules les requêtes POST sont acceptées
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../src/autoload.php';

use App\Services\Translation\TranslationServiceFactory;
use App\Services\Translation\TranslationCacheService;
use App\Services\Translation\DTO\TranslationConfig;
use App\Services\Translation\Exceptions\TranslationServiceException;
use Psr\Log\NullLogger;

// Simulation de logger (en production, utiliser un vrai logger)
$logger = new NullLogger();

try {
    // 1. Authentification et autorisation
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\\s+(.+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token d\'autorisation requis']);
        exit;
    }
    
    $token = $matches[1];
    
    // Décoder le token (simplifié pour la démo)
    $tokenData = json_decode(base64_decode($token), true);
    
    if (!$tokenData || !isset($tokenData['user_id']) || $tokenData['exp'] < time()) {
        http_response_code(401);
        echo json_encode(['error' => 'Token invalide ou expiré']);
        exit;
    }
    
    $userId = $tokenData['user_id'];

    // 2. Validation des données d'entrée
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Données JSON invalides']);
        exit;
    }

    // Paramètres requis
    $requiredFields = ['transcription_id', 'target_language'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Champ requis manquant: {$field}"]);
            exit;
        }
    }

    $transcriptionId = $input['transcription_id'];
    $targetLanguage = $input['target_language'];
    
    // Paramètres optionnels
    $translationProvider = $input['provider'] ?? 'auto'; // auto, gpt-4o-mini, whisper-1, hybrid
    $configOptions = $input['config'] ?? [];

    // 3. Validation de la langue cible
    $supportedLanguages = ['fr', 'es', 'de', 'it', 'pt', 'nl', 'sv', 'no', 'da', 'en'];
    if (!in_array($targetLanguage, $supportedLanguages)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Langue cible non supportée',
            'supported_languages' => $supportedLanguages
        ]);
        exit;
    }

    // 4. Récupérer la transcription source
    $dbPath = dirname(dirname(dirname(__DIR__))) . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        SELECT id, text, whisper_data, detected_language, has_word_timestamps, duration
        FROM transcriptions 
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute(['id' => $transcriptionId, 'user_id' => $userId]);
    $transcription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transcription) {
        http_response_code(404);
        echo json_encode(['error' => 'Transcription non trouvée']);
        exit;
    }

    // 5. Préparer segments pour traduction
    $whisperData = json_decode($transcription['whisper_data'], true);
    
    if (!$whisperData || empty($whisperData['words'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Transcription incompatible avec traduction',
            'details' => 'Word-level timestamps requis pour traduction de qualité'
        ]);
        exit;
    }

    // Créer segments intelligents à partir des word-level data
    $segments = createIntelligentSegmentsForTranslation($whisperData['words'], $transcription['duration']);

    // 6. Configuration de traduction
    $translationConfig = new TranslationConfig(
        preserveTimestamps: $configOptions['preserve_timestamps'] ?? true,
        strictTiming: $configOptions['strict_timing'] ?? true,
        emotionalContext: $configOptions['emotional_context'] ?? [],
        characterNames: $configOptions['character_names'] ?? [],
        technicalTerms: $configOptions['technical_terms'] ?? [],
        contentType: $configOptions['content_type'] ?? 'dialogue',
        adaptLengthForDubbing: $configOptions['adapt_length'] ?? true,
        translationStyle: $configOptions['style'] ?? 'natural'
    );

    // 7. Initialiser services de traduction
    $cache = new TranslationCacheService($logger);
    
    // Simulation OpenAI client (en production, utiliser le vrai client)
    $openAIClient = createMockOpenAIClient();
    
    $factory = new TranslationServiceFactory($openAIClient, $logger, $cache);
    
    // 8. Sélection automatique du service optimal
    if ($translationProvider === 'auto') {
        $translationService = $factory->createOptimalService($targetLanguage, $segments, $translationConfig);
    } else {
        $translationService = $factory->createService($translationProvider);
    }

    // 9. Estimer coût
    $estimatedCost = $translationService->estimateCost($segments, $targetLanguage);

    // 10. Effectuer la traduction
    $startTime = microtime(true);
    $translatedSegments = $translationService->translateSegments($segments, $targetLanguage, $translationConfig);
    $processingTime = microtime(true) - $startTime;

    // 11. Sauvegarder résultat en base
    $translationId = 'trans_' . uniqid();
    $translationData = [
        'id' => $translationId,
        'transcription_id' => $transcriptionId,
        'user_id' => $userId,
        'target_language' => $targetLanguage,
        'provider_used' => $translationService->getCapabilities()['service_name'] ?? 'unknown',
        'segments' => $translatedSegments,
        'config' => $translationConfig->toArray(),
        'estimated_cost' => $estimatedCost,
        'processing_time' => $processingTime,
        'quality_score' => calculateTranslationQuality($segments, $translatedSegments),
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Note: En production, sauvegarder dans table translations
    // Pour la démo, on retourne directement le résultat

    // 12. Réponse succès
    $response = [
        'success' => true,
        'data' => [
            'translation_id' => $translationId,
            'transcription_id' => $transcriptionId,
            'target_language' => $targetLanguage,
            'provider_used' => $translationData['provider_used'],
            'segments' => $translatedSegments,
            'metadata' => [
                'original_segments_count' => count($segments),
                'translated_segments_count' => count($translatedSegments),
                'estimated_cost_usd' => $estimatedCost,
                'processing_time_seconds' => round($processingTime, 3),
                'quality_score' => $translationData['quality_score'],
                'timestamp_preservation' => 'complete',
                'word_level_data_preserved' => true
            ],
            'capabilities' => $translationService->getCapabilities()
        ]
    ];

    echo json_encode($response);

} catch (TranslationServiceException $e) {
    http_response_code(422);
    echo json_encode([
        'error' => 'Erreur de service de traduction',
        'details' => $e->getMessage(),
        'code' => $e->getCode(),
        'context' => $e->getContext()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur interne du serveur',
        'details' => $e->getMessage()
    ]);
}

/**
 * Créer segments intelligents pour traduction à partir de word-level data
 */
function createIntelligentSegmentsForTranslation(array $words, float $duration): array
{
    $segments = [];
    $currentSegment = null;
    $segmentId = 0;
    
    foreach ($words as $word) {
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
            
            // Créer nouveau segment après ~8 secondes ou 20 mots pour traduction optimale
            $segmentDuration = $currentSegment['endTime'] - $currentSegment['startTime'];
            $wordCount = count($currentSegment['words']);
            
            if ($segmentDuration >= 8.0 || $wordCount >= 20) {
                $segments[] = $currentSegment;
                $segmentId++;
                $currentSegment = null;
            }
        }
    }
    
    // Ajouter le dernier segment
    if ($currentSegment !== null) {
        $segments[] = $currentSegment;
    }
    
    return $segments;
}

/**
 * Calculer score de qualité de traduction
 */
function calculateTranslationQuality(array $originalSegments, array $translatedSegments): float
{
    if (count($originalSegments) !== count($translatedSegments)) {
        return 0.5; // Pénalité si nombre de segments différent
    }
    
    $totalScore = 0;
    $segmentCount = count($originalSegments);
    
    foreach ($originalSegments as $index => $original) {
        $translated = $translatedSegments[$index] ?? null;
        if (!$translated) continue;
        
        $score = 1.0;
        
        // Vérifier préservation timestamps (critique)
        $timeDiff = abs(($translated['startTime'] ?? 0) - ($original['startTime'] ?? 0));
        if ($timeDiff > 0.1) { // Plus de 100ms de différence
            $score -= 0.3;
        }
        
        // Vérifier adaptation longueur (important pour doublage)
        $originalLength = strlen($original['text'] ?? '');
        $translatedLength = strlen($translated['text'] ?? '');
        $lengthRatio = $originalLength > 0 ? $translatedLength / $originalLength : 1;
        
        if ($lengthRatio < 0.7 || $lengthRatio > 1.4) { // Hors plage acceptable
            $score -= 0.2;
        }
        
        $totalScore += $score;
    }
    
    return $segmentCount > 0 ? $totalScore / $segmentCount : 0;
}

/**
 * Mock OpenAI client pour tests
 */
function createMockOpenAIClient()
{
    // En production, retourner le vrai client OpenAI
    // return new OpenAI\Client(OPENAI_API_KEY);
    
    // Pour la démo, retourner un mock
    return new class {
        public function chat() { return $this; }
        public function completions() { return $this; }
        public function create(array $params) {
            // Simulation de réponse GPT-4o-mini
            return (object) [
                'choices' => [
                    (object) [
                        'message' => (object) [
                            'content' => json_encode([
                                ['id' => 0, 'text' => 'Traduction simulée', 'startTime' => 0, 'endTime' => 2, 'translation_notes' => 'Mock translation']
                            ])
                        ]
                    ]
                ]
            ];
        }
    };
}