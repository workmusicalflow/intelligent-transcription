<?php

/**
 * Point d'entrée principal de l'API v2
 * 
 * Toutes les requêtes vers /api/v2/* sont redirigées ici
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Éviter les problèmes de session dans l'API
ini_set('session.use_cookies', 0);

// Définir les constantes si nécessaire
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(dirname(__DIR__)));
}

// Charger l'autoloader et le bootstrap
require_once BASE_DIR . '/src/bootstrap.php';

use Infrastructure\Http\Api\v2\ApiRouter;

// Headers de base pour l'API
header('Content-Type: application/json');
header('X-API-Version: 2.0');

try {
    // Créer le routeur
    $router = new ApiRouter();
    
    // Récupérer la méthode et l'URI
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    
    // Gérer la requête
    $router->handle($method, $uri);
    
} catch (\Exception $e) {
    // Erreur non gérée
    error_log("API Fatal Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}