<?php

/**
 * Script de test pour la transcription audio
 * Ce script est un exemple d'implémentation de la solution simplifiée
 */

// Configuration
$pythonPath = 'python3'; // Chemin vers l'interpréteur Python
$scriptPath = __DIR__ . '/transcribe.py'; // Chemin vers le script Python
$uploadDir = __DIR__ . '/uploads'; // Répertoire pour les fichiers téléchargés
$resultDir = __DIR__ . '/results'; // Répertoire pour les résultats

// Créer les répertoires s'ils n'existent pas
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!is_dir($resultDir)) {
    mkdir($resultDir, 0777, true);
}

// Fonction pour transcrire un fichier audio
function transcribeAudio($filePath, $language = null)
{
    global $pythonPath, $scriptPath, $resultDir;

    // Générer un nom de fichier pour le résultat
    $resultId = uniqid();
    $resultPath = $resultDir . '/' . $resultId . '.json';

    // Construire la commande
    $command = escapeshellcmd($pythonPath) . ' ' .
        escapeshellarg($scriptPath) . ' ' .
        '--file=' . escapeshellarg($filePath) . ' ' .
        '--output=' . escapeshellarg($resultPath);

    if ($language) {
        $command .= ' --language=' . escapeshellarg($language);
    }

    // Exécuter la commande
    $output = shell_exec($command);

    // Décoder la sortie JSON
    $result = json_decode($output, true);

    // Ajouter l'ID du résultat
    if ($result && isset($result['success']) && $result['success']) {
        $result['result_id'] = $resultId;
    }

    return $result;
}

// Fonction pour afficher un message d'erreur
function displayError($message)
{
    echo '<div style="color: red; padding: 10px; border: 1px solid red; margin: 10px 0;">';
    echo '<strong>Erreur:</strong> ' . htmlspecialchars($message);
    echo '</div>';
}

// Fonction pour afficher un message de succès
function displaySuccess($message)
{
    echo '<div style="color: green; padding: 10px; border: 1px solid green; margin: 10px 0;">';
    echo '<strong>Succès:</strong> ' . htmlspecialchars($message);
    echo '</div>';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si un fichier a été téléchargé
    if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
        displayError('Aucun fichier audio valide n\'a été téléchargé');
    } else {
        $file = $_FILES['audio_file'];
        $language = $_POST['language'] ?? null;

        // Générer un nom de fichier unique
        $filename = uniqid('audio_') . '_' . basename($file['name']);
        $filePath = $uploadDir . '/' . $filename;

        // Déplacer le fichier téléchargé
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Transcrire le fichier audio
            $result = transcribeAudio($filePath, $language !== 'auto' ? $language : null);

            // Vérifier si la transcription a réussi
            if ($result && isset($result['success']) && $result['success']) {
                displaySuccess('Transcription réussie!');

                // Afficher le résultat
                echo '<div style="padding: 10px; border: 1px solid #ccc; margin: 10px 0;">';
                echo '<h3>Texte transcrit:</h3>';
                echo '<div style="background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd;">';
                echo nl2br(htmlspecialchars($result['text']));
                echo '</div>';
                echo '<p><strong>Langue:</strong> ' . htmlspecialchars($result['language']) . '</p>';
                echo '</div>';
            } else {
                $error = $result['error'] ?? 'Erreur inconnue';
                displayError('Erreur lors de la transcription: ' . $error);
            }
        } else {
            displayError('Erreur lors du déplacement du fichier téléchargé');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de transcription audio</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #333;
        }

        form {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select {
            margin-bottom: 15px;
            padding: 8px;
            width: 100%;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <h1>Test de transcription audio</h1>

    <form method="post" enctype="multipart/form-data">
        <div>
            <label for="audio_file">Fichier audio:</label>
            <input type="file" name="audio_file" id="audio_file" accept="audio/*,video/*" required>
        </div>

        <div>
            <label for="language">Langue:</label>
            <select name="language" id="language">
                <option value="auto">Détection automatique</option>
                <option value="fr">Français</option>
                <option value="en">Anglais</option>
                <option value="es">Espagnol</option>
                <option value="de">Allemand</option>
                <option value="it">Italien</option>
            </select>
        </div>

        <button type="submit">Transcrire</button>
    </form>

    <p>Ce script utilise le script Python <code>transcribe.py</code> pour transcrire des fichiers audio avec l'API
        OpenAI Whisper.</p>
</body>

</html>