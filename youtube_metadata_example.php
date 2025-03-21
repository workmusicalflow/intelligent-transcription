<?php

/**
 * Exemple d'implémentation pour exploiter les métadonnées de vidéo YouTube
 * 
 * Ce fichier montre comment utiliser les données retournées par l'API loader.to
 * pour améliorer l'expérience utilisateur lors du téléchargement et de la transcription
 * de vidéos YouTube.
 */

// Inclure les fichiers nécessaires
require_once 'config.php';
require_once 'utils.php';

/**
 * Fonction qui gère le téléchargement d'une vidéo YouTube et affiche une interface utilisateur améliorée
 * 
 * @param string $youtubeUrl URL de la vidéo YouTube
 * @param string $language Langue de la transcription
 * @return string|bool ID du résultat ou false en cas d'erreur
 */
function downloadAndProcessYoutubeVideo($youtubeUrl, $language = 'auto')
{
    // Valider l'URL YouTube
    if (!isValidYoutubeUrl($youtubeUrl)) {
        displayError('URL YouTube invalide');
        return false;
    }

    // Initialiser la session pour stocker les informations de progression
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Générer un ID unique pour cette demande
    $requestId = uniqid('youtube_');
    $_SESSION['youtube_request'] = $requestId;

    // Afficher l'interface de progression
    displayProgressInterface($requestId);

    // Télécharger la vidéo YouTube en utilisant l'API loader.to
    $format = 'mp3';
    $apiKey = VIDEO_DOWNLOAD_API_KEY;
    $encodedUrl = urlencode($youtubeUrl);
    $apiUrl = VIDEO_DOWNLOAD_API_URL . "?format={$format}&url={$encodedUrl}&api={$apiKey}";

    // Initialiser cURL pour la requête initiale
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Vérifier si la requête a réussi
    if ($httpCode !== 200 || $error) {
        displayError('Erreur lors du téléchargement de la vidéo: ' . ($error ?: 'Code HTTP ' . $httpCode));
        return false;
    }

    // Décoder la réponse JSON
    $result = json_decode($response, true);

    if (!$result || !isset($result['success']) || !$result['success']) {
        $errorMessage = $result['message'] ?? 'Réponse API invalide';
        displayError($errorMessage);
        return false;
    }

    // Extraire les métadonnées de la vidéo
    $videoTitle = $result['title'] ?? $result['info']['title'] ?? 'Vidéo YouTube';
    $videoThumbnail = $result['info']['image'] ?? '';
    $downloadId = $result['id'] ?? '';
    $progressUrl = $result['progress_url'] ?? (VIDEO_DOWNLOAD_PROGRESS_URL . "?id={$downloadId}");
    $cachehash = $result['cachehash'] ?? '';

    // Stocker les informations dans la session
    $_SESSION['youtube_metadata'] = [
        'title' => $videoTitle,
        'thumbnail' => $videoThumbnail,
        'download_id' => $downloadId,
        'progress_url' => $progressUrl,
        'cachehash' => $cachehash,
        'language' => $language
    ];

    // Mettre à jour l'interface avec les métadonnées
    updateInterfaceWithMetadata($videoTitle, $videoThumbnail, $requestId);

    // Démarrer le processus de vérification de la progression en arrière-plan
    // (Dans une application réelle, cela serait fait via AJAX)
    $resultId = processVideoDownload($downloadId, $progressUrl, $language);

    return $resultId;
}

/**
 * Affiche l'interface de progression initiale
 * 
 * @param string $requestId ID unique de la requête
 */
