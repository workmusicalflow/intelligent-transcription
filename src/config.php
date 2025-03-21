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

// Charger les variables d'environnement depuis .env
if (file_exists(BASE_DIR . '/.env')) {
    $envFile = file_get_contents(BASE_DIR . '/.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!defined($key)) {
            define($key, $value);
        }
    }
}
