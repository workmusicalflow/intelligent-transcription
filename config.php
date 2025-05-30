<?php

/**
 * Configuration pour l'application de transcription audio
 */

// Chemins
define('BASE_DIR', __DIR__);
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('RESULT_DIR', __DIR__ . '/results');
define('PYTHON_PATH', __DIR__ . '/venv/bin/python'); // Sera remplacé par le chemin vers l'environnement virtuel
define('TEMP_AUDIO_DIR', __DIR__ . '/temp_audio');
define('EXPORT_DIR', __DIR__ . '/exports');

// Limites
define('MAX_UPLOAD_SIZE_MB', 100);
define('MAX_UPLOAD_SIZE_BYTES', MAX_UPLOAD_SIZE_MB * 1024 * 1024);

// Database configuration
define('DB_PATH', __DIR__ . '/database/transcription.db');
define('USE_DATABASE', true); // Set to false to continue using file-based storage

// Récupérer les clés API depuis le fichier .env
$env = parse_ini_file('.env');

// Clé API OpenAI
define('OPENAI_API_KEY', $env['OPENAI_API_KEY'] ?? '');

// Organization ID OpenAI pour le prompt caching
define('OPENAI_ORG_ID', $env['OPENAI_ORG_ID'] ?? 'org-HzNhomFpeY5ewhrUNlmpTehv');

// Vérifier si la clé API OpenAI est configurée
if (!OPENAI_API_KEY) {
    die('Erreur: La clé API OpenAI n\'est pas configurée dans le fichier .env');
}

// Clé API Video Download
define('VIDEO_DOWNLOAD_API_KEY', $env['VIDEO_DOWNLOAD_API_KEY'] ?? '');

// Vérifier si la clé API Video Download est configurée
if (!VIDEO_DOWNLOAD_API_KEY) {
    die('Erreur: La clé API Video Download n\'est pas configurée dans le fichier .env');
}

// URL de base de l'API Video Download
define('VIDEO_DOWNLOAD_API_URL', 'https://loader.to/ajax/download.php');
// L'URL de progression est maintenant récupérée dynamiquement depuis la réponse de l'API
// Cette URL est conservée comme fallback uniquement
define('VIDEO_DOWNLOAD_PROGRESS_URL', 'https://p.oceansaver.in/api/progress');

// Mode debug
define('DEBUG_MODE', true);
