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

/**
 * 🔑 FONCTION POST-PROCESSING: Améliorer la ponctuation manquante
 */
function enhancePunctuation($text) {
    if (empty($text)) return $text;
    
    // Si le texte a déjà une bonne ponctuation, ne pas modifier
    $punctuationCount = preg_match_all('/[.!?,:;]/', $text);
    $wordCount = str_word_count($text);
    
    if ($punctuationCount > $wordCount * 0.05) { // Si déjà 5% de ponctuation, c'est suffisant
        return $text;
    }
    
    $enhanced = $text;
    
    // 1. Ajouter des apostrophes pour les contractions françaises courantes
    $contractions = [
        '/\bj ai\b/i' => "j'ai",
        '/\bj entends\b/i' => "j'entends",
        '/\bc est\b/i' => "c'est",
        '/\bd accord\b/i' => "d'accord",
        '/\bl une\b/i' => "l'une",
        '/\bn ai\b/i' => "n'ai",
        '/\bqu un\b/i' => "qu'un",
        '/\bqu est\b/i' => "qu'est",
        '/\bqu il\b/i' => "qu'il",
        '/\bqu elle\b/i' => "qu'elle"
    ];
    
    foreach ($contractions as $pattern => $replacement) {
        $enhanced = preg_replace($pattern, $replacement, $enhanced);
    }
    
    // 2. Ajouter des points aux fins de phrases (mots de fin courants)
    $endingWords = ['monsieur', 'madame', 'merci', 'suffisant', 'fait', 'ça', 'voilà'];
    foreach ($endingWords as $word) {
        $enhanced = preg_replace('/\b' . preg_quote($word) . '\b(?!\s*[.!?])/i', $word . '.', $enhanced);
    }
    
    // 3. Ajouter des virgules avant certains mots de liaison
    $conjunctions = ['mais', 'alors', 'donc', 'car', 'or'];
    foreach ($conjunctions as $conjunction) {
        $enhanced = preg_replace('/\s+' . preg_quote($conjunction) . '\s+/', ', ' . $conjunction . ' ', $enhanced);
    }
    
    // 4. Points d'interrogation pour les questions
    $enhanced = preg_replace('/\b(est ce que|comment|pourquoi|quand|où|qui|que|quoi)\b([^.!?]*?)(\s+[A-Z]|$)/', '$1$2?$3', $enhanced);
    
    // 5. Majuscules après les points
    $enhanced = preg_replace_callback('/([.!?])\s+([a-z])/', function($matches) {
        return $matches[1] . ' ' . strtoupper($matches[2]);
    }, $enhanced);
    
    // 6. Majuscule au début si manquante
    $enhanced = ucfirst(trim($enhanced));
    
    // 7. Point final si manquant
    if (!preg_match('/[.!?]$/', trim($enhanced))) {
        $enhanced = trim($enhanced) . '.';
    }
    
    return $enhanced;
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
        // Traitement YouTube avec service existant
        $youtubeUrl = $transcription['youtube_url'];
        $youtubeId = $transcription['youtube_id'];
        
        logMessage("URL YouTube: $youtubeUrl");
        logMessage("Utilisation du service YouTube existant...");
        
        // Créer le répertoire temp_audio si nécessaire
        $tempDir = __DIR__ . '/temp_audio';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // Utiliser l'API loader.to existante
        $format = 'mp3';
        $apiKey = VIDEO_DOWNLOAD_API_KEY;
        $encodedUrl = urlencode($youtubeUrl);
        
        // Construire l'URL avec les paramètres de requête
        $apiUrl = VIDEO_DOWNLOAD_API_URL . "?format={$format}&url={$encodedUrl}&api={$apiKey}";
        
        logMessage("Requête API loader.to: $apiUrl");
        
        // Initialiser cURL pour la requête initiale
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200 || $error) {
            throw new Exception("Erreur API loader.to: HTTP $httpCode - $error");
        }
        
        $result = json_decode($response, true);
        if (!$result || !isset($result['success']) || !$result['success']) {
            throw new Exception("Réponse API loader.to invalide: " . json_encode($result));
        }
        
        $downloadId = $result['id'] ?? '';
        if (empty($downloadId)) {
            throw new Exception("ID de téléchargement non trouvé dans la réponse");
        }
        
        logMessage("ID de téléchargement: $downloadId");
        
        // Attendre la completion du téléchargement
        $downloadUrl = null;
        $maxAttempts = 30;
        $attempts = 0;
        $waitTime = 2;
        
        while ($attempts < $maxAttempts) {
            sleep((int)$waitTime);
            
            $progressUrl = $result['progress_url'] ?? (VIDEO_DOWNLOAD_PROGRESS_URL . "?id={$downloadId}");
            
            $ch = curl_init($progressUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $progressResponse = curl_exec($ch);
            $progressHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($progressHttpCode !== 200) {
                $attempts++;
                logMessage("Tentative $attempts/$maxAttempts - Progression HTTP: $progressHttpCode");
                continue;
            }
            
            $progressResult = json_decode($progressResponse, true);
            
            if (isset($progressResult['success']) && $progressResult['success'] == 1 && isset($progressResult['download_url'])) {
                $downloadUrl = $progressResult['download_url'];
                break;
            }
            
            if (isset($progressResult['progress'])) {
                logMessage("Progression: " . $progressResult['progress'] . "/1000");
            }
            
            $attempts++;
        }
        
        if (empty($downloadUrl)) {
            throw new Exception("Impossible d'obtenir l'URL de téléchargement après $maxAttempts tentatives");
        }
        
        logMessage("URL de téléchargement obtenue: $downloadUrl");
        
        // Télécharger le fichier audio
        $audioFileName = $transcriptionId . '_' . $youtubeId . '.mp3';
        $audioPath = $tempDir . '/' . $audioFileName;
        
        $fileContent = file_get_contents($downloadUrl);
        if ($fileContent === false) {
            throw new Exception("Impossible de télécharger le fichier audio");
        }
        
        if (file_put_contents($audioPath, $fileContent) === false) {
            throw new Exception("Impossible d'enregistrer le fichier audio");
        }
        
        $fileSize = filesize($audioPath);
        logMessage("Fichier audio téléchargé: " . round($fileSize / 1024 / 1024, 2) . " MB");
        
        // Vérifier la taille et compresser si nécessaire (même logique que pour les fichiers)
        if ($fileSize > 25 * 1024 * 1024) {
            logMessage("Fichier trop gros pour Whisper, compression nécessaire");
            
            $compressedPath = $tempDir . '/compressed_' . $audioFileName;
            $ffmpegCommand = "ffmpeg -i " . escapeshellarg($audioPath) . 
                           " -c:a libmp3lame -b:a 128k -ac 1 -ar 22050 " . 
                           escapeshellarg($compressedPath) . " -y 2>/dev/null";
            
            logMessage("Compression en cours...");
            exec($ffmpegCommand, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($compressedPath)) {
                logMessage("Compression réussie: " . round(filesize($compressedPath) / 1024 / 1024, 2) . " MB");
                unlink($audioPath); // Supprimer l'original
                $filePath = $compressedPath;
                
                // Mettre à jour le chemin dans la DB
                $stmt = $pdo->prepare("UPDATE transcriptions SET preprocessed_path = ? WHERE id = ?");
                $stmt->execute([$compressedPath, $transcriptionId]);
            } else {
                throw new Exception("Échec de la compression FFmpeg");
            }
        } else {
            $filePath = $audioPath;
            
            // Créer le répertoire de cache audio permanent pour YouTube
            $audioCacheDir = __DIR__ . '/audio_cache';
            if (!is_dir($audioCacheDir)) {
                mkdir($audioCacheDir, 0777, true);
            }
            
            // Copier le fichier vers le cache permanent
            $permanentAudioPath = $audioCacheDir . '/' . $audioFileName;
            if (copy($audioPath, $permanentAudioPath)) {
                logMessage("Fichier audio YouTube copié vers le cache permanent: $permanentAudioPath");
                
                // Mettre à jour le chemin dans la DB avec le fichier permanent
                $stmt = $pdo->prepare("UPDATE transcriptions SET file_path = ?, preprocessed_path = ? WHERE id = ?");
                $stmt->execute([$permanentAudioPath, $audioPath, $transcriptionId]);
                
                // Le fichier est maintenant permanent, on le garde
                $filePath = $permanentAudioPath;
            } else {
                // En cas d'échec de copie, utiliser le fichier temporaire
                logMessage("Erreur lors de la copie vers le cache permanent, utilisation du fichier temporaire");
                $stmt = $pdo->prepare("UPDATE transcriptions SET preprocessed_path = ? WHERE id = ?");
                $stmt->execute([$audioPath, $transcriptionId]);
            }
        }
        
        logMessage("Fichier audio prêt pour transcription: $filePath");
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
        'timestamp_granularities[]' => 'segment', // 🔑 RÉVOLUTIONNAIRE: Format PHP correct
        'timestamp_granularities[]' => 'word'     // 🔑 RÉVOLUTIONNAIRE: Word-level timestamps activés
    ];
    
    // Ajouter la langue si spécifiée
    if ($language && $language !== 'auto') {
        $postFields['language'] = $language;
    }
    
    // 🔑 Prompt contextuel AMÉLIORÉ pour optimiser transcription + ponctuation
    $dubbingPrompt = "Please provide an accurate transcription with proper punctuation, natural sentence structure, " .
                     "and clear paragraph breaks. Use periods, commas, question marks, and exclamation points " .
                     "appropriately. Include apostrophes in contractions (like j'ai, d'accord, c'est). " .
                     "Maintain conversational flow and natural speech rhythm for professional dubbing.";
    
    if ($isYoutube) {
        $dubbingPrompt .= " This video may contain multiple speakers - preserve dialogue structure and speaker changes.";
    }
    
    $postFields['prompt'] = $dubbingPrompt;
    
    // 🔑 DEBUG: Log de la configuration envoyée à Whisper
    logMessage("DEBUG: Configuration Whisper envoyée:");
    logMessage("  - model: " . $postFields['model']);
    logMessage("  - response_format: " . $postFields['response_format']);
    logMessage("  - timestamp_granularities: segment,word (fixed PHP format)");
    logMessage("  - prompt length: " . strlen($postFields['prompt']));
    if (isset($postFields['language'])) {
        logMessage("  - language: " . $postFields['language']);
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
    
    // 🔑 DEBUG: Examiner la structure de la réponse Whisper
    logMessage("DEBUG: Clés de réponse Whisper: " . implode(', ', array_keys($data)));
    if (isset($data['words'])) {
        logMessage("DEBUG: Nombre de mots avec timestamps: " . count($data['words']));
        if (!empty($data['words'])) {
            $firstWord = $data['words'][0];
            logMessage("DEBUG: Premier mot example: " . json_encode($firstWord));
        }
    } else {
        logMessage("DEBUG: Pas de clé 'words' dans la réponse Whisper");
    }
    
    // 🔑 Nouvelles métriques pour le doublage
    $wordCount = isset($data['words']) ? count($data['words']) : str_word_count($data['text']);
    $speechRate = ($data['duration'] > 0) ? ($wordCount / ($data['duration'] / 60)) : 0;
    $hasWordTimestamps = isset($data['words']) && !empty($data['words']);
    
    logMessage("Mots détectés: $wordCount");
    logMessage("Débit parole: " . round($speechRate, 1) . " mots/min");
    logMessage("Word-level timestamps: " . ($hasWordTimestamps ? "✅ Disponibles" : "❌ Indisponibles"));
    
    // Nettoyer les segments indésirables (mentions de services de transcription, etc.)
    $cleanedSegments = [];
    $unwantedPatterns = [
        '/transcribed by\s+https?:\/\/\S+/i',
        '/transcribed by\s+\w+\.\w+/i',
        '/generated by\s+\w+/i',
        '/powered by\s+\w+/i',
        '/made with\s+\w+/i'
    ];
    
    if (isset($data['segments']) && !empty($data['segments'])) {
        foreach ($data['segments'] as $segment) {
            $segmentText = trim($segment['text']);
            $isUnwanted = false;
            
            // Vérifier si ce segment correspond à un pattern indésirable
            foreach ($unwantedPatterns as $pattern) {
                if (preg_match($pattern, $segmentText)) {
                    $isUnwanted = true;
                    logMessage("Segment filtré (indésirable): " . substr($segmentText, 0, 50));
                    break;
                }
            }
            
            // Filtrer aussi les segments très courts avec une confiance très faible en fin de transcription
            $avgLogProb = $segment['avg_logprob'] ?? -1.0;
            $confidence = $avgLogProb > -10 ? exp($avgLogProb) : 0.0;
            if (strlen($segmentText) < 30 && $confidence < 0.6 && $segment['start'] > ($data['duration'] * 0.9)) {
                logMessage("Segment filtré (confiance faible en fin): " . substr($segmentText, 0, 50));
                $isUnwanted = true;
            }
            
            if (!$isUnwanted) {
                $cleanedSegments[] = $segment;
            }
        }
        
        // Calculer le nombre de segments supprimés
        $originalCount = count($data['segments']);
        $cleanedCount = count($cleanedSegments);
        $removedCount = $originalCount - $cleanedCount;
        
        if ($removedCount > 0) {
            logMessage("Segments nettoyés: $removedCount segment(s) supprimé(s) sur $originalCount");
        }
        
        // Remplacer les segments dans les données
        $data['segments'] = $cleanedSegments;
    }
    
    // 🔑 RÉVOLUTIONNAIRE: Préserver le texte original ponctué de Whisper quand possible
    $originalWhisperText = $data['text'] ?? '';
    $cleanedText = '';
    
    // Analyser la qualité de la ponctuation du texte original
    $originalPunctuationRatio = 0;
    if (!empty($originalWhisperText)) {
        $punctuationCount = preg_match_all('/[.!?,:;\'"]/', $originalWhisperText);
        $wordCount = str_word_count($originalWhisperText);
        $originalPunctuationRatio = $wordCount > 0 ? $punctuationCount / $wordCount : 0;
    }
    
    // 🎯 NOUVELLE LOGIQUE: Prioriser le texte original si bien ponctué
    if (!empty($originalWhisperText) && $originalPunctuationRatio > 0.03) {
        // Le texte original a une ponctuation correcte (>3%), le conserver
        logMessage("✨ Texte original Whisper conservé (ponctuation: " . round($originalPunctuationRatio * 100, 1) . "%)");
        $finalText = $originalWhisperText;
    } elseif (!empty($cleanedSegments)) {
        // Reconstruire à partir des segments nettoyés
        foreach ($cleanedSegments as $segment) {
            $cleanedText .= trim($segment['text']) . ' ';
        }
        $finalText = trim($cleanedText);
        logMessage("Texte reconstruit à partir de " . count($cleanedSegments) . " segments nettoyés");
    } elseif (isset($data['words']) && !empty($data['words'])) {
        // 🚀 Fallback: reconstruction word-level avec amélioration ponctuation
        foreach ($data['words'] as $word) {
            $cleanedText .= $word['word'] . ' ';
        }
        $finalText = trim($cleanedText);
        logMessage("🔥 Texte reconstruit à partir de " . count($data['words']) . " mots individuels (word-level)");
        
        // Améliorer la ponctuation seulement si reconstruction word-level
        $finalText = enhancePunctuation($finalText);
        logMessage("📝 Ponctuation améliorée par post-processing");
    } else {
        // Dernier recours: texte original même sans ponctuation
        if (!empty($originalWhisperText)) {
            $finalText = $originalWhisperText;
            logMessage("Utilisation du texte original Whisper (fallback)");
        } else {
            logMessage("⚠️ Aucun texte disponible dans la réponse Whisper");
            $finalText = '';
        }
    }
    
    // Appliquer le texte final déterminé par la logique ci-dessus
    $data['text'] = $finalText;
    
    // Calculer la confiance moyenne sur les segments nettoyés
    $confidence = 0.0;
    if (!empty($cleanedSegments)) {
        $totalConfidence = 0;
        $segmentCount = count($cleanedSegments);
        foreach ($cleanedSegments as $segment) {
            if (isset($segment['avg_logprob'])) {
                $totalConfidence += exp($segment['avg_logprob']);
            }
        }
        $confidence = $segmentCount > 0 ? $totalConfidence / $segmentCount : 0.0;
    }
    
    // Sauvegarder en base de données avec les nouvelles métriques de doublage
    $updateQuery = "UPDATE transcriptions SET 
        text = :text, 
        is_processed = 1, 
        duration = :duration,
        detected_language = :detected_language,
        confidence_score = :confidence_score,
        whisper_data = :whisper_data,
        whisper_version = 'whisper-1',
        has_word_timestamps = :has_word_timestamps,
        speech_rate = :speech_rate,
        word_count = :word_count
        WHERE id = :id";
    
    $stmt = $pdo->prepare($updateQuery);
    $result = $stmt->execute([
        'text' => $data['text'],
        'duration' => $data['duration'] ?? null,
        'detected_language' => $data['language'] ?? null,
        'confidence_score' => $confidence,
        'whisper_data' => json_encode($data),
        'has_word_timestamps' => $hasWordTimestamps ? 1 : 0,
        'speech_rate' => round($speechRate, 2),
        'word_count' => $wordCount,
        'id' => $transcriptionId
    ]);
    
    if (!$result) {
        throw new Exception("Erreur lors de la sauvegarde en base de données");
    }
    
    logMessage("✅ Transcription sauvegardée avec succès!");
    
    // Nettoyer les fichiers temporaires
    if (isset($compressedPath) && file_exists($compressedPath) && $compressedPath !== $transcription['file_path']) {
        unlink($compressedPath);
        logMessage("Fichier compressé temporaire supprimé");
    }
    
    // Nettoyer seulement les fichiers temporaires YouTube (pas ceux du cache permanent)
    if ($isYoutube && isset($filePath) && file_exists($filePath) && strpos($filePath, '/temp_audio/') !== false) {
        // Vérifier si le fichier a été copié vers le cache permanent
        $audioCacheDir = __DIR__ . '/audio_cache';
        $permanentFile = $audioCacheDir . '/' . basename($filePath);
        
        if (file_exists($permanentFile)) {
            // Le fichier permanent existe, on peut supprimer le temporaire
            unlink($filePath);
            logMessage("Fichier audio YouTube temporaire supprimé (permanent conservé)");
        } else {
            // Pas de fichier permanent, on garde le temporaire
            logMessage("Fichier audio YouTube temporaire conservé (pas de copie permanente)");
        }
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