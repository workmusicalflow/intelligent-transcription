<?php

/**
 * Script de téléchargement du résultat de la transcription
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
if (!$result) {
    redirect('index.php?error=invalid_result');
}

// Déterminer si c'est un résultat de transcription ou de paraphrase
$isParaphrased = isset($result['paraphrased_text']) || (isset($_GET['paraphrased']) && $_GET['paraphrased'] == '1');

if ($isParaphrased) {
    // C'est un résultat de paraphrase
    if (!isset($result['paraphrased_text']) && !isset($result['text'])) {
        redirect('index.php?error=invalid_result');
    }

    // Compatibilité avec les deux formats de résultat
    if (isset($result['paraphrased_text'])) {
        $text = $result['paraphrased_text'];
    } else {
        $text = $result['text'];
    }

    $filePrefix = 'paraphrase';
} else {
    // C'est un résultat de transcription
    if (!isset($result['text'])) {
        redirect('index.php?error=invalid_result');
    }
    $text = $result['text'];
    $filePrefix = 'transcription';
}

// Générer un nom de fichier pour le téléchargement
$filename = $filePrefix . '_' . date('Y-m-d_H-i-s') . '.txt';

// Définir les en-têtes pour le téléchargement
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($text));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Envoyer le contenu
echo $text;
exit;
