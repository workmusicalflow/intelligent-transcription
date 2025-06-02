<?php

/**
 * API v2 - Lister les traductions d'un utilisateur
 * GET /api/v2/translations/list
 * 
 * Endpoint pour récupérer la liste des traductions avec filtres et pagination
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Seules les requêtes GET sont acceptées
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET.']);
    exit;
}

require_once __DIR__ . '/../../../config.php';

try {
    // 1. Authentification
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\\s+(.+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token d\'autorisation requis']);
        exit;
    }
    
    $token = $matches[1];
    $tokenData = json_decode(base64_decode($token), true);
    
    if (!$tokenData || !isset($tokenData['user_id']) || $tokenData['exp'] < time()) {
        http_response_code(401);
        echo json_encode(['error' => 'Token invalide ou expiré']);
        exit;
    }
    
    $userId = $tokenData['user_id'];

    // 2. Paramètres de requête
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10))); // Max 50 par page
    $offset = ($page - 1) * $limit;
    
    $targetLanguage = $_GET['target_language'] ?? null;
    $provider = $_GET['provider'] ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    $sortBy = $_GET['sort_by'] ?? 'created_at';
    $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');
    
    // Validation paramètres
    $allowedSortBy = ['created_at', 'target_language', 'quality_score', 'processing_time'];
    if (!in_array($sortBy, $allowedSortBy)) {
        $sortBy = 'created_at';
    }
    
    $allowedSortOrder = ['ASC', 'DESC'];
    if (!in_array($sortOrder, $allowedSortOrder)) {
        $sortOrder = 'DESC';
    }

    // 3. Connexion base de données
    $dbPath = dirname(dirname(dirname(__DIR__))) . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 4. Construction requête avec filtres
    $whereConditions = ['user_id = :user_id'];
    $params = ['user_id' => $userId];
    
    if ($targetLanguage) {
        $whereConditions[] = 'target_language = :target_language';
        $params['target_language'] = $targetLanguage;
    }
    
    if ($provider) {
        $whereConditions[] = 'provider_used = :provider';
        $params['provider'] = $provider;
    }
    
    if ($status) {
        $whereConditions[] = 'status = :status';
        $params['status'] = $status;
    }
    
    if ($search) {
        $whereConditions[] = '(transcription_id LIKE :search OR target_language LIKE :search)';
        $params['search'] = "%{$search}%";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // 5. Requête principale (simulation - en production, table translations)
    // Pour la démo, on simule des données
    $translations = simulateTranslationsList($userId, $whereClause, $params, $sortBy, $sortOrder, $limit, $offset);
    
    // 6. Compter total pour pagination
    $totalTranslations = simulateTranslationsCount($userId, $whereClause, $params);
    
    // 7. Préparer réponse avec pagination
    $response = [
        'success' => true,
        'data' => [
            'translations' => $translations,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => $totalTranslations,
                'total_pages' => ceil($totalTranslations / $limit),
                'has_next_page' => $page < ceil($totalTranslations / $limit),
                'has_previous_page' => $page > 1
            ],
            'filters' => [
                'target_language' => $targetLanguage,
                'provider' => $provider,
                'status' => $status,
                'search' => $search
            ],
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ],
            'statistics' => calculateUserTranslationStats($translations)
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

/**
 * Simuler liste de traductions (en production, requête BDD réelle)
 */
function simulateTranslationsList(string $userId, string $whereClause, array $params, string $sortBy, string $sortOrder, int $limit, int $offset): array
{
    // Simulation de données pour la démo
    $baseTranslations = [
        [
            'id' => 'trans_' . uniqid(),
            'transcription_id' => 'trans_683df1ce49468',
            'target_language' => 'fr',
            'provider_used' => 'gpt-4o-mini',
            'status' => 'completed',
            'quality_score' => 0.92,
            'processing_time' => 3.45,
            'estimated_cost' => 0.008,
            'segments_count' => 15,
            'total_duration' => 45.2,
            'created_at' => '2025-06-02 20:00:00',
            'completed_at' => '2025-06-02 20:00:03'
        ],
        [
            'id' => 'trans_' . uniqid(),
            'transcription_id' => 'test_revolution_1748888037',
            'target_language' => 'es',
            'provider_used' => 'hybrid',
            'status' => 'completed',
            'quality_score' => 0.95,
            'processing_time' => 2.12,
            'estimated_cost' => 0.012,
            'segments_count' => 8,
            'total_duration' => 28.5,
            'created_at' => '2025-06-02 19:45:00',
            'completed_at' => '2025-06-02 19:45:02'
        ],
        [
            'id' => 'trans_' . uniqid(),
            'transcription_id' => 'trans_683db0c0e6634',
            'target_language' => 'de',
            'provider_used' => 'gpt-4o-mini',
            'status' => 'processing',
            'quality_score' => null,
            'processing_time' => null,
            'estimated_cost' => 0.015,
            'segments_count' => 22,
            'total_duration' => 172.3,
            'created_at' => '2025-06-02 20:05:00',
            'completed_at' => null
        ]
    ];
    
    // Appliquer pagination (simulation)
    return array_slice($baseTranslations, $offset, $limit);
}

/**
 * Simuler compte total de traductions
 */
function simulateTranslationsCount(string $userId, string $whereClause, array $params): int
{
    // En production, COUNT(*) sur la table translations
    return 3; // Simulation
}

/**
 * Calculer statistiques utilisateur
 */
function calculateUserTranslationStats(array $translations): array
{
    $totalCost = 0;
    $avgQuality = 0;
    $totalProcessingTime = 0;
    $languageStats = [];
    $providerStats = [];
    $completedCount = 0;
    
    foreach ($translations as $translation) {
        $totalCost += $translation['estimated_cost'] ?? 0;
        
        if ($translation['quality_score']) {
            $avgQuality += $translation['quality_score'];
        }
        
        if ($translation['processing_time']) {
            $totalProcessingTime += $translation['processing_time'];
        }
        
        if ($translation['status'] === 'completed') {
            $completedCount++;
        }
        
        // Stats par langue
        $lang = $translation['target_language'];
        $languageStats[$lang] = ($languageStats[$lang] ?? 0) + 1;
        
        // Stats par provider
        $provider = $translation['provider_used'];
        $providerStats[$provider] = ($providerStats[$provider] ?? 0) + 1;
    }
    
    $totalCount = count($translations);
    
    return [
        'total_translations' => $totalCount,
        'completed_translations' => $completedCount,
        'success_rate' => $totalCount > 0 ? round($completedCount / $totalCount * 100, 1) : 0,
        'total_cost_usd' => round($totalCost, 4),
        'average_quality_score' => $totalCount > 0 ? round($avgQuality / $totalCount, 3) : null,
        'total_processing_time' => round($totalProcessingTime, 2),
        'languages_used' => $languageStats,
        'providers_used' => $providerStats,
        'favorite_target_language' => !empty($languageStats) ? array_keys($languageStats, max($languageStats))[0] : null,
        'most_used_provider' => !empty($providerStats) ? array_keys($providerStats, max($providerStats))[0] : null
    ];
}