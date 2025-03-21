# Solution simplifiée pour la transcription audio

Face aux difficultés rencontrées avec l'architecture actuelle, voici une proposition de solution simplifiée pour créer un MVP fonctionnel de transcription audio.

## Architecture proposée

### 1. Application PHP simple (sans framework)

```
transcription-app/
├── index.php           # Page d'accueil avec formulaire de téléchargement
├── transcribe.php      # Script de traitement de la transcription
├── result.php          # Page d'affichage des résultats
├── config.php          # Configuration (clés API, etc.)
├── utils.php           # Fonctions utilitaires
├── uploads/            # Dossier pour les fichiers téléchargés
├── results/            # Dossier pour les résultats de transcription
└── assets/             # CSS, JS, etc.
```

### 2. Script Python autonome

```python
#!/usr/bin/env python3

import os
import sys
import json
import argparse
from dotenv import load_dotenv
import openai

# Charger les variables d'environnement
load_dotenv()

def transcribe_audio(file_path, language=None, output_path=None):
    """
    Transcrit un fichier audio avec OpenAI Whisper
    """
    try:
        # Vérifier si le fichier existe
        if not os.path.exists(file_path):
            return {"error": f"Le fichier {file_path} n'existe pas"}

        # Ouvrir le fichier audio
        with open(file_path, "rb") as audio_file:
            # Appeler l'API OpenAI Whisper
            client = openai.OpenAI(api_key=os.getenv("OPENAI_API_KEY"))
            response = client.audio.transcriptions.create(
                model="whisper-1",
                file=audio_file,
                language=language
            )

            # Préparer le résultat
            result = {
                "text": response.text,
                "language": language or "auto-détection"
            }

            # Enregistrer le résultat si demandé
            if output_path:
                with open(output_path, "w", encoding="utf-8") as f:
                    json.dump(result, f, ensure_ascii=False, indent=2)

            return result
    except Exception as e:
        return {"error": str(e)}

def main():
    parser = argparse.ArgumentParser(description="Transcription audio avec OpenAI Whisper")
    parser.add_argument("--file", required=True, help="Chemin vers le fichier audio")
    parser.add_argument("--language", help="Code de langue (fr, en, etc.)")
    parser.add_argument("--output", help="Chemin vers le fichier de sortie JSON")
    args = parser.parse_args()

    result = transcribe_audio(args.file, args.language, args.output)

    if "error" in result:
        print(json.dumps({"success": False, "error": result["error"]}))
        sys.exit(1)
    else:
        print(json.dumps({"success": True, "text": result["text"], "language": result["language"]}))

if __name__ == "__main__":
    main()
```

## Implémentation

### 1. index.php - Formulaire simple

```php
<?php
// Configuration
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transcription Audio</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Transcription Audio</h1>
        <p>Convertissez vos fichiers audio en texte</p>

        <form action="transcribe.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="audio_file">Fichier audio</label>
                <input type="file" name="audio_file" id="audio_file" accept="audio/*,video/*" required>
                <p class="help-text">Formats acceptés: MP3, WAV, MP4, etc. (max <?= MAX_UPLOAD_SIZE_MB ?>MB)</p>
            </div>

            <div class="form-group">
                <label for="language">Langue</label>
                <select name="language" id="language">
                    <option value="auto">Détection automatique</option>
                    <option value="fr">Français</option>
                    <option value="en">Anglais</option>
                    <option value="es">Espagnol</option>
                    <option value="de">Allemand</option>
                </select>
            </div>

            <button type="submit" class="btn-primary">Transcrire</button>
        </form>
    </div>
</body>
</html>
```

### 2. transcribe.php - Traitement

