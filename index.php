<?php

/**
 * Page d'accueil de l'application de transcription audio
 */

// Charger uniquement config.php du répertoire racine, pas src/config.php
require_once __DIR__ . '/config.php';

// Inclure ensuite le bootstrap
require_once __DIR__ . '/src/bootstrap.php';

use Template\TwigManager;
use Utils\FileUtils; // Assuming FileUtils is in the Utils namespace
use Utils\ResponseUtils; // Assuming ResponseUtils is in the Utils namespace

// Nettoyer les anciens fichiers
FileUtils::cleanupOldFiles(UPLOAD_DIR);
FileUtils::cleanupOldFiles(RESULT_DIR);

// Get error message if any
$errorMessage = null;
if (isset($_GET['error'])) {
    $errorCode = $_GET['error'];
    // Assuming getErrorMessage is in utils.php and utils.php is included via bootstrap
    $errorMessage = getErrorMessage($errorCode, $_GET['message'] ?? null);
}

// Data to pass to the template
$templateData = [
    'max_upload_size_mb' => MAX_UPLOAD_SIZE_MB,
    'error_message' => $errorMessage,
    'active_page' => 'home', // Set active page for navigation highlighting
];

// Render the home page template
TwigManager::display('home/index.twig', $templateData);
