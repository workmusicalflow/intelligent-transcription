<?php

/**
 * Script de téléchargement de vidéos YouTube
 */

// Inclure les fichiers nécessaires
require_once 'config.php';
require_once 'utils.php';

// Vérifier si une URL YouTube a été fournie
if (!isset($_POST['youtube_url']) || empty($_POST['youtube_url'])) {
    redirect('index.php?error=youtube&message=' . urlencode('Aucune URL YouTube fournie'));
}

$youtubeUrl = $_POST['youtube_url'];
$language = $_POST['language'] ?? 'auto';
$forceLanguage = isset($_POST['force_language']) ? true : false;

// Valider l'URL YouTube
if (!isValidYoutubeUrl($youtubeUrl)) {
    redirect('index.php?error=youtube&message=' . urlencode('URL YouTube invalide'));
}

// Créer le répertoire de téléchargement si nécessaire
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Générer un nom de fichier unique
$uniqueId = uniqid('audio_');
$filename = $uniqueId . '_' . getYoutubeVideoId($youtubeUrl) . '.mp3';
$filePath = UPLOAD_DIR . '/' . $filename;

// Télécharger la vidéo YouTube en utilisant l'API loader.to
$format = 'mp3';
$apiKey = VIDEO_DOWNLOAD_API_KEY;
$encodedUrl = urlencode($youtubeUrl);

// Construire l'URL avec les paramètres de requête
$apiUrl = VIDEO_DOWNLOAD_API_URL . "?format={$format}&url={$encodedUrl}&api={$apiKey}";

// Initialiser cURL pour la requête initiale
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Ajouter un timeout pour éviter les blocages
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Exécuter la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Enregistrer les informations de débogage
$debug_info = [
    'youtube_url' => $youtubeUrl,
    'api_url' => $apiUrl,
    'http_code' => $httpCode,
    'response' => $response,
    'error' => $error
];
file_put_contents('debug_youtube_download.log', print_r($debug_info, true));

// Implémenter un backoff exponentiel en cas d'erreur
$maxRetries = 3;
$retryCount = 0;
$retryDelay = 2;

while ($httpCode !== 200 && $retryCount < $maxRetries) {
    // Attendre avant de réessayer (backoff exponentiel)
    sleep($retryDelay);
    $retryDelay *= 2;
    $retryCount++;

    // Réessayer la requête
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Enregistrer les informations de débogage pour la tentative
    $retry_debug_info = [
        'retry' => $retryCount,
        'delay' => $retryDelay / 2,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
    file_put_contents('debug_youtube_download.log', print_r($retry_debug_info, true), FILE_APPEND);
}

// Vérifier si la requête a réussi après les tentatives
if ($httpCode !== 200 || $error) {
    redirect('index.php?error=youtube&message=' . urlencode('Erreur lors du téléchargement de la vidéo: ' . ($error ?: 'Code HTTP ' . $httpCode)));
}

// Décoder la réponse JSON
$result = json_decode($response, true);

if (!$result || !isset($result['success']) || !$result['success']) {
    redirect('index.php?error=youtube&message=' . urlencode('Réponse API invalide'));
}

// Récupérer l'ID de la demande de téléchargement
$downloadId = $result['id'] ?? '';
if (empty($downloadId)) {
    redirect('index.php?error=youtube&message=' . urlencode('ID de téléchargement non trouvé dans la réponse'));
}

// Vérifier la progression du téléchargement et attendre qu'il soit terminé
$downloadUrl = null;
$maxAttempts = 30; // Nombre maximum de tentatives
$attempts = 0;
$waitTime = 2; // Temps d'attente entre les tentatives en secondes

while ($attempts < $maxAttempts) {
    // Attendre avant de vérifier la progression (conversion explicite en entier)
    sleep((int)$waitTime);

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
        // Implémenter un backoff pour les erreurs de progression
        $waitTime = min($waitTime * 1.5, 10); // Augmenter le temps d'attente, max 10 secondes
        $attempts++;
        continue;
    }

    // Décoder la réponse JSON
    $progressResult = json_decode($progressResponse, true);

    // Enregistrer les informations de progression
    $progress_debug_info = [
        'attempt' => $attempts + 1,
        'progress_url' => VIDEO_DOWNLOAD_PROGRESS_URL,
        'http_code' => $progressHttpCode,
        'response' => $progressResult,
        'error' => $progressError
    ];
    file_put_contents('debug_youtube_progress.log', print_r($progress_debug_info, true), FILE_APPEND);

    // Vérifier si le téléchargement est terminé
    if (isset($progressResult['success']) && $progressResult['success'] == 1 && isset($progressResult['download_url'])) {
        $downloadUrl = $progressResult['download_url'];
        break;
    }

    // Si la progression est à 100% mais pas d'URL de téléchargement, attendre encore un peu
    if (isset($progressResult['progress']) && $progressResult['progress'] >= 1000) {
        $waitTime = 1; // Réduire le temps d'attente
    } else {
        // Augmenter légèrement le temps d'attente pour les téléchargements plus longs
        $waitTime = min($waitTime * 1.2, 5.0);
    }

    $attempts++;
}

