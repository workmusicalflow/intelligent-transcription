<?php

/**
 * Endpoint d'authentification - /api/auth/login
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

// Seules les requêtes POST sont autorisées
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Lire les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }
    
    // Valider les champs requis
    if (empty($data['email']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Email et mot de passe requis',
            'details' => 'Veuillez fournir un email et un mot de passe'
        ]);
        exit;
    }
    
    $email = trim($data['email']);
    $password = $data['password'];
    
    // Connexion à la base de données
    $dbPath = dirname(dirname(__DIR__)) . '/database/transcription.db';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Rechercher l'utilisateur par email
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, first_name, last_name, is_admin, is_active 
        FROM users 
        WHERE email = ? AND is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Identifiants invalides',
            'details' => 'Email ou mot de passe incorrect'
        ]);
        exit;
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Identifiants invalides',
            'details' => 'Email ou mot de passe incorrect'
        ]);
        exit;
    }
    
    // Générer un token JWT simple (pour la démo)
    $tokenPayload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'username' => $user['username'],
        'is_admin' => (bool)$user['is_admin'],
        'exp' => time() + (24 * 60 * 60) // 24 heures
    ];
    
    // Token JWT simplifié (en production, utiliser une vraie librairie JWT)
    $token = base64_encode(json_encode($tokenPayload));
    
    // Mettre à jour la date de dernière connexion
    $stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Réponse de succès
    $response = [
        'success' => true,
        'message' => 'Connexion réussie',
        'data' => [
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'is_admin' => (bool)$user['is_admin'],
                'full_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
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