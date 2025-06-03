<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Api\v2\Controller;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;
use Infrastructure\Http\Api\v2\Controller\BaseApiController;
use PDO;
use Exception;
use Infrastructure\Http\Api\v2\Middleware\CacheMiddleware;

/**
 * Contrôleur API v2 pour les traductions
 */
class TranslationApiController extends BaseApiController
{
    private PDO $pdo;

    public function __construct()
    {
        parent::__construct();
        
        // Connexion base de données simple
        $this->pdo = new PDO("sqlite:" . __DIR__ . "/../../../../../../database/transcription.db");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * GET /api/v2/translations/capabilities
     * Retourne les capacités du système de traduction
     */
    public function getCapabilities(ApiRequest $request): ApiResponse
    {
        try {
            $capabilities = [
                'supported_languages' => [
                    'fr' => [
                        'name' => 'Français',
                        'quality' => 'Excellent',
                        'optimal_providers' => ['gpt-4o-mini', 'hybrid'],
                        'specializations' => ['doublage', 'cinéma', 'technique']
                    ],
                    'es' => [
                        'name' => 'Español',
                        'quality' => 'Excellent', 
                        'optimal_providers' => ['gpt-4o-mini', 'hybrid'],
                        'specializations' => ['doublage', 'cinéma', 'actualités']
                    ],
                    'de' => [
                        'name' => 'Deutsch',
                        'quality' => 'Excellent',
                        'optimal_providers' => ['gpt-4o-mini', 'hybrid'],
                        'specializations' => ['technique', 'business', 'doublage']
                    ],
                    'it' => [
                        'name' => 'Italiano',
                        'quality' => 'Très bon',
                        'optimal_providers' => ['gpt-4o-mini'],
                        'specializations' => ['doublage', 'arts', 'cuisine']
                    ],
                    'pt' => [
                        'name' => 'Português',
                        'quality' => 'Très bon',
                        'optimal_providers' => ['gpt-4o-mini'],
                        'specializations' => ['doublage', 'business', 'technique']
                    ]
                ],
                'providers' => [
                    'gpt-4o-mini' => [
                        'name' => 'GPT-4o Mini',
                        'description' => 'IA avancée avec préservation émotions et timing précis',
                        'cost_per_minute' => 0.008,
                        'quality_level' => 'Premium (90%+)',
                        'supports_dubbing' => true,
                        'supports_emotions' => true,
                        'max_segments_per_request' => 100
                    ],
                    'hybrid' => [
                        'name' => 'Service Hybride',
                        'description' => 'Fiabilité 99.9% avec fallbacks automatiques',
                        'cost_per_minute' => 0.009,
                        'quality_level' => 'Maximum (95%+)',
                        'supports_dubbing' => true,
                        'supports_emotions' => true,
                        'max_segments_per_request' => 100
                    ],
                    'whisper-1' => [
                        'name' => 'Whisper-1',
                        'description' => 'OpenAI natif, optimisé pour l\'anglais',
                        'cost_per_minute' => 0.006,
                        'quality_level' => 'Standard (80%+)',
                        'supports_dubbing' => false,
                        'supports_emotions' => false,
                        'max_segments_per_request' => 50
                    ]
                ],
                'features' => [
                    'timestamp_preservation' => true,
                    'emotion_preservation' => true,
                    'dubbing_optimization' => true,
                    'cache_system' => true,
                    'batch_processing' => true,
                    'export_formats' => ['json', 'srt', 'vtt', 'txt', 'dubbing_json']
                ]
            ];

            $response = new ApiResponse($capabilities, 200);
            CacheMiddleware::cacheResponse($request, $response);
            return $response;
            
        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v2/translations/create
     * Créer une nouvelle traduction
     */
    public function createTranslation(ApiRequest $request): ApiResponse
    {
        try {
            $data = $request->getJsonBody();
            
            // Validation des données requises
            if (empty($data['transcription_id'])) {
                return new ApiResponse(['error' => 'transcription_id requis'], 400);
            }
            
            if (empty($data['target_language'])) {
                return new ApiResponse(['error' => 'target_language requis'], 400);
            }

            // Valeurs par défaut
            $provider = $data['provider'] ?? 'gpt-4o-mini';
            $config = $data['config'] ?? [];

            // Vérifier que la transcription existe
            $transcription = $this->getTranscriptionById($data['transcription_id']);
            if (!$transcription) {
                return new ApiResponse(['error' => 'Transcription non trouvée'], 404);
            }

            // Générer un ID unique pour la traduction
            $translationId = 'trans_' . uniqid() . '_' . time();

            // Créer le projet de traduction en base
            $this->createTranslationProject($translationId, $transcription, $data['target_language'], $provider, $config);

            // Réponse de succès avec données de création
            $response = [
                'success' => true,
                'data' => [
                    'translation_id' => $translationId,
                    'status' => 'pending',
                    'estimated_cost' => $this->estimateCost($transcription, $provider),
                    'estimated_processing_time' => $this->estimateProcessingTime($transcription),
                    'created_at' => date('c')
                ]
            ];
            
            // Option : Lancer le traitement immédiatement en arrière-plan
            if (!empty($data['process_immediately'])) {
                $this->triggerBackgroundProcessing($translationId);
            }

            return new ApiResponse($response, 201);

        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/v2/translations/status/{id}
     * Obtenir le statut d'une traduction
     */
    public function getTranslationStatus(string $translationId): ApiResponse
    {
        try {
            $translation = $this->getTranslationProjectById($translationId);
            
            if (!$translation) {
                return new ApiResponse(['error' => 'Traduction non trouvée'], 404);
            }

            return new ApiResponse(['success' => true, 'data' => $translation], 200);

        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v2/translations/list
     * Lister les traductions de l'utilisateur
     */
    public function listTranslations(ApiRequest $request): ApiResponse
    {
        try {
            // Récupérer l'utilisateur (mock pour test)
            $userId = 'default_user';
            
            // Paramètres de requête
            $params = $request->getQueryParams();
            $limit = min((int)($params['limit'] ?? 20), 100);
            $offset = max((int)($params['offset'] ?? 0), 0);

            $translations = $this->getTranslationsByUser($userId, $limit, $offset);

            $total = $this->countTranslationsByUser($userId);
            
            $response = new ApiResponse([
                'success' => true,
                'data' => [
                    'translations' => $translations,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'total' => $total
                    ],
                    'statistics' => [
                        'total_translations' => $total,
                        'success_rate' => $this->calculateSuccessRate($userId),
                        'average_cost' => $this->calculateAverageCost($userId),
                        'average_quality' => $this->calculateAverageQuality($userId),
                        'favorite_language' => $this->getFavoriteLanguage($userId),
                        'favorite_provider' => $this->getFavoriteProvider($userId)
                    ]
                ]
            ], 200);
            
            CacheMiddleware::cacheResponse($request, $response);
            return $response;

        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v2/translations/estimate
     * Estimer le coût d'une traduction
     */
    public function estimateTranslationCost(ApiRequest $request): ApiResponse
    {
        try {
            $data = $request->getJsonBody();
            
            if (empty($data['transcription_id']) || empty($data['target_language'])) {
                return new ApiResponse(['error' => 'transcription_id et target_language requis'], 400);
            }

            $provider = $data['provider'] ?? 'gpt-4o-mini';
            
            $transcription = $this->getTranscriptionById($data['transcription_id']);
            if (!$transcription) {
                return new ApiResponse(['error' => 'Transcription non trouvée'], 404);
            }

            $estimate = [
                'estimated_cost' => $this->estimateCost($transcription, $provider),
                'estimated_processing_time' => $this->estimateProcessingTime($transcription),
                'quality_estimate' => $this->estimateQuality($provider, $data['target_language']),
                'recommended_provider' => $this->getRecommendedProvider($data['target_language']),
                'segments_count' => count(json_decode($transcription['whisper_data'] ?? '{}', true)['segments'] ?? [])
            ];

            return new ApiResponse(['success' => true, 'data' => $estimate], 200);

        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 400);
        }
    }

    // Méthodes utilitaires privées

    private function getTranscriptionById(string $transcriptionId): ?array
    {
        $sql = "SELECT * FROM transcriptions WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$transcriptionId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function createTranslationProject(
        string $translationId, 
        array $transcription, 
        string $targetLanguage, 
        string $provider, 
        array $config
    ): void {
        // Calculer les métriques depuis la transcription
        $whisperData = json_decode($transcription['whisper_data'] ?? '{}', true);
        $segments = $whisperData['segments'] ?? [];
        $segmentsCount = count($segments);
        $totalDuration = (float)($transcription['duration'] ?? 0);
        $estimatedCost = $this->estimateCost($transcription, $provider);
        
        // Déterminer les capacités selon la configuration
        $hasWordTimestamps = !empty($config['preserve_word_timestamps']);
        $hasEmotionalContext = !empty($config['preserve_emotions']);
        
        $sql = "INSERT INTO translation_projects 
                (id, user_id, transcription_id, target_language, source_language, provider_used, 
                 config_json, status, priority, estimated_cost, segments_count, total_duration_seconds,
                 has_word_timestamps, has_emotional_context, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $translationId,
            'default_user',
            $transcription['id'],
            $targetLanguage,
            $transcription['language'] ?? 'en',
            $provider,
            json_encode($config),
            3, // priority par défaut
            $estimatedCost,
            $segmentsCount,
            $totalDuration,
            $hasWordTimestamps ? 1 : 0,
            $hasEmotionalContext ? 1 : 0
        ]);
    }

    private function getTranslationProjectById(string $translationId): ?array
    {
        $sql = "SELECT 
                    tp.*,
                    t.duration,
                    t.whisper_data,
                    t.word_count,
                    CASE 
                        WHEN t.whisper_data IS NOT NULL AND t.whisper_data != '' 
                        THEN json_array_length(json_extract(t.whisper_data, '$.segments'))
                        ELSE 0 
                    END as computed_segments_count
                FROM translation_projects tp
                LEFT JOIN transcriptions t ON tp.transcription_id = t.id
                WHERE tp.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$translationId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) return null;
        
        // Utiliser segments_count de la table ou calculer depuis segments_json
        $segmentsCount = $result['segments_count'] ?? $result['computed_segments_count'] ?? 0;
        
        // Utiliser total_duration_seconds de la table ou duration de transcriptions
        $totalDuration = $result['total_duration_seconds'] ?? $result['duration'] ?? 0;
        
        return [
            'id' => $result['id'],
            'user_id' => $result['user_id'],
            'transcription_id' => $result['transcription_id'],
            'target_language' => $result['target_language'],
            'source_language' => $result['source_language'] ?? 'en',
            'provider_used' => $result['provider_used'],
            'status' => $result['status'],
            'priority' => (int)($result['priority'] ?? 3),
            
            // Métriques avec mapping correct des noms
            'segments_count' => (int)$segmentsCount,
            'total_duration' => (float)$totalDuration,
            'quality_score' => (float)($result['quality_score'] ?? 0),
            'processing_time' => (float)($result['processing_time_seconds'] ?? 0),
            'estimated_cost' => (float)($result['estimated_cost'] ?? 0),
            'actual_cost' => isset($result['actual_cost']) ? (float)$result['actual_cost'] : null,
            
            // Capacités avancées
            'has_word_timestamps' => (bool)($result['has_word_timestamps'] ?? false),
            'has_emotional_context' => (bool)($result['has_emotional_context'] ?? false),
            'has_character_names' => (bool)($result['has_character_names'] ?? false),
            'has_technical_terms' => (bool)($result['has_technical_terms'] ?? false),
            
            // Flags de traitement
            'immediate_processing' => (bool)($result['immediate_processing'] ?? false),
            
            // Timestamps
            'created_at' => $result['created_at'],
            'updated_at' => $result['updated_at'],
            'started_at' => $result['started_at'],
            'completed_at' => $result['completed_at'],
            
            // Configuration (si disponible)
            'config_json' => $result['config_json']
        ];
    }

    private function getTranslationsByUser(string $userId, int $limit, int $offset): array
    {
        $sql = "SELECT 
                    tp.*,
                    t.duration,
                    t.whisper_data,
                    t.word_count,
                    CASE 
                        WHEN t.whisper_data IS NOT NULL AND t.whisper_data != '' 
                        THEN json_array_length(json_extract(t.whisper_data, '$.segments'))
                        ELSE 0 
                    END as computed_segments_count
                FROM translation_projects tp
                LEFT JOIN transcriptions t ON tp.transcription_id = t.id
                WHERE tp.user_id = ?
                ORDER BY tp.created_at DESC 
                LIMIT ? OFFSET ?";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        
        $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapper et enrichir les données avec les bons noms de champs
        return array_map(function($translation) {
            // Utiliser segments_count de la table ou calculer depuis segments_json
            $segmentsCount = $translation['segments_count'] ?? $translation['computed_segments_count'] ?? 0;
            
            // Utiliser total_duration_seconds de la table ou duration de transcriptions
            $totalDuration = $translation['total_duration_seconds'] ?? $translation['duration'] ?? 0;
            
            return [
                'id' => $translation['id'],
                'user_id' => $translation['user_id'],
                'transcription_id' => $translation['transcription_id'],
                'target_language' => $translation['target_language'],
                'source_language' => $translation['source_language'] ?? 'en',
                'provider_used' => $translation['provider_used'],
                'status' => $translation['status'],
                'priority' => (int)($translation['priority'] ?? 3),
                
                // Métriques avec mapping correct des noms
                'segments_count' => (int)$segmentsCount,
                'total_duration' => (float)$totalDuration,
                'quality_score' => (float)($translation['quality_score'] ?? 0),
                'processing_time' => (float)($translation['processing_time_seconds'] ?? 0),
                'estimated_cost' => (float)($translation['estimated_cost'] ?? 0),
                'actual_cost' => isset($translation['actual_cost']) ? (float)$translation['actual_cost'] : null,
                
                // Capacités avancées
                'has_word_timestamps' => (bool)($translation['has_word_timestamps'] ?? false),
                'has_emotional_context' => (bool)($translation['has_emotional_context'] ?? false),
                'has_character_names' => (bool)($translation['has_character_names'] ?? false),
                'has_technical_terms' => (bool)($translation['has_technical_terms'] ?? false),
                
                // Flags de traitement
                'immediate_processing' => (bool)($translation['immediate_processing'] ?? false),
                
                // Timestamps
                'created_at' => $translation['created_at'],
                'updated_at' => $translation['updated_at'],
                'started_at' => $translation['started_at'],
                'completed_at' => $translation['completed_at']
            ];
        }, $translations);
    }

    private function countTranslationsByUser(string $userId): int
    {
        $sql = "SELECT COUNT(*) FROM translation_projects WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        return (int)$stmt->fetchColumn();
    }

    private function estimateCost(array $transcription, string $provider): float
    {
        $duration = $transcription['duration'] ?? 0;
        $costPerMinute = [
            'gpt-4o-mini' => 0.008,
            'hybrid' => 0.009,
            'whisper-1' => 0.006
        ];
        
        return ($duration / 60) * ($costPerMinute[$provider] ?? 0.008);
    }

    private function estimateProcessingTime(array $transcription): float
    {
        $whisperData = json_decode($transcription['whisper_data'] ?? '{}', true);
        $segments = $whisperData['segments'] ?? [];
        $segmentCount = count($segments);
        return max(1.0, $segmentCount * 0.3); // ~0.3s par segment
    }

    private function estimateQuality(string $provider, string $targetLanguage): float
    {
        $qualityMap = [
            'gpt-4o-mini' => ['fr' => 0.92, 'es' => 0.91, 'de' => 0.90, 'it' => 0.88, 'pt' => 0.87],
            'hybrid' => ['fr' => 0.95, 'es' => 0.94, 'de' => 0.93, 'it' => 0.91, 'pt' => 0.90],
            'whisper-1' => ['fr' => 0.82, 'es' => 0.81, 'de' => 0.79, 'it' => 0.78, 'pt' => 0.77]
        ];
        
        return $qualityMap[$provider][$targetLanguage] ?? 0.85;
    }

    private function getRecommendedProvider(string $targetLanguage): string
    {
        $recommendations = [
            'fr' => 'gpt-4o-mini',
            'es' => 'gpt-4o-mini', 
            'de' => 'hybrid',
            'it' => 'gpt-4o-mini',
            'pt' => 'gpt-4o-mini'
        ];
        
        return $recommendations[$targetLanguage] ?? 'gpt-4o-mini';
    }
    
    /**
     * Déclencher le traitement en arrière-plan
     */
    private function triggerBackgroundProcessing(string $translationId): void
    {
        // Option 1 : Via exec() - Simple mais bloquant
        // exec("php " . __DIR__ . "/../../../../../../../process_translations_simple.php > /dev/null 2>&1 &");
        
        // Option 2 : Via cURL asynchrone
        $ch = curl_init('http://localhost:8000/api/v2/translations/process');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['translation_id' => $translationId]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500); // Timeout rapide
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Exécuter sans attendre la réponse
        curl_exec($ch);
        curl_close($ch);
    }
    
    /**
     * GET /api/v2/translations/download/{id}
     * Télécharger une traduction dans un format spécifique
     */
    public function downloadTranslation(string $translationId, ApiRequest $request): ApiResponse
    {
        try {
            $format = $request->getQuery('format', 'json');
            
            // Récupérer la traduction
            $translation = $this->getTranslationProjectById($translationId);
            if (!$translation || $translation['status'] !== 'completed') {
                return new ApiResponse(['error' => 'Traduction non trouvée ou non terminée'], 404);
            }
            
            // Récupérer la version active
            $sql = "SELECT segments_json FROM translation_versions 
                    WHERE project_id = ? AND is_active = 1 
                    ORDER BY version_number DESC LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$translationId]);
            $version = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$version) {
                return new ApiResponse(['error' => 'Aucune version disponible'], 404);
            }
            
            $segments = json_decode($version['segments_json'], true);
            
            // Générer le contenu selon le format
            $content = $this->generateExportContent($segments, $format, $translation);
            
            // Pour le téléchargement direct, on doit bypasser ApiResponse
            // car il attend un array et non une string
            http_response_code(200);
            header('Content-Type: ' . $this->getContentType($format));
            header('Content-Disposition: attachment; filename="translation_' . $translationId . '.' . $this->getFileExtension($format) . '"');
            header('Content-Length: ' . strlen($content));
            
            echo $content;
            exit;
            
        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    private function generateExportContent(array $segments, string $format, array $translation): string
    {
        switch ($format) {
            case 'json':
                return json_encode([
                    'translation_id' => $translation['id'],
                    'target_language' => $translation['target_language'],
                    'segments' => $segments,
                    'metadata' => [
                        'quality_score' => $translation['quality_score'],
                        'processing_time' => $translation['processing_time'],
                        'provider' => $translation['provider_used']
                    ]
                ], JSON_PRETTY_PRINT);
                
            case 'srt':
                $srt = '';
                foreach ($segments as $i => $segment) {
                    $srt .= ($i + 1) . "\n";
                    $srt .= $this->formatSrtTime($segment['start'] ?? 0) . ' --> ' . $this->formatSrtTime($segment['end'] ?? 0) . "\n";
                    $srt .= $segment['text'] . "\n\n";
                }
                return $srt;
                
            case 'vtt':
                $vtt = "WEBVTT\n\n";
                foreach ($segments as $segment) {
                    $vtt .= $this->formatVttTime($segment['start'] ?? 0) . ' --> ' . $this->formatVttTime($segment['end'] ?? 0) . "\n";
                    $vtt .= $segment['text'] . "\n\n";
                }
                return $vtt;
                
            case 'txt':
                $text = '';
                foreach ($segments as $segment) {
                    $text .= $segment['text'] . ' ';
                }
                return trim($text);
                
            case 'dubbing_json':
                return json_encode([
                    'version' => '1.0',
                    'translation_id' => $translation['id'],
                    'language' => $translation['target_language'],
                    'segments' => array_map(function($segment) {
                        return [
                            'id' => $segment['id'] ?? 0,
                            'start' => $segment['start'] ?? 0,
                            'end' => $segment['end'] ?? 0,
                            'text' => $segment['text'],
                            'words' => $segment['words'] ?? [],
                            'confidence' => $segment['confidence'] ?? 1.0
                        ];
                    }, $segments)
                ], JSON_PRETTY_PRINT);
                
            default:
                return json_encode($segments);
        }
    }
    
    private function formatSrtTime(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        $millis = ($seconds - floor($seconds)) * 1000;
        
        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $secs, $millis);
    }
    
    private function formatVttTime(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        $millis = ($seconds - floor($seconds)) * 1000;
        
        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $secs, $millis);
    }
    
    private function getContentType(string $format): string
    {
        $types = [
            'json' => 'application/json',
            'srt' => 'text/srt',
            'vtt' => 'text/vtt',
            'txt' => 'text/plain',
            'dubbing_json' => 'application/json'
        ];
        
        return $types[$format] ?? 'application/octet-stream';
    }
    
    private function getFileExtension(string $format): string
    {
        return $format === 'dubbing_json' ? 'json' : $format;
    }

    private function calculateSuccessRate(string $userId): float
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM translation_projects WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['total'] == 0) return 0.0;
        
