<?php

/**
 * Endpoint pour récupérer les informations de l'utilisateur connecté
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
    
    // Connexion à la base de données
    $dbPath = dirname(dirname(__DIR__)) . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les informations de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT id, username, email, first_name, last_name, is_admin, is_active, created_at, last_login
        FROM users 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$tokenData['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit;
    }
    
    // Réponse avec les informations utilisateur
    $response = [
        'success' => true,
        'data' => [
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'is_admin' => (bool)$user['is_admin'],
                'full_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                'created_at' => $user['created_at'],
                'last_login' => $user['last_login']
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