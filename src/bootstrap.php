<?php

/**
 * Fichier d'initialisation de l'application
 * Ce fichier est inclus au début de chaque script pour initialiser l'application
 */

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Charger la configuration
require_once __DIR__ . '/config.php';

// Charger l'autoloader
require_once __DIR__ . '/autoload.php';

// Définir le gestionnaire d'erreurs
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Ignorer les erreurs qui sont supprimées avec @
    if (error_reporting() === 0) {
        return false;
    }

    // Enregistrer l'erreur dans le fichier de log
    $error_message = date('Y-m-d H:i:s') . " - Erreur $errno: $errstr dans $errfile à la ligne $errline\n";
    error_log($error_message, 3, BASE_DIR . '/php_errors.log');

    // Afficher l'erreur en mode développement
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
        echo "<strong>Erreur $errno:</strong> $errstr<br>";
        echo "<strong>Fichier:</strong> $errfile<br>";
        echo "<strong>Ligne:</strong> $errline<br>";
        echo "</div>";
    }

    // Ne pas exécuter le gestionnaire d'erreurs interne de PHP
    return true;
});

// Définir le gestionnaire d'exceptions
set_exception_handler(function ($exception) {
    // Enregistrer l'exception dans le fichier de log
    $error_message = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . " dans " . $exception->getFile() . " à la ligne " . $exception->getLine() . "\n";
    $error_message .= "Trace: " . $exception->getTraceAsString() . "\n";
    error_log($error_message, 3, BASE_DIR . '/php_errors.log');

    // Afficher l'exception en mode développement
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
        echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>Fichier:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Ligne:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Trace:</strong> <pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        // En production, afficher un message d'erreur générique
        echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
        echo "<strong>Une erreur est survenue.</strong> Veuillez réessayer ultérieurement.";
        echo "</div>";
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
        error_log($error_message, 3, BASE_DIR . '/php_errors.log');

        // Afficher l'erreur en mode développement
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
            echo "<strong>Erreur fatale:</strong> " . $error['message'] . "<br>";
            echo "<strong>Fichier:</strong> " . $error['file'] . "<br>";
            echo "<strong>Ligne:</strong> " . $error['line'] . "<br>";
            echo "</div>";
        } else {
            // En production, afficher un message d'erreur générique
            echo "<div style='background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #c62828;'>";
            echo "<strong>Une erreur fatale est survenue.</strong> Veuillez réessayer ultérieurement.";
            echo "</div>";
        }
    }
});

// Nettoyer les anciens fichiers
\Utils\FileUtils::cleanupOldFiles(UPLOAD_DIR);
\Utils\FileUtils::cleanupOldFiles(TEMP_AUDIO_DIR);
\Utils\FileUtils::cleanupOldFiles(RESULT_DIR);
