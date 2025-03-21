<?php

/**
 * Script de test pour le serveur mock de l'API loader.to
 */

// URL de test YouTube
$testUrl = 'https://www.youtube.com/watch?v=AJpK3YTTKZ4';
$mockApiUrl = 'http://localhost:8080/download.php';
$mockProgressUrl = 'http://localhost:8080/progress.php';
$apiKey = '6ac76ef3deb3bb8c2e9f8860938e86516291e90a'; // Clé fictive pour le test

echo "Test du serveur mock pour l'API loader.to\n";
echo "URL: $mockApiUrl\n";
echo "URL YouTube: $testUrl\n\n";

// Préparer les paramètres pour l'API
$format = 'mp3';
$encodedUrl = urlencode($testUrl);
$fullUrl = $mockApiUrl . "?format={$format}&url={$encodedUrl}&api={$apiKey}";

echo "URL complète: $fullUrl\n\n";

// Initialiser cURL
$ch = curl_init($fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Activer le mode verbose pour voir les détails de la requête
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Exécuter la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Obtenir les informations détaillées de la requête
rewind($verbose);
$verboseLog = stream_get_contents($verbose);

curl_close($ch);

// Afficher les résultats
echo "Code HTTP: $httpCode\n";

if ($error) {
    echo "Erreur cURL: $error\n";
}

echo "\nDétails de la requête:\n$verboseLog\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "Réponse: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

    // Vérifier si la requête a réussi
    if (!$result || !isset($result['success']) || !$result['success']) {
        echo "Erreur: Réponse API invalide\n";
        exit(1);
    }

    // Récupérer l'ID de la demande de téléchargement
    $downloadId = $result['id'] ?? '';
    if (empty($downloadId)) {
        echo "Erreur: ID de téléchargement non trouvé dans la réponse\n";
        exit(1);
    }

    echo "\nID de téléchargement: $downloadId\n";
    echo "Vérification de la progression du téléchargement...\n";

    // Vérifier la progression du téléchargement
    $downloadUrl = null;
    $maxAttempts = 10; // Nombre maximum de tentatives
    $attempts = 0;
    $waitTime = 1; // Temps d'attente entre les tentatives en secondes

    while ($attempts < $maxAttempts) {
        // Attendre avant de vérifier la progression
        echo "Tentative " . ($attempts + 1) . "/$maxAttempts - Attente de $waitTime secondes...\n";
        sleep($waitTime);

        // Construire l'URL de progression avec l'ID
        $progressUrl = $mockProgressUrl . "?id={$downloadId}";

        // Initialiser cURL pour la requête de progression
        $ch = curl_init($progressUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        // Exécuter la requête
        $progressResponse = curl_exec($ch);
        $progressHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $progressError = curl_error($ch);
        curl_close($ch);

        // Vérifier si la requête a réussi
        if ($progressHttpCode !== 200 || $progressError) {
            echo "Erreur lors de la vérification de la progression: " . ($progressError ?: "Code HTTP $progressHttpCode") . "\n";
            $attempts++;
            continue;
        }

        // Décoder la réponse JSON
        $progressResult = json_decode($progressResponse, true);

        // Afficher la progression
        $progress = $progressResult['progress'] ?? 0;
        $progressPercent = ($progress / 10) . '%';
        $progressText = $progressResult['text'] ?? 'En cours';
        echo "Progression: $progressPercent - $progressText\n";

        // Vérifier si le téléchargement est terminé
        if (isset($progressResult['success']) && $progressResult['success'] == 1 && isset($progressResult['download_url'])) {
            $downloadUrl = $progressResult['download_url'];
            echo "Téléchargement terminé!\n";
            echo "URL de téléchargement: $downloadUrl\n";
            break;
        }

        // Si la progression est à 1000 (100%) mais pas d'URL de téléchargement, attendre encore un peu
        if (isset($progressResult['progress']) && $progressResult['progress'] >= 1000) {
            echo "Progression à 100% mais pas d'URL de téléchargement, attente supplémentaire...\n";
            $attempts++;
            $waitTime = 1; // Réduire le temps d'attente
            continue;
        }

        $attempts++;
    }

    // Vérifier si nous avons obtenu une URL de téléchargement
    if (empty($downloadUrl)) {
        echo "Erreur: Impossible d'obtenir l'URL de téléchargement après $maxAttempts tentatives\n";
        exit(1);
    }

    // Tester le téléchargement du fichier
    echo "\nTest de téléchargement du fichier...\n";
    $fileContent = file_get_contents($downloadUrl);

    if ($fileContent === false) {
        echo "Erreur: Impossible de télécharger le fichier\n";
        exit(1);
    }

    echo "Téléchargement réussi! Contenu du fichier: " . $fileContent . "\n";
    echo "\nTest réussi! Le serveur mock fonctionne correctement.\n";
} else {
    echo "Réponse: $response\n";
    echo "\nTest échoué. Vérifiez les détails ci-dessus pour plus d'informations.\n";
    exit(1);
}

echo "\nPour utiliser le serveur mock avec l'application, suivez ces étapes:\n";
echo "1. Démarrez le serveur mock: php -S localhost:8080 mock_video_api.php\n";
echo "2. Modifiez config.php pour utiliser l'URL du serveur mock:\n";
echo "   define('VIDEO_DOWNLOAD_API_URL', 'http://localhost:8080/download.php');\n";
echo "   define('VIDEO_DOWNLOAD_PROGRESS_URL', 'http://localhost:8080/progress.php');\n";
