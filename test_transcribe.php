<?php

/**
 * Script de test pour la transcription audio
 */

// Inclure les fichiers nécessaires
require_once 'config.php';
require_once 'utils.php';

// Chemin vers un fichier audio de test
$testFile = __DIR__ . '/uploads/audio_6813d6d11b58d_hMirZ5dwUSo.mp3';

// Vérifier si le fichier existe
if (!file_exists($testFile)) {
    echo "Le fichier de test n'existe pas: $testFile\n";
    echo "Veuillez d'abord télécharger un fichier audio via l'interface web.\n";
    exit(1);
}

echo "Fichier de test trouvé: $testFile\n";

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
echo "Prétraitement du fichier audio...\n";
$pythonPath = PYTHON_PATH;
$preprocessScript = __DIR__ . '/preprocess_audio.py';

$preprocessCommand = escapeshellcmd($pythonPath) . ' ' .
    escapeshellarg($preprocessScript) . ' ' .
    '--file=' . escapeshellarg($testFile) . ' ' .
    '--output_dir=' . escapeshellarg($tempDir) . ' ' .
    '--target_size_mb=24';

echo "Exécution de la commande de prétraitement: $preprocessCommand\n";

$preprocessOutput = shell_exec($preprocessCommand);
$preprocessResult = json_decode($preprocessOutput, true);

if (!$preprocessResult || !isset($preprocessResult['success']) || !$preprocessResult['success']) {
    $error = $preprocessResult['error'] ?? 'Erreur inconnue';
    echo "Erreur lors du prétraitement: $error\n";
    exit(1);
}

echo "Prétraitement réussi!\n";
echo "Fichier original: " . $preprocessResult['original_size_mb'] . " Mo\n";
echo "Fichier prétraité: " . $preprocessResult['new_size_mb'] . " Mo\n";

// Utiliser le fichier prétraité pour la transcription
$testFile = $preprocessResult['output_file'];

// Générer un nom de fichier pour le résultat
$resultId = generateUniqueId();
$resultPath = RESULT_DIR . '/' . $resultId . '.json';

// Exécuter le script Python
$pythonPath = PYTHON_PATH;
$scriptPath = __DIR__ . '/transcribe.py';

$command = escapeshellcmd($pythonPath) . ' ' .
    escapeshellarg($scriptPath) . ' ' .
    '--file=' . escapeshellarg($testFile) . ' ' .
    '--output=' . escapeshellarg($resultPath);

echo "Exécution de la commande: $command\n";

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

    // Afficher les informations de débogage
    echo "Sortie standard:\n$output\n\n";
    echo "Sortie d'erreur:\n$error_output\n\n";
    echo "Code de retour: $return_value\n\n";

    // Décoder la sortie JSON
    $result = json_decode($output, true);

    // Vérifier si la transcription a réussi
    if ($result && isset($result['success']) && $result['success']) {
        echo "Transcription réussie!\n";
        echo "Texte: " . $result['text'] . "\n";
        echo "Langue: " . $result['language'] . "\n";
        echo "Résultat enregistré dans: $resultPath\n";
    } else {
        $error = $result['error'] ?? 'Erreur inconnue';
        echo "Erreur lors de la transcription: $error\n";
    }
} else {
    echo "Impossible de démarrer le processus: $command\n";
}
