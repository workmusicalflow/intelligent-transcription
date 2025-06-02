<?php

/**
 * API pour récupérer les détails complets d'une transcription
 */

// Headers pour l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Récupérer le token d'autorisation
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
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
    
    // Récupérer l'ID de la transcription depuis l'URL
    $transcriptionId = $_GET['id'] ?? '';
    
    if (empty($transcriptionId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de transcription requis']);
        exit;
    }
    
    // Connexion à la base de données
    $dbPath = dirname(dirname(__DIR__)) . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Requête pour récupérer la transcription complète avec nouvelles métadonnées de doublage
    $query = "
        SELECT 
            id,
            file_name,
            file_path,
            text,
            language,
            original_text,
            youtube_url,
            youtube_id,
            created_at,
            file_size,
            duration,
            is_processed,
            preprocessed_path,
            whisper_data,
            confidence_score,
            detected_language,
            processing_model,
            whisper_version,
            has_word_timestamps,
            speech_rate,
            word_count
        FROM transcriptions 
        WHERE id = :id AND user_id = :user_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'id' => $transcriptionId,
        'user_id' => $userId
    ]);
    
    $transcription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transcription) {
        http_response_code(404);
        echo json_encode(['error' => 'Transcription non trouvée']);
        exit;
    }
    
    // 🔑 Extraire les données Whisper enrichies pour le doublage
    $realSegments = [];
    $wordLevelData = [];
    $hasRealSegments = false;
    $hasWordTimestamps = (bool)($transcription['has_word_timestamps'] ?? false);
    
    // Vérifier s'il existe des données Whisper avec segments et mots
    if (!empty($transcription['whisper_data'])) {
        $whisperData = json_decode($transcription['whisper_data'], true);
        if ($whisperData && isset($whisperData['segments'])) {
            $realSegments = $whisperData['segments'];
            $hasRealSegments = true;
            
            // 🔑 RÉVOLUTIONNAIRE: Extraire les données word-level
            if (isset($whisperData['words']) && !empty($whisperData['words'])) {
                $wordLevelData = $whisperData['words'];
                $hasWordTimestamps = true;
            }
        }
    }
    
    // Formater les données pour le frontend
    $formattedTranscription = [
        'id' => $transcription['id'],
        'fileName' => $transcription['file_name'],
        'filePath' => $transcription['file_path'],
        'text' => $transcription['text'],
        'language' => $transcription['language'],
        'originalText' => $transcription['original_text'],
        'sourceType' => $transcription['youtube_url'] ? 'youtube' : 'file',
        'youtubeUrl' => $transcription['youtube_url'],
        'youtubeId' => $transcription['youtube_id'],
        'createdAt' => $transcription['created_at'],
        'fileSize' => $transcription['file_size'] ? intval($transcription['file_size']) : null,
        'duration' => $transcription['duration'] ? intval($transcription['duration']) : null,
        'isProcessed' => (bool)$transcription['is_processed'],
        'status' => $transcription['is_processed'] ? 'completed' : 'processing',
        'preprocessedPath' => $transcription['preprocessed_path'],
        'whisperMetadata' => [
            'confidenceScore' => $transcription['confidence_score'] ? floatval($transcription['confidence_score']) : null,
            'detectedLanguage' => $transcription['detected_language'],
            'processingModel' => $transcription['processing_model'],
            'whisperVersion' => $transcription['whisper_version'],
            'hasSegments' => $hasRealSegments,
            // 🔑 Nouvelles métadonnées révolutionnaires pour le doublage
            'hasWordTimestamps' => $hasWordTimestamps,
            'speechRate' => $transcription['speech_rate'] ? floatval($transcription['speech_rate']) : null,
            'wordCount' => $transcription['word_count'] ? intval($transcription['word_count']) : null,
            'dubbingCapabilities' => [
                'wordLevelSync' => $hasWordTimestamps,
                'preciseTimestamps' => $hasWordTimestamps,
                'speechAnalysis' => !empty($transcription['speech_rate']),
                'optimizedForDubbing' => $hasWordTimestamps && !empty($transcription['speech_rate'])
            ]
        ]
    ];
    
    // Ajouter des statistiques sur le texte
    $textStats = [
        'characterCount' => mb_strlen($transcription['text']),
        'wordCount' => str_word_count($transcription['text']),
        'paragraphCount' => substr_count($transcription['text'], "\n") + 1,
        'estimatedReadingTime' => ceil(str_word_count($transcription['text']) / 200) // 200 mots/minute
    ];
    
    // Si pas de vrais segments, générer des segments estimés
    $segments = [];
    
    if ($hasRealSegments) {
        // Utiliser les vrais segments Whisper avec enrichissements pour le doublage
        foreach ($realSegments as $index => $segment) {
            $segmentData = [
                'id' => $index,
                'text' => $segment['text'],
                'startTime' => (float)$segment['start'],
                'endTime' => (float)$segment['end'],
                'confidence' => isset($segment['avg_logprob']) ? exp($segment['avg_logprob']) : null,
                'isEstimated' => false,
                // 🔑 Métadonnées avancées pour le doublage
                'duration' => (float)$segment['end'] - (float)$segment['start'],
                'wordCount' => str_word_count($segment['text']),
                'metadata' => [
                    'avgLogprob' => $segment['avg_logprob'] ?? null,
                    'compressionRatio' => $segment['compression_ratio'] ?? null,
                    'noSpeechProb' => $segment['no_speech_prob'] ?? null,
                    'temperature' => $segment['temperature'] ?? null
                ]
            ];
            
            // 🔑 RÉVOLUTIONNAIRE: Ajouter les mots avec timestamps si disponibles
            if ($hasWordTimestamps && !empty($wordLevelData)) {
                $segmentWords = [];
                $segmentStart = (float)$segment['start'];
                $segmentEnd = (float)$segment['end'];
                
                foreach ($wordLevelData as $word) {
                    $wordStart = (float)$word['start'];
                    $wordEnd = (float)$word['end'];
                    
                    // Vérifier si le mot appartient à ce segment
                    if ($wordStart >= $segmentStart && $wordEnd <= $segmentEnd) {
                        $segmentWords[] = [
                            'word' => $word['word'],
                            'start' => $wordStart,
                            'end' => $wordEnd,
                            'confidence' => $word['confidence'] ?? null
                        ];
                    }
                }
                
                $segmentData['words'] = $segmentWords;
                $segmentData['hasWordTimestamps'] = !empty($segmentWords);
            }
            
            $segments[] = $segmentData;
        }
    } else {
        // Générer des segments estimés basés sur les phrases
        $sentences = preg_split('/[.!?]+/', $transcription['text'], -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_map('trim', $sentences);
        $sentences = array_filter($sentences);
        
        $totalWords = str_word_count($transcription['text']);
        $duration = $transcription['duration'] ?: 60; // Durée par défaut si manquante
        $wordsPerSecond = $totalWords > 0 ? $totalWords / $duration : 2; // ~2 mots/seconde par défaut
        $currentTime = 0;
        
        foreach ($sentences as $index => $sentence) {
            if (trim($sentence)) {
                $wordCount = str_word_count($sentence);
                $estimatedDuration = $wordCount / $wordsPerSecond;
                
                $segments[] = [
                    'id' => $index,
                    'text' => trim($sentence) . '.',
                    'startTime' => round($currentTime, 1),
                    'endTime' => round($currentTime + $estimatedDuration, 1),
                    'wordCount' => $wordCount,
                    'isEstimated' => true
                ];
                
                $currentTime += $estimatedDuration;
            }
        }
    }
    
    // URL de lecture (si fichier local existe)
    $audioUrl = null;
    if ($transcription['file_path'] && file_exists($transcription['file_path'])) {
        // Générer l'URL de streaming (à adapter selon votre configuration)
        $audioUrl = "/api/stream/" . urlencode($transcription['id']);
    } elseif ($transcription['youtube_url']) {
        // Pour YouTube, on peut utiliser l'URL directe (à traiter côté frontend)
        $audioUrl = $transcription['youtube_url'];
    }
    
    // Informations de partage
    $shareInfo = [
        'publicUrl' => null, // À implémenter si nécessaire
        'embedCode' => null,
        'downloadUrl' => "/api/transcriptions/download/" . urlencode($transcription['id'])
    ];
    
    // Réponse complète
    $response = [
        'success' => true,
        'data' => [
            'transcription' => $formattedTranscription,
            'textStats' => $textStats,
            'segments' => $segments,
            'audioUrl' => $audioUrl,
            'shareInfo' => $shareInfo,
            'capabilities' => [
                'canEdit' => true,
                'canDelete' => true,
                'canShare' => true,
                'canExport' => true,
                'hasRealTimeSync' => $hasRealSegments, // Vrai si segments Whisper disponibles
                'supportedExports' => ['txt', 'pdf', 'docx', 'json', 'srt']
            ],
            'segmentInfo' => [
                'hasRealSegments' => $hasRealSegments,
                'segmentCount' => count($segments),
                'estimationMethod' => $hasRealSegments ? 'whisper' : 'sentence_based'
            ],
            // 🔑 RÉVOLUTIONNAIRE: Données word-level pour synchronisation ultra-précise
            'wordLevelData' => $hasWordTimestamps ? [
                'available' => true,
                'totalWords' => count($wordLevelData),
                'words' => $wordLevelData,
                'dubbingReady' => true,
                'syncPrecision' => 'word-level',
                'averageWordDuration' => !empty($wordLevelData) ? 
                    array_sum(array_map(function($w) { return $w['end'] - $w['start']; }, $wordLevelData)) / count($wordLevelData) : null
            ] : [
                'available' => false,
                'dubbingReady' => false,
                'syncPrecision' => 'segment-level',
                'upgradeRequired' => 'Reprocess with word-level timestamps for premium dubbing capabilities'
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur interne du serveur',
        'details' => $e->getMessage()
    ]);
}