function displayProgressInterface($requestId)
{
?>
    <div class="youtube-download-container" id="youtube-container-<?php echo htmlspecialchars($requestId); ?>">
        <div class="video-info" id="video-info-<?php echo htmlspecialchars($requestId); ?>">
            <div class="loading-placeholder">
                <div class="spinner"></div>
                <p>Récupération des informations de la vidéo...</p>
            </div>
        </div>

        <div class="progress-container">
            <div class="progress-label">Progression: <span id="progress-text-<?php echo htmlspecialchars($requestId); ?>">En
                    attente...</span></div>
            <div class="progress-bar-container">
                <div class="progress-bar" id="progress-bar-<?php echo htmlspecialchars($requestId); ?>" style="width: 0%">
                </div>
            </div>
        </div>

        <div class="status-message" id="status-message-<?php echo htmlspecialchars($requestId); ?>"></div>
    </div>

    <script>
        // JavaScript pour mettre à jour la progression
        document.addEventListener('DOMContentLoaded', function() {
            const requestId = '<?php echo $requestId; ?>';
            checkProgress(requestId);
        });

        function checkProgress(requestId) {
            // Dans une application réelle, cela ferait un appel AJAX à un endpoint PHP
            // qui vérifierait la progression et retournerait les données
            fetch('check_youtube_progress.php?request_id=' + requestId)
                .then(response => response.json())
                .then(data => {
                    updateProgressUI(data, requestId);

                    if (data.status !== 'completed') {
                        // Vérifier à nouveau après un délai
                        setTimeout(() => checkProgress(requestId), 2000);
                    } else {
                        // Rediriger vers la page de résultat
                        window.location.href = 'result.php?id=' + data.result_id;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la vérification de la progression:', error);
                    document.getElementById('status-message-' + requestId).textContent =
                        'Erreur lors de la vérification de la progression. Réessai dans 5 secondes...';

                    // Réessayer après un délai plus long en cas d'erreur
                    setTimeout(() => checkProgress(requestId), 5000);
                });
        }

        function updateProgressUI(data, requestId) {
            // Mettre à jour la barre de progression
            const progressBar = document.getElementById('progress-bar-' + requestId);
            const progressText = document.getElementById('progress-text-' + requestId);
            const statusMessage = document.getElementById('status-message-' + requestId);

            if (progressBar && progressText) {
                // La progression est sur 1000, donc diviser par 10 pour obtenir un pourcentage
                const percentage = (data.progress / 10).toFixed(1);
                progressBar.style.width = percentage + '%';
                progressText.textContent = percentage + '% - ' + data.text;

                // Mettre à jour le message de statut
                if (data.status === 'downloading') {
                    statusMessage.textContent = 'Téléchargement de la vidéo en cours...';
                } else if (data.status === 'converting') {
                    statusMessage.textContent = 'Conversion de la vidéo en audio...';
                } else if (data.status === 'transcribing') {
                    statusMessage.textContent = 'Transcription de l\'audio en cours...';
                } else if (data.status === 'completed') {
                    statusMessage.textContent = 'Terminé! Redirection vers les résultats...';
                }
            }
        }
    </script>
<?php
}

/**
 * Met à jour l'interface avec les métadonnées de la vidéo
 * 
 * @param string $title Titre de la vidéo
 * @param string $thumbnail URL de la miniature
 * @param string $requestId ID unique de la requête
 */
function updateInterfaceWithMetadata($title, $thumbnail, $requestId)
{
    // Dans une application réelle, cela serait fait via AJAX
    // Ici, nous simulons la mise à jour de l'interface
?>
    <script>
        // Mettre à jour l'interface avec les métadonnées
        document.addEventListener('DOMContentLoaded', function() {
            const videoInfoContainer = document.getElementById('video-info-<?php echo $requestId; ?>');
            if (videoInfoContainer) {
                videoInfoContainer.innerHTML = `
                <div class="video-metadata">
                    <img src="<?php echo htmlspecialchars($thumbnail); ?>" alt="Miniature de la vidéo" class="video-thumbnail">
                    <h3 class="video-title"><?php echo htmlspecialchars($title); ?></h3>
                </div>
            `;
            }
        });
    </script>
<?php
}

/**
 * Affiche un message d'erreur
 * 
 * @param string $message Message d'erreur
 */
function displayError($message)
{
?>
    <div class="error-message">
        <i class="error-icon">⚠️</i>
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php
}

/**
 * Traite le téléchargement de la vidéo et suit la progression
 * 
 * @param string $downloadId ID de téléchargement
 * @param string $progressUrl URL pour vérifier la progression
 * @param string $language Langue de la transcription
 * @return string|bool ID du résultat ou false en cas d'erreur
 */
function processVideoDownload($downloadId, $progressUrl, $language)
{
    // Dans une application réelle, ce processus serait géré par des appels AJAX
    // et des scripts en arrière-plan

    // Simuler le processus de téléchargement et de transcription
    $downloadUrl = null;
    $maxAttempts = 30;
    $attempts = 0;
    $waitTime = 2;

    while ($attempts < $maxAttempts) {
        // Attendre avant de vérifier la progression
        sleep($waitTime);

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
            $attempts++;
            continue;
        }

        // Décoder la réponse JSON
        $progressResult = json_decode($progressResponse, true);

        // Vérifier si le téléchargement est terminé
        if (isset($progressResult['success']) && $progressResult['success'] == 1 && isset($progressResult['download_url'])) {
            $downloadUrl = $progressResult['download_url'];
            break;
        }

        $attempts++;
    }

    // Vérifier si nous avons obtenu une URL de téléchargement
    if (empty($downloadUrl)) {
        return false;
    }

    // Télécharger le fichier audio
    $uniqueId = uniqid('audio_');
    $filename = $uniqueId . '_youtube.mp3';
    $filePath = UPLOAD_DIR . '/' . $filename;

    $fileContent = file_get_contents($downloadUrl);
    if ($fileContent === false) {
        return false;
    }

    // Enregistrer le fichier audio
    if (file_put_contents($filePath, $fileContent) === false) {
        return false;
    }

    // Prétraiter et transcrire le fichier audio
    // (Code simplifié pour l'exemple)
    $resultId = generateUniqueId();
    $resultPath = RESULT_DIR . '/' . $resultId . '.json';

    // Simuler la transcription
    $transcriptionResult = [
        'success' => true,
        'text' => 'Transcription simulée pour ' . $downloadId,
        'language' => $language,
        'duration' => 120,
        'source' => 'youtube',
        'youtube_url' => $_SESSION['youtube_metadata']['title'] ?? 'Vidéo YouTube'
    ];

    // Enregistrer le résultat
    file_put_contents($resultPath, json_encode($transcriptionResult, JSON_PRETTY_PRINT));

    return $resultId;
}

