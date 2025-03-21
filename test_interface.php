<?php

/**
 * Script de test pour vérifier le fonctionnement de l'interface
 * Permet de tester l'interface sans avoir à utiliser le serveur de production
 */

// Simuler l'environnement Twig pour le test
$app_name = "Transcription Audio - Test";
$app_version = "1.0.0";
$active_page = "home";
$max_upload_size_mb = 500;

// Fonction helper pour simuler la fonction Twig url()
function url($path, $params = [])
{
    $url = $path;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Interface - Transcription Audio</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .file-upload-area {
            border: 2px dashed #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        .file-upload-area.active {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
            margin-top: 3rem;
            margin-bottom: 3rem;
        }

        @media (min-width: 768px) {
            .features-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .feature-card {
            padding: 1.5rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
            transition: all 0.2s;
        }

        .feature-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: #e5e7eb;
        }

        .feature-card-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 9999px;
            background-color: #dbeafe;
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        /* Classes pour gérer les onglets */
        .tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .tab-button {
            padding: 0.5rem 1rem;
            font-weight: 500;
            color: #4b5563;
            border-bottom: 2px solid transparent;
        }

        .tab-button:hover {
            color: #1f2937;
        }

        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }

        .tab-content {
            display: none;
            padding: 1rem 0;
        }

        .tab-content.active {
            display: block;
        }

        /* Loading overlay */
        .spinner {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            border: 0.25rem solid #e2e8f0;
            border-top-color: #3b82f6;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Classes utilitaires */
        .container {
            width: 100%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }

        .btn-primary {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #3b82f6;
            color: white;
            font-weight: 500;
            border-radius: 0.375rem;
            text-align: center;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        /* Debug box */
        #debug-log {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>

<body class="bg-gray-50">
    <nav class="bg-gray-800 text-white shadow-md">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="font-semibold text-xl"><?php echo $app_name; ?></div>
                <ul class="flex space-x-4">
                    <li><a href="<?php echo url('index.php'); ?>"
                            class="hover:text-blue-300 transition-colors text-blue-300">Accueil</a></li>
                    <li><a href="<?php echo url('index.php', ['action' => 'chat']); ?>"
                            class="hover:text-blue-300 transition-colors">Chat</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-2 text-gray-800">Transcription Audio</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Convertissez vos fichiers audio et vidéos en texte avec une précision exceptionnelle
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 mb-12">
            <div class="tabs">
                <button class="tab-button active" data-tab="file-tab">Fichier</button>
                <button class="tab-button" data-tab="youtube-tab">YouTube</button>
            </div>

            <div id="file-tab" class="tab-content active">
                <form action="transcribe.php" method="post" enctype="multipart/form-data" class="space-y-6">
                    <div class="form-group">
                        <label for="audio_file" class="block text-sm font-medium text-gray-700 mb-1">Fichier audio ou
                            vidéo</label>
                        <div id="file-upload-area" class="file-upload-area">
                            <input type="file" name="audio_file" id="audio_file" accept="audio/*,video/*" required
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div class="flex flex-col items-center justify-center py-6">
                                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                    </path>
                                </svg>
                                <p class="text-gray-700 font-medium">Glissez et déposez votre fichier ici</p>
                                <p class="text-gray-500 text-sm mt-1">ou cliquez pour sélectionner un fichier</p>
                                <p id="selected-file-name" class="mt-2 text-blue-500 font-medium hidden"></p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Formats acceptés: MP3, WAV, MP4, etc. (max
                            <?php echo $max_upload_size_mb; ?>MB)</p>
                    </div>

                    <div class="form-group">
                        <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Langue</label>
                        <select name="language" id="language" class="form-control">
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
                        <div class="mt-3">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="force_language" id="force_language_file"
                                    class="h-5 w-5 text-blue-500" checked>
                                <span class="ml-2 text-gray-700">Forcer la traduction dans la langue sélectionnée</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Si l'audio est dans une autre langue, le texte sera
                                traduit</p>
                        </div>
                    </div>

                    <button type="submit" onclick="showLoadingOverlay()" class="btn-primary">Transcrire</button>
                </form>
            </div>

            <div id="youtube-tab" class="tab-content">
                <form action="youtube_download.php" method="post" class="space-y-6" onsubmit="showLoadingOverlay()">
                    <div class="form-group">
                        <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-1">URL
                            YouTube</label>
                        <input type="url" name="youtube_url" id="youtube_url"
                            placeholder="https://www.youtube.com/watch?v=..." class="form-control" required>
                        <p class="text-xs text-gray-500 mt-2">Entrez l'URL d'une vidéo YouTube à transcrire</p>
                    </div>

                    <div class="form-group">
                        <label for="language_youtube"
                            class="block text-sm font-medium text-gray-700 mb-1">Langue</label>
                        <select name="language" id="language_youtube" class="form-control">
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
                        <div class="mt-3">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="force_language" id="force_language_youtube"
                                    class="h-5 w-5 text-blue-500" checked>
                                <span class="ml-2 text-gray-700">Forcer la traduction dans la langue sélectionnée</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Si l'audio est dans une autre langue, le texte sera
                                traduit</p>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Transcrire</button>
                </form>
            </div>
        </div>

        <!-- Debug log area -->
        <div class="mt-8 p-4 bg-white rounded-xl shadow-md">
            <h2 class="text-xl font-bold mb-2">Console de débogage</h2>
            <div id="debug-log"></div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center">
                <div>
                    <p>&copy; <?php echo date('Y'); ?> <?php echo $app_name; ?></p>
                </div>
                <div>
                    <p>Version <?php echo $app_version; ?></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Overlay de chargement -->
    <div id="loading-overlay"
        class="fixed inset-0 bg-black bg-opacity-70 z-50 hidden flex justify-center items-center flex-col">
        <div class="spinner mb-4"></div>
        <div class="text-white text-xl font-medium">Traitement en cours...</div>
    </div>

    <script>
        // Initialisation pour journaliser les messages
        function log(message) {
            const logElement = document.getElementById('debug-log');
            const entry = document.createElement('div');
            entry.className = 'text-gray-700 border-b border-gray-200 pb-1 mb-1';
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logElement.appendChild(entry);
            console.log(message);
        }

        document.addEventListener("DOMContentLoaded", function() {
            log("Document chargé, initialisation des onglets...");

            // Récupérer tous les boutons d'onglets
            const tabButtons = document.querySelectorAll(".tab-button");
            log(`Trouvé ${tabButtons.length} boutons d'onglets`);

            tabButtons.forEach(button => {
                button.addEventListener("click", function(e) {
                    e.preventDefault();
                    log(`Clic sur l'onglet: "${this.textContent.trim()}"`);

                    // Récupérer l'ID de l'onglet
                    const tabId = this.getAttribute("data-tab");
                    log(`ID de l'onglet: "${tabId}"`);

                    if (!tabId) {
                        log("ERREUR: Attribut data-tab manquant sur le bouton");
                        return;
                    }

                    // Désactiver tous les boutons
                    tabButtons.forEach(btn => {
                        btn.classList.remove("active");
                        log(`Désactivation du bouton: "${btn.textContent.trim()}"`);
                    });

                    // Activer ce bouton
                    this.classList.add("active");
                    log(`Activation du bouton: "${this.textContent.trim()}"`);

                    // Masquer tous les contenus d'onglets
                    const tabContents = document.querySelectorAll(".tab-content");
                    tabContents.forEach(content => {
                        content.classList.remove("active");
                        log(`Désactivation du contenu: "${content.id}"`);
                    });

                    // Afficher le contenu de l'onglet
                    const tabContent = document.getElementById(tabId);
                    if (tabContent) {
                        tabContent.classList.add("active");
                        log(`Activation du contenu: "${tabId}"`);
                    } else {
                        log(`ERREUR: Contenu d'onglet non trouvé pour l'ID: "${tabId}"`);
                    }
                });

                log(
                    `Écouteur d'événement ajouté pour l'onglet "${button.textContent.trim()}" avec data-tab="${button.getAttribute("data-tab")}"`);
            });

            // Fonction pour afficher l'overlay de chargement
            window.showLoadingOverlay = function() {
                log("Affichage de l'overlay de chargement");
                const overlay = document.getElementById("loading-overlay");
                if (overlay) {
                    overlay.classList.remove("hidden");
                    overlay.classList.add("flex");
                }
                return true;
            };

            // Initialiser la gestion du drag & drop pour le sélecteur de fichier
            const fileInput = document.getElementById("audio_file");
            const uploadArea = document.getElementById("file-upload-area");
            const fileNameDisplay = document.getElementById("selected-file-name");

            if (fileInput && uploadArea) {
                log("Initialisation du système de téléchargement de fichiers");

                // Mise à jour du nom du fichier
                fileInput.addEventListener("change", function() {
                    if (this.files.length > 0) {
                        fileNameDisplay.textContent = this.files[0].name;
                        fileNameDisplay.classList.remove("hidden");
                        log(`Fichier sélectionné: "${this.files[0].name}"`);
                    } else {
                        fileNameDisplay.classList.add("hidden");
                        log("Aucun fichier sélectionné");
                    }
                });

                // Effets visuels pour le drag & drop
                ["dragenter", "dragover"].forEach(eventName => {
                    uploadArea.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        uploadArea.classList.add("active");
                        log(`Événement "${eventName}" sur la zone de téléchargement`);
                    }, false);
                });

                ["dragleave", "drop"].forEach(eventName => {
                    uploadArea.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        uploadArea.classList.remove("active");
                        log(`Événement "${eventName}" sur la zone de téléchargement`);

                        if (eventName === "drop") {
                            fileInput.files = e.dataTransfer.files;
                            if (fileInput.files.length > 0) {
                                fileNameDisplay.textContent = fileInput.files[0].name;
                                fileNameDisplay.classList.remove("hidden");
                                log(`Fichier déposé: "${fileInput.files[0].name}"`);
                            } else {
                                log("Aucun fichier déposé");
                            }
                        }
                    }, false);
                });

                log("Configuration du système de téléchargement terminée");
            } else {
                log("AVERTISSEMENT: Éléments de téléchargement de fichiers non trouvés");
            }

            log("Initialisation terminée");
        });
    </script>
</body>

</html>