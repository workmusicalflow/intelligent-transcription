<?php

/**
 * Configuration de base de l'application
 */

// Chemins de base
define('BASE_DIR', dirname(__DIR__));
define('SRC_DIR', __DIR__);
define('TEMPLATES_DIR', BASE_DIR . '/templates');
define('UPLOAD_DIR', BASE_DIR . '/uploads');
define('TEMP_AUDIO_DIR', BASE_DIR . '/temp_audio');
define('RESULT_DIR', BASE_DIR . '/results');
define('EXPORT_DIR', BASE_DIR . '/exports');
define('DB_PATH', BASE_DIR . '/database/transcription.db');

// Chemins environnement
define('ENV_DIR', dirname(BASE_DIR)); // Dossier parent pour les fichiers d'environnement
define('ENV_FILE', ENV_DIR . '/inteligent-transcription-env/.env'); // Fichier .env en dehors du répertoire web

// Configuration de l'application
define('DEBUG_MODE', true); // Mettre à false en production
define('MAX_UPLOAD_SIZE_MB', 100);
define('MAX_UPLOAD_SIZE_BYTES', MAX_UPLOAD_SIZE_MB * 1024 * 1024);

// Configuration Python
define('PYTHON_PATH', getenv('PYTHON_PATH') ?: '/usr/bin/python3');

// Configuration API
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
define('VIDEO_DOWNLOAD_API_KEY', getenv('VIDEO_DOWNLOAD_API_KEY'));
define('VIDEO_DOWNLOAD_API_URL', 'https://loader.to/ajax/download.php');
define('VIDEO_DOWNLOAD_PROGRESS_URL', 'https://p.oceansaver.in/ajax/progress.php');

// Fonction pour charger les variables d'environnement
function loadEnvFile($envFilePath) {
    if (file_exists($envFilePath)) {
        $envFile = file_get_contents($envFilePath);
        $lines = explode("\n", $envFile);
        foreach ($lines as $line) {
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!defined($key) && !empty($key)) {
                    define($key, $value);
                    // Définir également dans $_ENV pour compatibilité
                    $_ENV[$key] = $value;
                    // Définir dans getenv() pour compatibilité
                    putenv("$key=$value");
                }
            }
        }
        return true;
    }
    return false;
}

// Essayer de charger .env en dehors du répertoire web
$envLoaded = loadEnvFile(ENV_FILE);

// Si le fichier .env externe n'existe pas, essayer avec le fichier dans le répertoire du projet
if (!$envLoaded) {
    loadEnvFile(BASE_DIR . '/.env');
}
