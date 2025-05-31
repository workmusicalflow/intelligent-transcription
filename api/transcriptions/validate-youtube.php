<?php

/**
 * API pour valider une URL YouTube et récupérer les métadonnées
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

try {
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        exit;
    }

    // Récupérer le JSON du body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['url'])) {
        http_response_code(400);
        echo json_encode(['error' => 'URL requise']);
        exit;
    }
    
    $youtubeUrl = trim($input['url']);
    
    // Valider le format de l'URL YouTube
    if (!preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $youtubeUrl, $matches)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'URL YouTube invalide',
            'message' => 'L\'URL fournie n\'est pas une URL YouTube valide'
        ]);
        exit;
    }
    
    $videoId = $matches[4];
    
    // Fonction pour récupérer les métadonnées YouTube via l'API oEmbed
    function getYouTubeMetadata($videoId) {
        $oEmbedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . urlencode($videoId) . "&format=json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oEmbedUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; TranscriptionApp/1.0)');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    // Fonction pour estimer la durée via une requête HTML (fallback)
    function estimateYouTubeDuration($videoId) {
        $pageUrl = "https://www.youtube.com/watch?v=" . urlencode($videoId);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; TranscriptionApp/1.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $html) {
            // Rechercher la durée dans les métadonnées JSON-LD
            if (preg_match('/"duration":"PT(\d+)M(\d+)S"/', $html, $matches)) {
                $minutes = intval($matches[1]);
                $seconds = intval($matches[2]);
                return sprintf('%d:%02d', $minutes, $seconds);
            }
            
            // Rechercher dans les métadonnées OpenGraph
            if (preg_match('/content="PT(\d+)M(\d+)S"/', $html, $matches)) {
                $minutes = intval($matches[1]);
                $seconds = intval($matches[2]);
                return sprintf('%d:%02d', $minutes, $seconds);
            }
        }
        
        return null;
    }
    
    try {
        // Récupérer les métadonnées via oEmbed
        $metadata = getYouTubeMetadata($videoId);
        
        if (!$metadata) {
            // Fallback: vérifier si la vidéo existe via une requête HTTP simple
            $testUrl = "https://www.youtube.com/watch?v=" . urlencode($videoId);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; TranscriptionApp/1.0)');
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Vidéo non trouvée',
                    'message' => 'La vidéo YouTube spécifiée n\'existe pas ou n\'est pas accessible'
                ]);
                exit;
            }
            
            // Créer des métadonnées minimales si oEmbed échoue
            $metadata = [
                'title' => 'Vidéo YouTube (titre non disponible)',
                'author_name' => 'Chaîne inconnue',
                'thumbnail_url' => "https://img.youtube.com/vi/$videoId/mqdefault.jpg"
            ];
        }
        
        // Essayer de récupérer la durée
        $duration = estimateYouTubeDuration($videoId);
        
        // Construire la réponse
        $response = [
            'success' => true,
            'data' => [
                'videoId' => $videoId,
                'title' => $metadata['title'] ?? 'Titre non disponible',
                'channel' => $metadata['author_name'] ?? 'Chaîne inconnue',
                'thumbnail' => $metadata['thumbnail_url'] ?? "https://img.youtube.com/vi/$videoId/mqdefault.jpg",
                'duration' => $duration ?? 'Durée inconnue',
                'url' => $youtubeUrl,
                'isValid' => true,
                'accessibility' => [
                    'isPublic' => true, // On suppose que si on peut accéder aux métadonnées, c'est public
                    'hasSubtitles' => null, // Impossible à déterminer sans API YouTube Data
                    'language' => null // Impossible à déterminer sans API YouTube Data
                ]
            ],
            'message' => 'URL YouTube validée avec succès'
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Erreur lors de la validation',
            'message' => 'Impossible de valider l\'URL YouTube: ' . $e->getMessage()
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur interne du serveur',
        'details' => $e->getMessage()
    ]);
}