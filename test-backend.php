<?php

/**
 * Test simple du backend - Sans GraphQL
 */

// Headers pour l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Test simple
$response = [
    'success' => true,
    'message' => 'Backend PHP opérationnel!',
    'data' => [
        'php_version' => PHP_VERSION,
        'timestamp' => date('c'),
        'server' => $_SERVER['SERVER_NAME'] ?? 'localhost',
        'port' => $_SERVER['SERVER_PORT'] ?? '8000'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);