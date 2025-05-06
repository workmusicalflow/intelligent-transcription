<?php

/**
 * Page d'affichage du résultat de la transcription
 */

// Inclure les fichiers nécessaires
require_once 'config.php';
require_once 'utils.php';
require_once 'context_manager.php';

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
$isParaphrased = isset($_GET['paraphrased']) && $_GET['paraphrased'] == '1';
$originalText = '';

// Initialiser le gestionnaire de contexte
$contextManager = ContextManager::getInstance();

if ($isParaphrased) {
    // C'est un résultat de paraphrase
    if (!isset($result['paraphrased_text']) && !isset($result['text'])) {
        redirect('index.php?error=invalid_result');
    }

    // Compatibilité avec les deux formats de résultat
    if (isset($result['paraphrased_text'])) {
        $text = $result['paraphrased_text'];
        $originalText = $result['original_text'] ?? '';
    } else {
        $text = $result['text'];
    }

    $language = $result['language'];
    $resultType = 'paraphrase';
} else {
    // C'est un résultat de transcription
    if (!isset($result['text'])) {
        redirect('index.php?error=invalid_result');
    }
    $text = $result['text'];
    $language = $result['language'];
    $resultType = 'transcription';
}

// Stocker la transcription dans le gestionnaire de contexte
if ($isParaphrased) {
    $contextManager->updateContext($originalText, $text, [
        'type' => 'paraphrase',
        'language' => $language,
        'result_id' => $resultId
    ]);
} else {
    $contextManager->updateContext($text, '', [
        'type' => 'transcription',
        'language' => $language,
        'result_id' => $resultId
    ]);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat de la transcription</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Styles pour la version mobile-first */
        body {
            padding: 1rem;
            max-width: 100%;
            overflow-x: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .result-header {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1rem;
        }
        
        .result-info {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .result-info-item {
            flex: 1 1 100%;
            padding: 0.5rem;
            background-color: #ffffff;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        @media (min-width: 640px) {
            .result-info-item {
                flex: 1 1 calc(50% - 0.5rem);
            }
        }
        
        .info-label {
            display: block;
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 500;
            color: #111827;
        }
        
        .text-box {
            background-color: #ffffff;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
            line-height: 1.6;
            overflow-wrap: break-word;
            max-height: 50vh;
            overflow-y: auto;
        }
        
        .buttons-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: #3182ce;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2c5282;
        }
        
        .btn-secondary {
            background-color: #48bb78;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #276749;
        }
        
        .btn-action {
            background-color: #edf2f7;
            color: #2d3748;
        }
        
        .btn-action:hover {
            background-color: #e2e8f0;
        }
        
        .btn-icon {
            margin-right: 0.375rem;
            height: 1rem;
            width: 1rem;
        }
        
        /* Animation pour le bouton copier */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .copy-feedback {
            animation: fadeIn 0.3s ease;
            display: inline-block;
            margin-left: 0.5rem;
            color: #2c5282;
            font-weight: 500;
        }
        
        /* Onglets */
        .tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 1rem;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }
        
        .tab-button {
            padding: 0.5rem 1rem;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            color: #6b7280;
        }
        
        .tab-button.active {
            border-color: #3182ce;
            color: #3182ce;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="result-header">
            <h1>Résultat de la <?= $isParaphrased ? 'paraphrase' : 'transcription' ?></h1>
        </div>
        
        <div class="result-info">
            <div class="result-info-item">
                <span class="info-label">Langue</span>
                <span class="info-value"><?= htmlspecialchars($language) ?></span>
            </div>
            
            <div class="result-info-item">
                <span class="info-label">Type</span>
                <span class="info-value"><?= $isParaphrased ? 'Paraphrase' : 'Transcription' ?></span>
            </div>
            
            <div class="result-info-item">
                <span class="info-label">ID</span>
                <span class="info-value" style="font-size: 0.75rem; font-family: monospace;"><?= htmlspecialchars($resultId) ?></span>
            </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"
                style="background-color: #ffebee; color: #c62828; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; border-left: 4px solid #c62828;">
                <strong>Erreur:</strong> <?= htmlspecialchars(getErrorMessage($_GET['error'], $_GET['message'] ?? null)) ?>
            </div>
        <?php endif; ?>

        <?php if ($isParaphrased && $originalText): ?>
            <div class="tabs">
                <div class="tab-button active" data-tab="paraphrased">Texte paraphrasé</div>
                <div class="tab-button" data-tab="original">Texte original</div>
            </div>
            
            <div id="paraphrased" class="tab-content active">
                <div class="text-box">
                    <?= nl2br(htmlspecialchars($text)) ?>
                </div>
            </div>
            
            <div id="original" class="tab-content">
                <div class="text-box">
                    <?= nl2br(htmlspecialchars($originalText)) ?>
                </div>
            </div>
        <?php else: ?>
            <h2><?= $isParaphrased ? 'Texte paraphrasé' : 'Texte transcrit' ?></h2>
            <div class="text-box">
                <?= nl2br(htmlspecialchars($text)) ?>
            </div>
        <?php endif; ?>
        
        <button id="copy-button" class="btn btn-secondary" style="margin-top: 1rem;">
            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
            </svg>
            Copier le texte
            <span id="copy-feedback" style="display: none;" class="copy-feedback">✓ Copié!</span>
        </button>

        <div class="buttons-group">
            <a href="index.php" class="btn btn-primary">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Nouvelle transcription
            </a>
            
            <a href="download.php?id=<?= urlencode($resultId) ?>" class="btn btn-secondary">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Télécharger TXT
            </a>
            
            <?php if (!$isParaphrased): ?>
                <a href="paraphrase.php?id=<?= urlencode($resultId) ?>" class="btn btn-action">
                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                    Paraphraser
                </a>
            <?php endif; ?>
            
            <a href="chat.php" class="btn btn-action">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                Discuter avec l'IA
            </a>
        </div>
    </div>

    <script>
        // Gestion des onglets
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Retirer la classe active de tous les boutons et contenus
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Ajouter la classe active au bouton cliqué et au contenu associé
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Gestion du bouton de copie
        document.getElementById('copy-button').addEventListener('click', function() {
            const text = <?= json_encode($text) ?>;
            navigator.clipboard.writeText(text).then(function() {
                const feedback = document.getElementById('copy-feedback');
                feedback.style.display = 'inline-block';
                
                setTimeout(function() {
                    feedback.style.display = 'none';
                }, 2000);
            });
        });
    </script>
</body>

</html>