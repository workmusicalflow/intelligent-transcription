<?php

/**
 * Interface d'analytics pour les statistiques de cache
 */

// Inclure les fichiers nécessaires
require_once __DIR__ . '/src/bootstrap.php';

// Initialiser le contrôleur
$analyticsController = new Controllers\AnalyticsController();

// Déterminer l'action en fonction des paramètres
$action = $_GET['action'] ?? 'dashboard';
$conversationId = $_GET['id'] ?? null;

// Exécuter l'action correspondante
switch ($action) {
    case 'conversation':
        // Afficher les analytics d'une conversation spécifique
        if ($conversationId) {
            echo $analyticsController->showConversationAnalytics($conversationId);
        } else {
            // Rediriger vers le tableau de bord si aucun ID de conversation n'est spécifié
            header('Location: analytics.php');
            exit;
        }
        break;
        
    case 'clear_cache':
        // Effacer le cache
        $analyticsController->clearCache($conversationId);
        // La redirection est gérée dans le contrôleur
        break;
        
    case 'optimize_cache':
        // Optimiser le cache
        $analyticsController->optimizeCache();
        // La redirection est gérée dans le contrôleur
        break;
        
    case 'dashboard':
    default:
        // Afficher le tableau de bord par défaut
        echo $analyticsController->showCacheDashboard();
        break;
}