<?php
/**
 * Page de suivi de traitement asynchrone
 */

// Inclure les fichiers requis
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/autoload.php';

// Initialiser les services
$controller = new Controllers\TranscriptionController();
$twigManager = new Template\TwigManager();

// Obtenir les données de traitement
$processingData = $controller->showProcessingStatus();

// Afficher la vue
echo $twigManager->render('processing/show.twig', $processingData);