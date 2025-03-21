<?php

/**
 * Script de traitement de la paraphrase de texte
 */

// Inclure les fichiers nécessaires
require_once 'config.php';
require_once 'utils.php';

// Vérifier si un ID de résultat est fourni
if (!isset($_GET['id'])) {
    redirect('index.php?error=missing_id');
}

$resultId = $_GET['id'];
$resultPath = RESULT_DIR . '/' . $resultId . '.json';

// Vérifier si le fichier de résultat existe
if (!file_exists($resultPath)) {
    redirect('index.php?error=result_not_found');
}

// Lire le résultat
$result = json_decode(file_get_contents($resultPath), true);

// Vérifier si le résultat est valide
if (!$result || !isset($result['text'])) {
    redirect('index.php?error=invalid_result');
}

// Récupérer le texte et la langue
$text = $result['text'];
$language = $result['language'] ?? 'fr';

// Créer un fichier temporaire pour le texte
$tempTextFile = tempnam(sys_get_temp_dir(), 'paraphrase_');
file_put_contents($tempTextFile, $text);

// Exécuter le script Python
$pythonPath = PYTHON_PATH;
$scriptPath = __DIR__ . '/paraphrase.py';

// Générer un nom de fichier pour le résultat
$paraphraseId = generateUniqueId();
$paraphrasePath = RESULT_DIR . '/' . $paraphraseId . '.json';

$command = escapeshellcmd($pythonPath) . ' ' .
    escapeshellarg($scriptPath) . ' ' .
    '--file=' . escapeshellarg($tempTextFile) . ' ' .
    '--output=' . escapeshellarg($paraphrasePath) . ' ' .
    '--language=' . escapeshellarg($language);

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
    file_put_contents('debug_paraphrase.log', print_r($debug_info, true));

    // Décoder la sortie JSON
    // Extraire le JSON de la sortie (au cas où il y aurait d'autres messages avant ou après)
    if (preg_match('/{.*}/s', $output, $matches)) {
        $jsonOutput = $matches[0];
        $paraphraseResult = json_decode($jsonOutput, true);

        // Enregistrer le JSON extrait pour le débogage
        file_put_contents('debug_paraphrase_json.log', $jsonOutput);
    } else {
        $paraphraseResult = json_decode($output, true);
    }
} else {
    // Impossible de démarrer le processus
    file_put_contents('debug_paraphrase.log', "Impossible de démarrer le processus: $command");
    redirect('result.php?id=' . $resultId . '&error=paraphrase&message=' . urlencode("Impossible de démarrer le processus de paraphrase"));
}

// Supprimer le fichier temporaire
if (file_exists($tempTextFile)) {
    unlink($tempTextFile);
}

// Enregistrer le résultat de la paraphrase pour le débogage
file_put_contents('debug_paraphrase_result.log', print_r($paraphraseResult, true));

// Vérifier si la paraphrase a réussi
if (!$paraphraseResult || !isset($paraphraseResult['success']) || !$paraphraseResult['success']) {
    $error = $paraphraseResult['error'] ?? 'Erreur inconnue';
    redirect('result.php?id=' . $resultId . '&error=paraphrase&message=' . urlencode($error));
}

// Vérifier si le fichier de résultat a été créé
if (!file_exists($paraphrasePath)) {
    redirect('result.php?id=' . $resultId . '&error=paraphrase&message=' . urlencode('Le fichier de résultat n\'a pas été créé'));
}

// Vérifier si le fichier de résultat contient les données attendues
$resultContent = json_decode(file_get_contents($paraphrasePath), true);
if (!$resultContent || !isset($resultContent['paraphrased_text'])) {
    // Enregistrer le contenu du fichier pour le débogage
    file_put_contents('debug_paraphrase_content.log', file_get_contents($paraphrasePath));

    // Enregistrer le résultat de json_decode pour le débogage
    file_put_contents('debug_paraphrase_decode.log', print_r($resultContent, true));

    redirect('result.php?id=' . $resultId . '&error=paraphrase&message=' . urlencode('Le fichier de résultat est invalide'));
}

// Rediriger vers la page de résultat avec le texte paraphrasé
redirect('result.php?id=' . $paraphraseId . '&paraphrased=1');
