<?php

/**
 * Routeur pour le serveur PHP de développement
 */

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Nettoyer l'URI
$path = parse_url($requestUri, PHP_URL_PATH);

// Routing pour les endpoints API
if (strpos($path, '/api/') === 0) {
    // Extraire le chemin après /api/
    $apiPath = substr($path, 4); // Enlever '/api'
    
    // Router vers le bon fichier
    switch (true) {
        case $apiPath === '/auth/login':
        case $apiPath === '/auth/login.php':
            require __DIR__ . '/api/auth/login.php';
            return true;
            
        case $apiPath === '/auth/logout':
        case $apiPath === '/auth/logout.php':
            require __DIR__ . '/api/auth/logout.php';
            return true;
            
        case $apiPath === '/auth/me':
        case $apiPath === '/auth/me.php':
            require __DIR__ . '/api/auth/me.php';
            return true;
            
        case $apiPath === '/transcriptions/list':
        case $apiPath === '/transcriptions/list.php':
            require __DIR__ . '/api/transcriptions/list.php';
            return true;
            
        case $apiPath === '/transcriptions/detail':
        case $apiPath === '/transcriptions/detail.php':
            require __DIR__ . '/api/transcriptions/detail.php';
            return true;
            
        case $apiPath === '/transcriptions/create':
        case $apiPath === '/transcriptions/create.php':
            require __DIR__ . '/api/transcriptions/create.php';
            return true;
            
        case preg_match('#^/v2/(.+)$#', $apiPath, $matches):
            // Router vers l'API v2
            $_SERVER['PATH_INFO'] = '/' . $matches[1];
            require __DIR__ . '/api/v2/index.php';
            return true;
            
        default:
            // API endpoint non trouvé
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Endpoint non trouvé',
                'path' => $path
            ]);
            return true;
    }
}

// Routing pour GraphQL
if ($path === '/graphql') {
    require __DIR__ . '/graphql/index.php';
    return true;
}

// Pour les fichiers statiques et autres
if ($path !== '/' && file_exists(__DIR__ . $path)) {
    return false; // Laisser le serveur PHP servir le fichier
}

// Page d'accueil par défaut
if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

// Autre cas - 404
http_response_code(404);
echo "404 - Page non trouvée: " . htmlspecialchars($path);
return true;