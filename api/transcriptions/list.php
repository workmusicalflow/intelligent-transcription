<?php

/**
 * API pour lister les transcriptions de l'utilisateur connecté
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
    
    // Paramètres de requête
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(5, intval($_GET['limit']))) : 10;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $language = isset($_GET['language']) ? trim($_GET['language']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    $offset = ($page - 1) * $limit;
    
    // Connexion à la base de données
    $dbPath = dirname(dirname(__DIR__)) . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Construction de la requête avec filtres
    $whereConditions = ['user_id = :user_id'];
    $params = ['user_id' => $userId];
    
    if (!empty($search)) {
        $whereConditions[] = '(file_name LIKE :search OR text LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }
    
    if (!empty($language)) {
        $whereConditions[] = 'language = :language';
        $params['language'] = $language;
    }
    
    if (!empty($status)) {
        if ($status === 'completed') {
            $whereConditions[] = 'is_processed = 1';
        } elseif ($status === 'processing') {
            $whereConditions[] = 'is_processed = 0';
        }
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Valider le champ de tri
    $allowedSortFields = ['created_at', 'file_name', 'language', 'duration', 'file_size'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'created_at';
    }
    
    // Requête pour compter le total
    $countQuery = "SELECT COUNT(*) as total FROM transcriptions WHERE $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Requête principale pour récupérer les transcriptions
    $query = "
        SELECT 
            id,
            file_name,
            CASE 
                WHEN length(text) > 200 THEN substr(text, 1, 200) || '...'
                ELSE text
            END as preview_text,
            language,
            youtube_url,
            youtube_id,
            created_at,
            file_size,
            duration,
            is_processed,
            CASE WHEN youtube_url IS NOT NULL THEN 'youtube' ELSE 'file' END as source_type
        FROM transcriptions 
        WHERE $whereClause 
        ORDER BY $sortBy $sortOrder 
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    
    $transcriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données
    $formattedTranscriptions = array_map(function($transcription) {
        return [
            'id' => $transcription['id'],
            'fileName' => $transcription['file_name'],
            'previewText' => $transcription['preview_text'],
            'language' => $transcription['language'],
            'sourceType' => $transcription['source_type'],
            'youtubeUrl' => $transcription['youtube_url'],
            'createdAt' => $transcription['created_at'],
            'fileSize' => $transcription['file_size'] ? intval($transcription['file_size']) : null,
            'duration' => $transcription['duration'] ? intval($transcription['duration']) : null,
            'isProcessed' => (bool)$transcription['is_processed'],
            'status' => $transcription['is_processed'] ? 'completed' : 'processing'
        ];
    }, $transcriptions);
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalCount / $limit);
    $hasNext = $page < $totalPages;
    $hasPrev = $page > 1;
    
    // Statistiques supplémentaires
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN is_processed = 0 THEN 1 ELSE 0 END) as processing,
            COUNT(DISTINCT language) as languages,
            SUM(duration) as total_duration,
            SUM(file_size) as total_size
        FROM transcriptions 
        WHERE user_id = :user_id
    ";
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute(['user_id' => $userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Langues disponibles
    $languagesQuery = "
        SELECT DISTINCT language, COUNT(*) as count 
        FROM transcriptions 
        WHERE user_id = :user_id 
        GROUP BY language 
        ORDER BY count DESC
    ";
    $languagesStmt = $pdo->prepare($languagesQuery);
    $languagesStmt->execute(['user_id' => $userId]);
    $languages = $languagesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Réponse
    $response = [
        'success' => true,
        'data' => [
            'transcriptions' => $formattedTranscriptions,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalCount' => intval($totalCount),
                'limit' => $limit,
                'hasNext' => $hasNext,
                'hasPrev' => $hasPrev
            ],
            'filters' => [
                'search' => $search,
                'language' => $language,
                'status' => $status,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ],
            'stats' => [
                'total' => intval($stats['total']),
                'completed' => intval($stats['completed']),
                'processing' => intval($stats['processing']),
                'languages' => intval($stats['languages']),
                'totalDuration' => intval($stats['total_duration'] ?? 0),
                'totalSize' => intval($stats['total_size'] ?? 0)
            ],
            'availableLanguages' => $languages
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