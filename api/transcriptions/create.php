<?php

/**
 * API pour créer une nouvelle transcription (fichier upload ou YouTube)
 */

// Headers pour l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inclure les fichiers nécessaires
require_once dirname(dirname(__DIR__)) . '/config.php';
require_once dirname(dirname(__DIR__)) . '/utils.php';

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        exit;
    }

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
    
    // Connexion à la base de données
    $dbPath = dirname(dirname(__DIR__)) . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Debug : afficher ce qui est reçu
    error_log("=== DEBUG CREATE TRANSCRIPTION ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'non défini'));
    
    // Déterminer le type de transcription (fichier ou YouTube)
    $isFileUpload = isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK;
    $isYouTube = !$isFileUpload && isset($_POST['youtube_url']) && !empty($_POST['youtube_url']);
    
    if (!$isFileUpload && !$isYouTube) {
        http_response_code(400);
        echo json_encode(['error' => 'Aucun fichier ou URL YouTube fourni']);
        exit;
    }
    
    $transcriptionId = uniqid('trans_');
    $fileName = '';
    $filePath = '';
    $fileSize = 0;
    $youtubeUrl = '';
    $youtubeId = '';
    $language = $_POST['language'] ?? 'auto';
    $title = $_POST['title'] ?? '';
    
    if ($isFileUpload) {
        // Gestion de l'upload de fichier
        $file = $_FILES['audio_file'];
        
        // Vérifier la taille du fichier
        if ($file['size'] > (defined('MAX_UPLOAD_SIZE_BYTES') ? MAX_UPLOAD_SIZE_BYTES : 100 * 1024 * 1024)) { // 100MB par défaut
            http_response_code(400);
            echo json_encode(['error' => 'Fichier trop volumineux']);
            exit;
        }
        
        // Vérifier le type de fichier
        $allowedTypes = ['audio/mpeg', 'audio/wav', 'audio/mp3', 'audio/mp4', 'video/mp4', 'video/mpeg'];
        if (!in_array($file['type'], $allowedTypes)) {
            // Vérification supplémentaire basée sur l'extension
            $allowedExtensions = ['mp3', 'wav', 'mp4', 'mpeg', 'm4a', 'flac', 'ogg'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                http_response_code(400);
                echo json_encode(['error' => 'Type de fichier non autorisé']);
                exit;
            }
        }
        
        // Créer le répertoire d'upload si nécessaire
        $uploadDir = dirname(dirname(__DIR__)) . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Générer un nom de fichier unique
        $fileName = $title ?: pathinfo($file['name'], PATHINFO_FILENAME);
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFileName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileName);
        $uniqueFileName = $transcriptionId . '_' . $safeFileName . '.' . $fileExtension;
        $filePath = $uploadDir . '/' . $uniqueFileName;
        
        // Déplacer le fichier téléchargé
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la sauvegarde du fichier']);
            exit;
        }
        
        $fileSize = $file['size'];
        $fileName = $file['name'];
        
    } elseif ($isYouTube) {
        // Gestion de l'URL YouTube
        $youtubeUrl = $_POST['youtube_url'];
        
        // Valider l'URL YouTube
        if (!preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $youtubeUrl, $matches)) {
            http_response_code(400);
            echo json_encode(['error' => 'URL YouTube invalide']);
            exit;
        }
        
        $youtubeId = $matches[4];
        $fileName = $title ?: 'YouTube_' . $youtubeId;
    }
    
    // Insérer la transcription dans la base de données
    $insertQuery = "
        INSERT INTO transcriptions (
            id, user_id, file_name, file_path, file_size, language, 
            youtube_url, youtube_id, created_at, is_processed, text
        ) VALUES (
            :id, :user_id, :file_name, :file_path, :file_size, :language,
            :youtube_url, :youtube_id, :created_at, :is_processed, :text
        )
    ";
    
    $stmt = $pdo->prepare($insertQuery);
    $result = $stmt->execute([
        'id' => $transcriptionId,
        'user_id' => $userId,
        'file_name' => $fileName,
        'file_path' => $filePath,
        'file_size' => $fileSize,
        'language' => $language,
        'youtube_url' => $youtubeUrl ?: null,
        'youtube_id' => $youtubeId ?: null,
        'created_at' => date('Y-m-d H:i:s'),
        'is_processed' => 0,
        'text' => '' // Texte vide temporaire, sera rempli par le processus de transcription
    ]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la création de la transcription']);
        exit;
    }
    
    // Démarrer le traitement en arrière-plan (simulé)
    // En production, ceci devrait déclencher un job en arrière-plan
    if ($isFileUpload) {
        // Logique pour traiter le fichier audio
        $processingCommand = "php " . dirname(dirname(__DIR__)) . "/transcribe.php '$filePath' '$language' '$transcriptionId' > /dev/null 2>&1 &";
    } else {
        // Logique pour traiter la vidéo YouTube
        $processingCommand = "php " . dirname(dirname(__DIR__)) . "/youtube_download.php '$youtubeUrl' '$language' '$transcriptionId' > /dev/null 2>&1 &";
    }
    
    // Exécuter la commande en arrière-plan (en développement seulement)
    if (function_exists('exec')) {
        exec($processingCommand);
    }
    
    // Réponse de succès
    $response = [
        'success' => true,
        'data' => [
            'transcriptionId' => $transcriptionId,
            'fileName' => $fileName,
            'sourceType' => $isFileUpload ? 'file' : 'youtube',
            'language' => $language,
            'status' => 'processing',
            'createdAt' => date('Y-m-d H:i:s'),
            'estimatedProcessingTime' => $isFileUpload ? 
                ceil($fileSize / (1024 * 1024)) * 30 : // ~30 secondes par MB
                180 // ~3 minutes pour YouTube
        ],
        'message' => 'Transcription créée avec succès. Le traitement a commencé.'
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur interne du serveur',
        'details' => $e->getMessage()
    ]);
}