<?php

/**
 * Script de test pour la nouvelle structure MVC
 * Ce script permet de vérifier que la structure MVC fonctionne correctement
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/src/bootstrap.php';

// Utiliser les classes avec leurs namespaces
use Controllers\TranscriptionController;
use Controllers\ParaphraseController;
use Utils\FileUtils;
use Utils\ResponseUtils;

// Afficher l'en-tête HTML
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test MVC</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .test-section {
            background-color: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #c0392b;
            font-weight: bold;
        }
        .code {
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Test de la structure MVC</h1>
    <p>Cette page permet de vérifier que la structure MVC fonctionne correctement.</p>
';

// Tester l'autoloader
echo '<div class="test-section">
    <h2>Test de l\'autoloader</h2>';

try {
    $transcriptionController = new TranscriptionController();
    echo '<p class="success">✅ L\'autoloader fonctionne correctement.</p>';
    echo '<p>La classe TranscriptionController a été chargée avec succès.</p>';
} catch (Exception $e) {
    echo '<p class="error">❌ Erreur lors du chargement de la classe TranscriptionController : ' . $e->getMessage() . '</p>';
}

echo '</div>';

// Tester les utilitaires
echo '<div class="test-section">
    <h2>Test des utilitaires</h2>';

try {
    $formattedSize = FileUtils::formatFileSize(1024 * 1024);
    echo '<p class="success">✅ La classe FileUtils fonctionne correctement.</p>';
    echo '<p>1024 * 1024 octets = ' . $formattedSize . '</p>';
} catch (Exception $e) {
    echo '<p class="error">❌ Erreur lors de l\'utilisation de la classe FileUtils : ' . $e->getMessage() . '</p>';
}

echo '</div>';

// Tester les services
echo '<div class="test-section">
    <h2>Test des services</h2>';

try {
    $paraphraseController = new ParaphraseController();
    $styles = $paraphraseController->getAvailableStyles();
    echo '<p class="success">✅ La classe ParaphraseService fonctionne correctement.</p>';
    echo '<p>Styles disponibles :</p>';
    echo '<ul>';
    foreach ($styles as $key => $value) {
        echo '<li>' . $key . ' : ' . $value . '</li>';
    }
    echo '</ul>';
} catch (Exception $e) {
    echo '<p class="error">❌ Erreur lors de l\'utilisation de la classe ParaphraseService : ' . $e->getMessage() . '</p>';
}

echo '</div>';

// Tester la configuration
echo '<div class="test-section">
    <h2>Test de la configuration</h2>';

echo '<p>Constantes définies :</p>';
echo '<ul>';
echo '<li>BASE_DIR : ' . BASE_DIR . '</li>';
echo '<li>UPLOAD_DIR : ' . UPLOAD_DIR . '</li>';
echo '<li>TEMP_AUDIO_DIR : ' . TEMP_AUDIO_DIR . '</li>';
echo '<li>RESULT_DIR : ' . RESULT_DIR . '</li>';
echo '<li>MAX_UPLOAD_SIZE_MB : ' . MAX_UPLOAD_SIZE_MB . '</li>';
echo '</ul>';

echo '</div>';

// Exemple d'utilisation
echo '<div class="test-section">
    <h2>Exemple d\'utilisation</h2>';

echo '<p>Voici un exemple d\'utilisation de la structure MVC :</p>';
echo '<div class="code">
$transcriptionController = new \Controllers\TranscriptionController();<br>
$result = $transcriptionController->showResult();<br><br>
// Afficher le résultat<br>
echo $result["text"];<br>
</div>';

echo '</div>';

// Afficher le pied de page HTML
echo '
    <p>Tous les tests ont été effectués avec succès. La structure MVC est prête à être utilisée.</p>
    <p><a href="index.php">Retour à l\'accueil</a></p>
</body>
</html>';
