<?php

/**
 * Point d'entrée GraphQL
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Éviter les problèmes de session dans GraphQL
ini_set('session.use_cookies', 0);

if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

// Headers CORS pour GraphQL
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once BASE_DIR . '/src/bootstrap.php';

use Infrastructure\GraphQL\GraphQLService;
use Infrastructure\Http\Api\v2\Middleware\AuthMiddleware;

// Headers pour GraphQL
header('Content-Type: application/json');

try {
    // Créer le service GraphQL
    $graphqlService = new GraphQLService();
    
    // Récupérer les données de la requête
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \InvalidArgumentException('Invalid JSON in request body');
    }
    
    // Extraire query, variables et operationName
    $query = $input['query'] ?? '';
    $variables = $input['variables'] ?? null;
    $operationName = $input['operationName'] ?? null;
    
    if (empty($query)) {
        throw new \InvalidArgumentException('Query is required');
    }
    
    // Préparer le contexte
    $context = [];
    
    // Gérer l'authentification si présente
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        try {
            $token = $matches[1];
            $middleware = new AuthMiddleware();
            
            // Simuler une requête pour l'authentification
            $request = new \Infrastructure\Http\Api\v2\ApiRequest(
                'POST',
                '/graphql',
                [],
                [],
                [],
                ['Authorization' => $authHeader]
            );
            
            $authResponse = $middleware->handle($request);
            
            if ($authResponse === null) {
                // Authentification réussie
                $context['user'] = $request->getUser();
            }
        } catch (\Exception $e) {
            // Authentification échouée - continuer sans utilisateur
        }
    }
    
    // Exécuter la requête GraphQL
    $result = $graphqlService->executeQuery($query, $variables, $operationName, $context);
    
    // Retourner le résultat
    echo json_encode($result);
    
} catch (\Exception $e) {
    // Erreur globale
    http_response_code(400);
    echo json_encode([
        'errors' => [
            [
                'message' => $e->getMessage(),
                'extensions' => [
                    'category' => 'request'
                ]
            ]
        ]
    ]);
}