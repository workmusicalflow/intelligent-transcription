<?php

/**
 * Fichier d'initialisation de l'application
 * Ce fichier est inclus au début de chaque script pour initialiser l'application
 */

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Avoid loading the src/config.php if we've already loaded the root config.php
if (!defined('UPLOAD_DIR')) {
    // Charger la configuration
    require_once __DIR__ . '/config.php';
}

// Charger l'autoloader Composer
require_once BASE_PATH . '/vendor/autoload.php';

// Charger notre autoloader personnalisé
require_once __DIR__ . '/autoload.php';

// Initialisation du conteneur DI et de l'architecture Clean
use Infrastructure\Container\ServiceLocator;

// Configuration de l'environnement pour DI
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Configuration par défaut
if (!isset($_ENV['APP_ENV'])) {
    $_ENV['APP_ENV'] = 'development';
}

if (!isset($_ENV['OPENAI_API_KEY'])) {
    $_ENV['OPENAI_API_KEY'] = '';
}

// Initialiser le conteneur DI
ServiceLocator::init();

// Services de transition pour l'ancien code
if (!function_exists('get_transcription_repository')) {
    function get_transcription_repository() {
        return ServiceLocator::getTranscriptionRepository();
    }
}

if (!function_exists('get_transcriber')) {
    function get_transcriber() {
        return ServiceLocator::getTranscriber();
    }
}

if (!function_exists('get_cache')) {
    function get_cache() {
        return ServiceLocator::getCache();
    }
}

// Démarrer la session et définir les paramètres des cookies
if (session_status() == PHP_SESSION_NONE) {
    // Définir les paramètres des cookies de session (facultatif mais recommandé)
    // session_set_cookie_params([
    //     'lifetime' => 86400, // Durée de vie du cookie en secondes (ici, 1 jour)
    //     'path' => '/', // Chemin sur le serveur où le cookie sera disponible
    //     'domain' => '', // Domaine du cookie (laisser vide pour le domaine actuel)
    //     'secure' => isset($_SERVER['HTTPS']), // True si HTTPS est utilisé
    //     'httponly' => true, // Le cookie ne sera accessible qu'en HTTP(S)
    //     'samesite' => 'Lax' // Protection CSRF
    // ]);
    session_start();
}


// Définir le gestionnaire d'erreurs
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Ignorer les erreurs qui sont supprimées avec @
    if (error_reporting() === 0) {
        return false;
    }

    // Enregistrer l'erreur dans le fichier de log
    $error_message = date('Y-m-d H:i:s') . " - Erreur $errno: $errstr dans $errfile à la ligne $errline\n";
    error_log($error_message, 3, BASE_PATH . '/php_errors.log');

    // En mode développement, stocker l'erreur pour l'afficher plus tard (après les en-têtes)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        // Ne pas afficher directement pour éviter les erreurs "headers already sent"
        if (!isset($GLOBALS['error_messages'])) {
            $GLOBALS['error_messages'] = [];
        }
        $GLOBALS['error_messages'][] = [
            'type' => 'error',
            'errno' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];
    }

    // Ne pas exécuter le gestionnaire d'erreurs interne de PHP
    return true;
});

// Définir le gestionnaire d'exceptions
set_exception_handler(function ($exception) {
    // Enregistrer l'exception dans le fichier de log
    $error_message = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . " dans " . $exception->getFile() . " à la ligne " . $exception->getLine() . "\n";
    $error_message .= "Trace: " . $exception->getTraceAsString() . "\n";
    error_log($error_message, 3, BASE_PATH . '/php_errors.log');

    // En mode développement, stocker l'exception pour l'afficher plus tard (après les en-têtes)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        if (!isset($GLOBALS['error_messages'])) {
            $GLOBALS['error_messages'] = [];
        }
        $GLOBALS['error_messages'][] = [
            'type' => 'exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
    } else {
        // En production, stocker un message d'erreur générique
        if (!isset($GLOBALS['error_messages'])) {
            $GLOBALS['error_messages'] = [];
        }
        $GLOBALS['error_messages'][] = [
            'type' => 'generic_exception',
            'message' => 'Une erreur est survenue. Veuillez réessayer ultérieurement.'
        ];
    }

    // Terminer le script
    exit(1);
});

// Définir le gestionnaire de shutdown
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Enregistrer l'erreur fatale dans le fichier de log
        $error_message = date('Y-m-d H:i:s') . " - Erreur fatale: " . $error['message'] . " dans " . $error['file'] . " à la ligne " . $error['line'] . "\n";
        error_log($error_message, 3, BASE_PATH . '/php_errors.log');

        // En mode développement, stocker l'erreur fatale
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            if (!isset($GLOBALS['error_messages'])) {
                $GLOBALS['error_messages'] = [];
            }
            $GLOBALS['error_messages'][] = [
                'type' => 'fatal_error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line']
            ];
        } else {
            // En production, stocker un message d'erreur générique
            if (!isset($GLOBALS['error_messages'])) {
                $GLOBALS['error_messages'] = [];
            }
            $GLOBALS['error_messages'][] = [
                'type' => 'generic_fatal',
                'message' => 'Une erreur fatale est survenue. Veuillez réessayer ultérieurement.'
            ];
        }
    }
    
    // Afficher les erreurs stockées si elles existent et que la sortie n'a pas encore commencé
    if (isset($GLOBALS['error_messages']) && !empty($GLOBALS['error_messages']) && !headers_sent()) {
        // Afficher les erreurs stockées dans une div après avoir envoyé les en-têtes
        register_shutdown_function(function() {
            echo "<div class='error-container' style='position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background-color: #f8d7da; border-bottom: 1px solid #f5c6cb; padding: 10px;'>";
            foreach ($GLOBALS['error_messages'] as $error) {
                if ($error['type'] === 'error') {
                    echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
                    echo "<strong>Erreur {$error['errno']}:</strong> {$error['message']}<br>";
                    echo "<strong>Fichier:</strong> {$error['file']}<br>";
                    echo "<strong>Ligne:</strong> {$error['line']}<br>";
                    echo "</div>";
                } elseif ($error['type'] === 'exception') {
                    echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
                    echo "<strong>Exception:</strong> {$error['message']}<br>";
                    echo "<strong>Fichier:</strong> {$error['file']}<br>";
                    echo "<strong>Ligne:</strong> {$error['line']}<br>";
                    echo "<strong>Trace:</strong> <pre>" . $error['trace'] . "</pre>";
                    echo "</div>";
                } elseif ($error['type'] === 'fatal_error') {
                    echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
                    echo "<strong>Erreur fatale:</strong> {$error['message']}<br>";
                    echo "<strong>Fichier:</strong> {$error['file']}<br>";
                    echo "<strong>Ligne:</strong> {$error['line']}<br>";
                    echo "</div>";
                } else {
                    echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
                    echo "<strong>{$error['message']}</strong>";
                    echo "</div>";
                }
            }
            echo "</div>";
        });
    }
});

// Note: File cleanup is now handled in index.php or other specific scripts if needed.
// Removed redundant cleanup calls from bootstrap.