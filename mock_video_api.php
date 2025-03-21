<?php

/**
 * Mock API server pour simuler l'API loader.to
 * 
 * Pour utiliser ce serveur mock:
 * 1. Modifiez config.php pour pointer vers ce serveur local:
 *    define('VIDEO_DOWNLOAD_API_URL', 'http://localhost:8080/download.php');
 *    define('VIDEO_DOWNLOAD_PROGRESS_URL', 'http://localhost:8080/progress.php');
 * 2. Lancez le serveur avec: php -S localhost:8080 mock_video_api.php
 */

// Définir les en-têtes pour JSON
header('Content-Type: application/json');

// Récupérer la méthode HTTP et le chemin de la requête
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Récupérer les paramètres de la requête
$params = $_GET;

// Traiter les requêtes
if ($method === 'GET' && ($uri === '/download.php')) {
    // Vérifier si l'API key est fournie
    if (empty($params['api'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'API key is required'
        ]);
        exit;
    }

    // Vérifier si l'URL YouTube est fournie
    if (empty($params['url'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'YouTube URL is required'
        ]);
        exit;
    }

    // Vérifier si le format est fourni
    if (empty($params['format'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Format is required'
        ]);
        exit;
    }

    // Extraire l'ID de la vidéo YouTube
    $youtubeUrl = $params['url'];
    $videoId = '';

    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $youtubeUrl, $matches)) {
        $videoId = $matches[1];
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid YouTube URL'
        ]);
        exit;
    }

    // Générer un ID de téléchargement unique
    $downloadId = uniqid('dl_');

    // Enregistrer les informations de la requête pour le débogage
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'request' => [
            'url' => $youtubeUrl,
            'format' => $params['format'],
            'api_key' => substr($params['api'], 0, 5) . '...' // Masquer la clé API pour la sécurité
        ],
        'response' => [
            'id' => $downloadId,
            'video_id' => $videoId
        ]
    ];

    file_put_contents('mock_api_log.json', json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    // Répondre avec l'ID de téléchargement
    echo json_encode([
        'success' => true,
        'id' => $downloadId,
        'content' => base64_encode('<div class="download-item">Downloading ' . $videoId . '</div>'),
        'info' => [
            'image' => 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',
            'title' => 'Mock Video Title for ' . $videoId
        ]
    ]);
    exit;
} elseif ($method === 'GET' && ($uri === '/progress.php')) {
    // Vérifier si l'ID de téléchargement est fourni
    if (empty($params['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => 0,
            'error' => 'Download ID is required'
        ]);
        exit;
    }

    $downloadId = $params['id'];

    // Simuler la progression du téléchargement
    // Dans un environnement réel, cela vérifierait la progression réelle

    // Extraire le numéro de tentative à partir du cookie
    $attemptCookie = 'mock_attempt_' . $downloadId;
    $attempt = isset($_COOKIE[$attemptCookie]) ? (int)$_COOKIE[$attemptCookie] : 0;
    $attempt++;

    // Définir le cookie pour la prochaine requête
    setcookie($attemptCookie, $attempt, time() + 3600);

    // Créer un répertoire pour les fichiers audio fictifs
    $mockAudioDir = __DIR__ . '/mock_audio';
    if (!is_dir($mockAudioDir)) {
        mkdir($mockAudioDir, 0777, true);
    }

    // Simuler différentes étapes de progression
    if ($attempt <= 2) {
        // En cours de téléchargement
        echo json_encode([
            'progress' => $attempt * 200, // 0-40%
            'success' => 0,
            'download_url' => null,
            'text' => 'Downloading'
        ]);
    } elseif ($attempt <= 5) {
        // En cours de conversion
        echo json_encode([
            'progress' => 400 + ($attempt - 2) * 100, // 40-70%
            'success' => 0,
            'download_url' => null,
            'text' => 'Converting'
        ]);
    } elseif ($attempt <= 7) {
        // Presque terminé
        echo json_encode([
            'progress' => 700 + ($attempt - 5) * 100, // 70-90%
            'success' => 0,
            'download_url' => null,
            'text' => 'Finalizing'
        ]);
    } else {
        // Terminé
        // Créer un fichier audio fictif
        $mockAudioFile = $mockAudioDir . '/' . $downloadId . '.mp3';
        if (!file_exists($mockAudioFile)) {
            file_put_contents($mockAudioFile, 'Mock audio file for testing - ID: ' . $downloadId);
        }

        // Construire l'URL de téléchargement
        $downloadUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/mock_audio/' . $downloadId . '.mp3';

        echo json_encode([
            'progress' => 1000, // 100%
            'success' => 1,
            'download_url' => $downloadUrl,
            'text' => 'Completed'
        ]);
    }
    exit;
} elseif (preg_match('/^\/mock_audio\/(.+)$/', $uri, $matches) && file_exists(__DIR__ . $uri)) {
    // Servir le fichier audio fictif
    $file = __DIR__ . $uri;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file);
    finfo_close($finfo);

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    // Route non trouvée
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Not Found',
        'message' => 'The requested endpoint does not exist'
    ]);
    exit;
}
