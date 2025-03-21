<?php

/**
 * Script de traitement de la transcription audio
 */

// Inclure les fichiers nécessaires
require_once 'config.php';
require_once 'utils.php';

// Enregistrer les informations de débogage
$debug_info = [
    'FILES' => $_FILES,
    'POST' => $_POST,
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
];
file_put_contents('debug_upload.log', print_r($debug_info, true));

// Vérifier si un fichier a été téléchargé
if (!isset($_FILES['audio_file'])) {
    redirect('index.php?error=upload&message=' . urlencode('Aucun fichier téléchargé'));
}

if ($_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite définie dans php.ini (upload_max_filesize)',
        UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite définie dans le formulaire HTML (MAX_FILE_SIZE)',
        UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
    ];
    $error_code = $_FILES['audio_file']['error'];
    $error_message = $error_messages[$error_code] ?? 'Erreur inconnue';
    redirect('index.php?error=upload&message=' . urlencode($error_message));
}

$file = $_FILES['audio_file'];
$language = $_POST['language'] ?? 'auto';
$forceLanguage = isset($_POST['force_language']) ? true : false;

// Vérifier la taille du fichier
if ($file['size'] > MAX_UPLOAD_SIZE_BYTES) {
    redirect('index.php?error=size&max=' . MAX_UPLOAD_SIZE_MB);
}

// Créer le répertoire de téléchargement si nécessaire
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Générer un nom de fichier unique
$filename = uniqid('audio_') . '_' . basename($file['name']);
$filePath = UPLOAD_DIR . '/' . $filename;

// Déplacer le fichier téléchargé
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    redirect('index.php?error=move');
}

// Vérifier si le fichier est un fichier audio ou vidéo valide
if (!isValidMediaFile($filePath)) {
    // Supprimer le fichier invalide
    unlink($filePath);
    redirect('index.php?error=invalid_file');
}

// Créer le répertoire de résultats si nécessaire
if (!is_dir(RESULT_DIR)) {
    mkdir(RESULT_DIR, 0777, true);
}

// Créer un répertoire temporaire pour les fichiers prétraités
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
