<?php

/**
 * Endpoint de déconnexion
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

// Pour un token JWT simple, la déconnexion côté serveur est optionnelle
// Le frontend supprime simplement le token côté client

$response = [
    'success' => true,
    'message' => 'Déconnexion réussie'
];

echo json_encode($response);