```php
<?php
// Configuration
require_once 'config.php';
require_once 'utils.php';

// Vérifier si un fichier a été téléchargé
if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
    redirect('index.php?error=upload');
}

$file = $_FILES['audio_file'];
$language = $_POST['language'] ?? 'auto';

// Vérifier la taille du fichier
if ($file['size'] > MAX_UPLOAD_SIZE_BYTES) {
    redirect('index.php?error=size');
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

// Créer le répertoire de résultats si nécessaire
if (!is_dir(RESULT_DIR)) {
    mkdir(RESULT_DIR, 0777, true);
}

// Générer un nom de fichier pour le résultat
$resultId = uniqid();
$resultPath = RESULT_DIR . '/' . $resultId . '.json';

// Exécuter le script Python
$pythonPath = PYTHON_PATH;
$scriptPath = __DIR__ . '/transcribe.py';

$command = escapeshellcmd($pythonPath) . ' ' .
           escapeshellarg($scriptPath) . ' ' .
           '--file=' . escapeshellarg($filePath) . ' ' .
           '--output=' . escapeshellarg($resultPath);

if ($language !== 'auto') {
    $command .= ' --language=' . escapeshellarg($language);
}

// Exécuter la commande
$output = shell_exec($command);
$result = json_decode($output, true);

// Vérifier si la transcription a réussi
if (!$result || !isset($result['success']) || !$result['success']) {
    $error = $result['error'] ?? 'Erreur inconnue';
    redirect('index.php?error=transcription&message=' . urlencode($error));
}

// Rediriger vers la page de résultat
redirect('result.php?id=' . $resultId);
```

### 3. result.php - Affichage du résultat

```php
<?php
// Configuration
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat de la transcription</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Résultat de la transcription</h1>

        <div class="result-info">
            <p>Langue: <?= htmlspecialchars($result['language']) ?></p>
        </div>

        <div class="result-text">
            <h2>Texte transcrit</h2>
            <div class="text-box">
                <?= nl2br(htmlspecialchars($result['text'])) ?>
            </div>
            <button id="copy-button" class="btn-secondary">Copier le texte</button>
        </div>

        <div class="actions">
            <a href="index.php" class="btn-primary">Nouvelle transcription</a>
        </div>
    </div>

    <script>
        document.getElementById('copy-button').addEventListener('click', function() {
            const text = <?= json_encode($result['text']) ?>;
            navigator.clipboard.writeText(text).then(function() {
                const button = document.getElementById('copy-button');
                button.textContent = 'Copié!';
                setTimeout(function() {
                    button.textContent = 'Copier le texte';
                }, 2000);
            });
        });
    </script>
</body>
</html>
```

### 4. config.php - Configuration

```php
<?php
// Chemins
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('RESULT_DIR', __DIR__ . '/results');
define('PYTHON_PATH', '/usr/bin/python3'); // Ajuster selon votre système

// Limites
define('MAX_UPLOAD_SIZE_MB', 100);
define('MAX_UPLOAD_SIZE_BYTES', MAX_UPLOAD_SIZE_MB * 1024 * 1024);

// Clé API OpenAI
// Vous pouvez aussi utiliser un fichier .env avec la bibliothèque vlucas/phpdotenv
define('OPENAI_API_KEY', 'votre_clé_api_openai');
```

### 5. utils.php - Fonctions utilitaires

```php
<?php
/**
 * Redirige vers une URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Affiche un message d'erreur
 */
function getErrorMessage($code, $message = null) {
    $errors = [
        'upload' => 'Erreur lors du téléchargement du fichier',
        'size' => 'Le fichier est trop volumineux',
        'move' => 'Erreur lors du déplacement du fichier',
        'transcription' => 'Erreur lors de la transcription',
        'missing_id' => 'ID de résultat manquant',
        'result_not_found' => 'Résultat non trouvé',
        'invalid_result' => 'Résultat invalide'
    ];

    return $errors[$code] ?? 'Erreur inconnue' . ($message ? ': ' . $message : '');
}
```

## Avantages de cette approche

1. **Simplicité** : Architecture minimaliste et facile à comprendre
2. **Maintenabilité** : Peu de fichiers, code clair et direct
3. **Fiabilité** : Moins de points de défaillance
4. **Facilité de déploiement** : Pas de dépendances complexes
5. **Base solide** : Peut être étendue progressivement

## Prochaines étapes

1. Ajouter un système de cache pour éviter de retranscrire les mêmes fichiers
2. Implémenter un traitement asynchrone pour les fichiers volumineux
3. Ajouter des fonctionnalités de traduction
4. Améliorer l'interface utilisateur avec des animations et des retours visuels
5. Mettre en place un système de gestion des utilisateurs

Cette approche simplifiée permettra de disposer rapidement d'un MVP fonctionnel, sur lequel vous pourrez ensuite itérer pour ajouter des fonctionnalités plus avancées.
