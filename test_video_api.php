<?php

/**
 * Script de test pour l'API loader.to
 */

// Inclure les fichiers nécessaires
require_once 'config.php';

// URL de test YouTube
$testUrl = 'https://www.youtube.com/watch?v=AJpK3YTTKZ4';

// Fonction pour tester l'API
function testVideoDownloadApi($youtubeUrl)
{
    $format = 'mp3';
    $apiKey = VIDEO_DOWNLOAD_API_KEY;
    $encodedUrl = urlencode($youtubeUrl);

    // Construire l'URL avec les paramètres de requête
    $apiUrl = VIDEO_DOWNLOAD_API_URL . "?format={$format}&url={$encodedUrl}&api={$apiKey}";

    echo "Test de l'API loader.to\n";
    echo "URL: $apiUrl\n";
    echo "URL YouTube: $youtubeUrl\n\n";

    // Initialiser cURL
    $ch = curl_init($apiUrl);
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
            return false;
        }

        // Récupérer l'ID de la demande de téléchargement
        $downloadId = $result['id'] ?? '';
        if (empty($downloadId)) {
            echo "Erreur: ID de téléchargement non trouvé dans la réponse\n";
            return false;
        }

        echo "\nID de téléchargement: $downloadId\n";
        echo "Vérification de la progression du téléchargement...\n";

        // Vérifier la progression du téléchargement
        $downloadUrl = null;
        $maxAttempts = 30; // Nombre maximum de tentatives
        $attempts = 0;
        $waitTime = 2; // Temps d'attente entre les tentatives en secondes

        while ($attempts < $maxAttempts) {
            // Attendre avant de vérifier la progression
            echo "Tentative " . ($attempts + 1) . "/$maxAttempts - Attente de $waitTime secondes...\n";
            sleep($waitTime);

            // Récupérer l'URL de progression depuis la réponse de l'API
            $progressUrl = $result['progress_url'] ?? (VIDEO_DOWNLOAD_PROGRESS_URL . "?id={$downloadId}");

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
            return false;
        }

        // Tester le téléchargement du fichier
        echo "\nTest de téléchargement du fichier...\n";
        $fileContent = file_get_contents($downloadUrl);

        if ($fileContent === false) {
            echo "Erreur: Impossible de télécharger le fichier\n";
            return false;
        }

        echo "Téléchargement réussi! Taille du fichier: " . strlen($fileContent) . " octets\n";
        return true;
    } else {
        echo "Réponse: $response\n";
        return false;
    }
}

// Tester l'API
$result = testVideoDownloadApi($testUrl);

if ($result) {
    echo "\nTest réussi! L'API fonctionne correctement.\n";
} else {
    echo "\nTest échoué. Vérifiez les détails ci-dessus pour plus d'informations.\n";
}

echo "\nPour exécuter ce test, utilisez la commande suivante:\n";
echo "php test_video_api.php\n";
