<?php

/**
 * Page d'accueil de l'application de transcription audio
 */

// Inclure les fichiers nécessaires
require_once 'config.php';
require_once 'utils.php';

// Nettoyer les anciens fichiers
cleanupOldFiles(UPLOAD_DIR);
cleanupOldFiles(RESULT_DIR);
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
    <nav class="main-nav">
        <ul>
            <li><a href="index.php" class="active">Accueil</a></li>
            <li><a href="chat.php">Chat</a></li>
        </ul>
    </nav>

    <h1>Transcription Audio</h1>
    <p>Convertissez vos fichiers audio en texte avec une précision exceptionnelle</p>

    <?php
    // Afficher les messages d'erreur
    if (isset($_GET['error'])) {
        $errorCode = $_GET['error'];
        $errorMessage = getErrorMessage($errorCode, $_GET['message'] ?? null);
        echo '<div class="error-message" style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #c62828;">';
        echo '<strong>Erreur:</strong> ' . htmlspecialchars($errorMessage);
        echo '</div>';
    }
    ?>

    <div class="tabs">
        <button class="tab-button active" onclick="showTab('file-tab')">Fichier</button>
        <button class="tab-button" onclick="showTab('youtube-tab')">YouTube</button>
    </div>

    <div id="file-tab" class="tab-content active">
        <form action="transcribe.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="audio_file">Fichier audio ou vidéo</label>
                <input type="file" name="audio_file" id="audio_file" accept="audio/*,video/*" required>
                <p class="help-text">Formats acceptés: MP3, WAV, MP4, etc. (max <?= MAX_UPLOAD_SIZE_MB ?>MB)</p>
            </div>

            <div class="form-group">
                <label for="language">Langue</label>
                <select name="language" id="language">
                    <option value="auto">Détection automatique</option>
                    <option value="fr" selected>Français</option>
                    <option value="en">Anglais</option>
                    <option value="es">Espagnol</option>
                    <option value="de">Allemand</option>
                    <option value="it">Italien</option>
                    <option value="pt">Portugais</option>
                    <option value="ru">Russe</option>
                    <option value="zh">Chinois</option>
                    <option value="ja">Japonais</option>
                    <option value="ar">Arabe</option>
                </select>
                <div class="checkbox-group">
                    <input type="checkbox" name="force_language" id="force_language_file" checked>
                    <label for="force_language_file">Forcer la traduction dans la langue sélectionnée</label>
                    <p class="help-text">Si l'audio est dans une autre langue, le texte sera traduit</p>
                </div>
            </div>

            <button type="submit" class="btn-primary">Transcrire</button>
        </form>
    </div>

    <div id="youtube-tab" class="tab-content">
        <form action="youtube_download.php" method="post">
            <div class="form-group">
                <label for="youtube_url">URL YouTube</label>
                <input type="url" name="youtube_url" id="youtube_url" placeholder="https://www.youtube.com/watch?v=..."
                    required>
                <p class="help-text">Entrez l'URL d'une vidéo YouTube à transcrire</p>
            </div>

            <div class="form-group">
                <label for="language">Langue</label>
                <select name="language" id="language_youtube">
                    <option value="auto">Détection automatique</option>
                    <option value="fr" selected>Français</option>
                    <option value="en">Anglais</option>
                    <option value="es">Espagnol</option>
                    <option value="de">Allemand</option>
                    <option value="it">Italien</option>
                    <option value="pt">Portugais</option>
                    <option value="ru">Russe</option>
                    <option value="zh">Chinois</option>
                    <option value="ja">Japonais</option>
                    <option value="ar">Arabe</option>
                </select>
                <div class="checkbox-group">
                    <input type="checkbox" name="force_language" id="force_language_youtube" checked>
                    <label for="force_language_youtube">Forcer la traduction dans la langue sélectionnée</label>
                    <p class="help-text">Si l'audio est dans une autre langue, le texte sera traduit</p>
                </div>
            </div>

            <button type="submit" class="btn-primary">Transcrire</button>
        </form>
    </div>

    <script>
        function showTab(tabId) {
            // Masquer tous les contenus d'onglets
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });

            // Désactiver tous les boutons d'onglets
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });

            // Afficher le contenu de l'onglet sélectionné
            document.getElementById(tabId).classList.add('active');

            // Activer le bouton d'onglet sélectionné
            const activeButton = document.querySelector(`.tab-button[onclick="showTab('${tabId}')"]`);
            activeButton.classList.add('active');
        }
    </script>

    <div class="features">
        <h2>Fonctionnalités</h2>
        <ul>
            <li>Transcription précise grâce à l'API OpenAI Whisper</li>
            <li>Support pour de nombreux formats audio et vidéo</li>
            <li>Transcription directe de vidéos YouTube</li>
            <li>Détection automatique de la langue</li>
            <li>Interface simple et intuitive</li>
        </ul>
    </div>

    <div class="about">
        <h2>À propos</h2>
        <p>Cette application utilise l'API OpenAI Whisper pour transcrire des fichiers audio en texte. Elle est conçue
            pour être simple, rapide et précise.</p>
        <p>Pour plus d'informations, consultez la <a href="SOLUTION_TRANSCRIPTION_SIMPLIFIEE.md">documentation</a>.</p>
    </div>
</body>

</html>