        return round(($result['completed'] / $result['total']) * 100, 1);
    }

    private function calculateAverageCost(string $userId): float
    {
        $sql = "SELECT AVG(actual_cost) as avg_cost FROM translation_projects 
                WHERE user_id = ? AND actual_cost IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetchColumn();
        return round($result ?: 0.0, 4);
    }

    private function calculateAverageQuality(string $userId): float
    {
        $sql = "SELECT AVG(quality_score) as avg_quality FROM translation_projects 
                WHERE user_id = ? AND quality_score IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetchColumn();
        return round(($result ?: 0.0) * 100, 1);
    }

    private function getFavoriteLanguage(string $userId): string
    {
        $sql = "SELECT target_language, COUNT(*) as count FROM translation_projects 
                WHERE user_id = ? GROUP BY target_language ORDER BY count DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetchColumn();
        return $result ?: 'fr';
    }

    private function getFavoriteProvider(string $userId): string
    {
        $sql = "SELECT provider_used, COUNT(*) as count FROM translation_projects 
                WHERE user_id = ? GROUP BY provider_used ORDER BY count DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetchColumn();
        return $result ?: 'gpt-4o-mini';
    }

    /**
     * POST /api/v2/translations/stop/{id}
     * Arrêter une traduction en cours
     */
    public function stopTranslation(string $translationId): ApiResponse
    {
        try {
            // Récupérer la traduction
            $translation = $this->getTranslationProjectById($translationId);
            
            if (!$translation) {
                return new ApiResponse(['error' => 'Traduction non trouvée'], 404);
            }
            
            // Vérifier que la traduction est en cours ou en attente
            if (!in_array($translation['status'], ['pending', 'processing'])) {
                return new ApiResponse(['error' => 'La traduction ne peut pas être arrêtée'], 400);
            }
            
            // Marquer comme annulée
            $sql = "UPDATE translation_projects 
                    SET status = 'cancelled', 
                        updated_at = datetime('now'),
                        completed_at = datetime('now')
                    WHERE id = ?";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$translationId]);
            
            // Enregistrer l'événement d'annulation
            try {
                $errorSql = "INSERT INTO translation_errors 
                            (project_id, user_id, error_type, error_message, created_at)
                            VALUES (?, ?, 'user_cancelled', 'Traduction annulée par l''utilisateur', datetime('now'))";
                $errorStmt = $this->pdo->prepare($errorSql);
                $errorStmt->execute([
                    $translationId,
                    $translation['user_id']
                ]);
            } catch (Exception $e) {
                // Ignorer si la table n'existe pas
            }
            
            return new ApiResponse([
                'success' => true,
                'message' => 'Traduction arrêtée avec succès',
                'translation_id' => $translationId
            ], 200);
            
        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/v2/translations/{id}
     * Supprimer une traduction et toutes ses données associées
     */
    public function deleteTranslation(string $translationId): ApiResponse
    {
        try {
            // Récupérer la traduction
            $translation = $this->getTranslationProjectById($translationId);
            
            if (!$translation) {
                return new ApiResponse(['error' => 'Traduction non trouvée'], 404);
            }
            
            // Commencer une transaction pour assurer l'intégrité
            $this->pdo->beginTransaction();
            
            try {
                // Supprimer les versions
                $sql = "DELETE FROM translation_versions WHERE project_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$translationId]);
                
                // Supprimer les erreurs
                $sql = "DELETE FROM translation_errors WHERE project_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$translationId]);
                
                // Supprimer le projet de traduction
                $sql = "DELETE FROM translation_projects WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$translationId]);
                
                // Valider la transaction
                $this->pdo->commit();
                
                return new ApiResponse([
                    'success' => true,
                    'message' => 'Traduction supprimée avec succès',
                    'translation_id' => $translationId
                ], 200);
                
            } catch (Exception $e) {
                // Annuler la transaction en cas d'erreur
                $this->pdo->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v2/translations/process
     * Démarrer le traitement immédiat d'une traduction
     */
    public function processTranslation(ApiRequest $request): ApiResponse
    {
        try {
            $data = $request->getJsonBody();
            $translationId = $data['translation_id'] ?? null;
            
            if (!$translationId) {
                return new ApiResponse(['error' => 'translation_id requis'], 400);
            }
            
            // Vérifier que la traduction existe et est en attente
            $translation = $this->getTranslationProjectById($translationId);
            if (!$translation) {
                return new ApiResponse(['error' => 'Traduction non trouvée'], 404);
            }
            
            if ($translation['status'] !== 'pending') {
                return new ApiResponse(['error' => 'Traduction déjà traitée ou en cours'], 400);
            }
            
            // Marquer comme traitement immédiat
            $updateSql = "UPDATE translation_projects 
                         SET immediate_processing = 1, 
                             updated_at = datetime('now')
                         WHERE id = ?";
            $updateStmt = $this->pdo->prepare($updateSql);
            $updateStmt->execute([$translationId]);
            
            // Lancer le worker batch en arrière-plan
            $cmd = sprintf(
                'php %s/../../../../../process_translations_batch.php > /dev/null 2>&1 &',
                __DIR__
            );
            
            exec($cmd);
            
            return new ApiResponse([
                'success' => true,
                'message' => 'Traitement démarré',
                'translation_id' => $translationId
            ], 200);
            
        } catch (Exception $e) {
            return new ApiResponse(['error' => $e->getMessage()], 500);
        }
    }
}