/**
 * Génère un ID unique
 * 
 * @return string ID unique
 */
function generateUniqueId()
{
    return uniqid('', true);
}

// Exemple d'utilisation
if (isset($_POST['youtube_url'])) {
    $youtubeUrl = $_POST['youtube_url'];
    $language = $_POST['language'] ?? 'auto';

    $resultId = downloadAndProcessYoutubeVideo($youtubeUrl, $language);

    if ($resultId) {
        // Dans une application réelle, la redirection serait gérée par JavaScript
        // après que le processus de téléchargement et de transcription soit terminé
        echo '<p>Traitement en cours. Vous serez redirigé vers la page de résultat une fois terminé.</p>';
    }
}
?>

<!-- CSS pour l'interface -->
<style>
    .youtube-download-container {
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .video-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 20px;
    }

    .loading-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 10px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .video-metadata {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
    }

    .video-thumbnail {
        max-width: 320px;
        height: auto;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .video-title {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        margin: 10px 0;
    }

    .progress-container {
        margin: 20px 0;
    }

    .progress-label {
        margin-bottom: 5px;
        font-weight: bold;
    }

    .progress-bar-container {
        height: 20px;
        background-color: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background-color: #4CAF50;
        width: 0%;
        transition: width 0.5s ease;
    }

    .status-message {
        margin-top: 10px;
        font-style: italic;
        color: #666;
    }

    .error-message {
        display: flex;
        align-items: center;
        padding: 15px;
        background-color: #ffebee;
        border-left: 4px solid #f44336;
        margin: 20px 0;
    }

    .error-icon {
        font-size: 24px;
        margin-right: 10px;
    }
</style>

<!-- Formulaire d'exemple -->
<div class="form-container">
    <h2>Transcrire une vidéo YouTube</h2>
    <form method="post" action="">
        <div class="form-group">
            <label for="youtube_url">URL YouTube:</label>
            <input type="text" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..."
                required>
        </div>

        <div class="form-group">
            <label for="language">Langue:</label>
            <select id="language" name="language">
                <option value="auto">Détection automatique</option>
                <option value="fr">Français</option>
                <option value="en">Anglais</option>
                <option value="es">Espagnol</option>
                <option value="de">Allemand</option>
                <option value="it">Italien</option>
            </select>
        </div>

        <div class="form-group">
            <button type="submit" class="submit-button">Transcrire</button>
        </div>
    </form>
</div>

<!-- CSS pour le formulaire -->
<style>
    .form-container {
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .form-container h2 {
        margin-top: 0;
        color: #333;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group input[type="text"],
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .submit-button {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
    }

    .submit-button:hover {
        background-color: #45a049;
    }
</style>