// Vérifier si nous avons obtenu une URL de téléchargement
if (empty($downloadUrl)) {
    redirect('index.php?error=youtube&message=' . urlencode('Impossible d\'obtenir l\'URL de téléchargement après plusieurs tentatives'));
}

// Télécharger le fichier audio depuis l'URL fournie avec gestion des erreurs
$maxDownloadRetries = 3;
$downloadRetries = 0;
$fileContent = false;

while ($fileContent === false && $downloadRetries < $maxDownloadRetries) {
    $fileContent = @file_get_contents($downloadUrl);
    if ($fileContent === false) {
        $downloadRetries++;
        sleep(2 * $downloadRetries);
    }
}

if ($fileContent === false) {
    redirect('index.php?error=youtube&message=' . urlencode('Impossible de télécharger le fichier audio après plusieurs tentatives'));
}

// Enregistrer le fichier audio
if (file_put_contents($filePath, $fileContent) === false) {
    redirect('index.php?error=youtube&message=' . urlencode('Impossible d\'enregistrer le fichier audio'));
}

// Créer le répertoire temporaire pour les fichiers prétraités
$tempDir = __DIR__ . '/temp_audio';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Prétraiter le fichier audio pour réduire sa taille
$pythonPath = PYTHON_PATH;
$preprocessScript = __DIR__ . '/preprocess_audio.py';

$preprocessCommand = escapeshellcmd($pythonPath) . ' ' .
    escapeshellarg($preprocessScript) . ' ' .
    '--file=' . escapeshellarg($filePath) . ' ' .
    '--output_dir=' . escapeshellarg($tempDir) . ' ' .
    '--target_size_mb=24';

// Exécuter la commande de prétraitement
$preprocessOutput = shell_exec($preprocessCommand);
$preprocessResult = json_decode($preprocessOutput, true);

// Enregistrer les informations de prétraitement
$preprocess_debug_info = [
    'command' => $preprocessCommand,
    'output' => $preprocessOutput,
    'result' => $preprocessResult
];
file_put_contents('debug_preprocess.log', print_r($preprocess_debug_info, true));

if (!$preprocessResult || !isset($preprocessResult['success']) || !$preprocessResult['success']) {
    $error = $preprocessResult['error'] ?? 'Erreur inconnue';
    redirect('index.php?error=preprocess&message=' . urlencode($error));
}

// Utiliser le fichier prétraité pour la transcription
$filePath = $preprocessResult['output_file'];

// Créer le répertoire de résultats si nécessaire
if (!is_dir(RESULT_DIR)) {
    mkdir(RESULT_DIR, 0777, true);
}

// Générer un nom de fichier pour le résultat
$resultId = generateUniqueId();
$resultPath = RESULT_DIR . '/' . $resultId . '.json';

// Exécuter le script Python
$pythonPath = PYTHON_PATH;
$scriptPath = __DIR__ . '/transcribe.py';

$command = escapeshellcmd($pythonPath) . ' ' .
    escapeshellarg($scriptPath) . ' ' .
    '--file=' . escapeshellarg($filePath) . ' ' .
    '--output=' . escapeshellarg($resultPath);

// Toujours transmettre le paramètre de langue, même pour "auto"
// Si "auto", on passe une chaîne vide pour que l'API utilise la détection automatique
if ($language === 'auto') {
    $command .= ' --language=""';
} else {
    $command .= ' --language=' . escapeshellarg($language);

    // Ajouter le paramètre pour forcer la traduction dans la langue spécifiée
    // seulement si la case à cocher est cochée
    if ($forceLanguage) {
        $command .= ' --force-language';
    }
}

// Exécuter la commande et capturer la sortie standard et d'erreur
$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin
    1 => array("pipe", "w"),  // stdout
    2 => array("pipe", "w")   // stderr
);

$process = proc_open($command, $descriptorspec, $pipes);

if (is_resource($process)) {
    // Lire la sortie standard
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Lire la sortie d'erreur
    $error_output = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // Fermer le processus
    $return_value = proc_close($process);

    // Enregistrer les informations de débogage
    $debug_info = [
        'command' => $command,
        'output' => $output,
        'error_output' => $error_output,
        'return_value' => $return_value
    ];
    file_put_contents('debug_transcribe.log', print_r($debug_info, true));

    // Décoder la sortie JSON
    $result = json_decode($output, true);
} else {
    // Impossible de démarrer le processus
    file_put_contents('debug_transcribe.log', "Impossible de démarrer le processus: $command");
    redirect('index.php?error=transcription&message=' . urlencode("Impossible de démarrer le processus de transcription"));
}

// Vérifier si la transcription a réussi
if (!$result || !isset($result['success']) || !$result['success']) {
    $error = $result['error'] ?? 'Erreur inconnue';
    redirect('index.php?error=transcription&message=' . urlencode($error));
}

// Rediriger vers la page de résultat
redirect('result.php?id=' . $resultId);
