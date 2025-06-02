<?php
/**
 * Processeur automatique de transcription (appelé en arrière-plan)
 * Usage: php process_transcription_auto.php <transcription_id>
 */

if ($argc < 2) {
    die("Usage: php process_transcription_auto.php <transcription_id>\n");
}

$transcriptionId = $argv[1];

// Chargement de la configuration
require_once __DIR__ . '/config.php';

// Fonction de log
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] AUTO-TRANSCRIPTION: $message\n", 3, __DIR__ . '/logs/auto_transcription.log');
    echo "[$timestamp] $message\n";
}

logMessage("Début du traitement automatique pour: $transcriptionId");

try {
    // Connexion à la base de données
    $dbPath = __DIR__ . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les informations de la transcription
    $stmt = $pdo->prepare("SELECT * FROM transcriptions WHERE id = ?");
    $stmt->execute([$transcriptionId]);
    $transcription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transcription) {
        throw new Exception("Transcription $transcriptionId non trouvée");
    }
    
    if ($transcription['is_processed']) {
        logMessage("Transcription déjà traitée, arrêt");
        exit(0);
    }
    
    $filePath = $transcription['file_path'];
    $language = $transcription['language'];
    $isYoutube = !empty($transcription['youtube_url']);
    
    logMessage("Fichier: " . ($transcription['file_name'] ?? 'YouTube'));
    logMessage("Langue: $language");
    logMessage("Type: " . ($isYoutube ? 'YouTube' : 'Fichier'));
    
    // Vérifier si c'est un fichier local
    if (!$isYoutube) {
        if (!file_exists($filePath)) {
            throw new Exception("Fichier non trouvé: $filePath");
        }
        
        $fileSize = filesize($filePath);
        logMessage("Taille fichier: " . round($fileSize / 1024 / 1024, 2) . " MB");
        
        // Vérifier la limite Whisper (25MB)
        if ($fileSize > 25 * 1024 * 1024) {
            logMessage("Fichier trop gros pour Whisper, compression nécessaire");
            
            // Lancer la compression FFmpeg
            $compressedPath = dirname($filePath) . '/' . 'compressed_' . basename($filePath, '.mp4') . '.mp3';
            $ffmpegCommand = "ffmpeg -i " . escapeshellarg($filePath) . 
                           " -c:a libmp3lame -b:a 128k -ac 1 -ar 22050 " . 
                           escapeshellarg($compressedPath) . " -y 2>/dev/null";
            
            logMessage("Compression en cours...");
            exec($ffmpegCommand, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($compressedPath)) {
                logMessage("Compression réussie: " . round(filesize($compressedPath) / 1024 / 1024, 2) . " MB");
                $filePath = $compressedPath;
                
                // Mettre à jour le chemin dans la DB
                $stmt = $pdo->prepare("UPDATE transcriptions SET preprocessed_path = ? WHERE id = ?");
                $stmt->execute([$compressedPath, $transcriptionId]);
            } else {
                throw new Exception("Échec de la compression FFmpeg");
            }
        }
    } else {
        // Pour YouTube, on devrait télécharger d'abord
        throw new Exception("Traitement YouTube non implémenté dans cette version");
    }
    
    // Marquer comme en cours de traitement
    $stmt = $pdo->prepare("UPDATE transcriptions SET processing_model = 'whisper-1' WHERE id = ?");
    $stmt->execute([$transcriptionId]);
    
    // Appel à l'API Whisper
    logMessage("Envoi à OpenAI Whisper...");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes max
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OPENAI_API_KEY,
    ]);
    
    $postFields = [
        'model' => 'whisper-1',
        'file' => new CURLFile($filePath, 'audio/mp4', basename($filePath)),
        'response_format' => 'verbose_json',
        'timestamp_granularities' => 'segment' // Pour avoir les segments
    ];
    
    // Ajouter la langue si spécifiée
    if ($language && $language !== 'auto') {
        $postFields['language'] = $language;
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $processingTime = microtime(true) - $startTime;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Erreur cURL: $error");
    }
    
    if ($httpCode !== 200) {
        $errorMsg = "Erreur Whisper HTTP $httpCode: " . substr($response, 0, 200);
        logMessage($errorMsg);
        
        // Marquer comme échoué
        $stmt = $pdo->prepare("UPDATE transcriptions SET is_processed = -1 WHERE id = ?");
        $stmt->execute([$transcriptionId]);
        
        throw new Exception($errorMsg);
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        throw new Exception("Réponse JSON invalide de Whisper");
    }
    
    // Succès !
    logMessage("Transcription réussie!");
    logMessage("Temps de traitement: " . round($processingTime, 2) . "s");
    logMessage("Langue détectée: " . ($data['language'] ?? 'N/A'));
    logMessage("Durée audio: " . ($data['duration'] ?? 'N/A') . "s");
    logMessage("Texte: " . strlen($data['text']) . " caractères");
    
    // Calculer la confiance moyenne
    $confidence = 0.0;
    if (isset($data['segments']) && !empty($data['segments'])) {
        $totalConfidence = 0;
        $segmentCount = count($data['segments']);
        foreach ($data['segments'] as $segment) {
            if (isset($segment['avg_logprob'])) {
                $totalConfidence += exp($segment['avg_logprob']);
            }
        }
        $confidence = $segmentCount > 0 ? $totalConfidence / $segmentCount : 0.0;
    }
    
    // Sauvegarder en base de données
    $updateQuery = "UPDATE transcriptions SET 
        text = :text, 
        is_processed = 1, 
        duration = :duration,
        detected_language = :detected_language,
        confidence_score = :confidence_score,
        whisper_data = :whisper_data,
        whisper_version = 'whisper-1'
        WHERE id = :id";
    
    $stmt = $pdo->prepare($updateQuery);
    $result = $stmt->execute([
        'text' => $data['text'],
        'duration' => $data['duration'] ?? null,
        'detected_language' => $data['language'] ?? null,
        'confidence_score' => $confidence,
        'whisper_data' => json_encode($data),
        'id' => $transcriptionId
    ]);
    
    if (!$result) {
        throw new Exception("Erreur lors de la sauvegarde en base de données");
    }
    
    logMessage("✅ Transcription sauvegardée avec succès!");
    
    // Nettoyer le fichier compressé temporaire si créé
    if (isset($compressedPath) && file_exists($compressedPath) && $compressedPath !== $transcription['file_path']) {
        unlink($compressedPath);
        logMessage("Fichier temporaire supprimé");
    }
    
    logMessage("🎉 Traitement terminé avec succès pour: $transcriptionId");
    
} catch (Exception $e) {
    logMessage("❌ Erreur: " . $e->getMessage());
    
    // Marquer comme échoué en base
    try {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("UPDATE transcriptions SET is_processed = -1 WHERE id = ?");
            $stmt->execute([$transcriptionId]);
        }
    } catch (Exception $dbError) {
        logMessage("❌ Erreur DB: " . $dbError->getMessage());
    }
    
    exit(1);
}
?>