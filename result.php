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
</head>

<body>
    <h1>Résultat de la <?= $isParaphrased ? 'paraphrase' : 'transcription' ?></h1>

    <div class="result-info">
        <p><strong>Langue:</strong> <?= htmlspecialchars($language) ?></p>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message"
            style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #c62828;">
            <strong>Erreur:</strong> <?= htmlspecialchars(getErrorMessage($_GET['error'], $_GET['message'] ?? null)) ?>
        </div>
    <?php endif; ?>

    <div class="result-text">
        <h2><?= $isParaphrased ? 'Texte paraphrasé' : 'Texte transcrit' ?></h2>
        <div class="text-box">
            <?= nl2br(htmlspecialchars($text)) ?>
        </div>
        <button id="copy-button" class="btn-secondary">Copier le texte</button>
    </div>

    <?php if ($isParaphrased && $originalText): ?>
        <div class="result-text">
            <h2>Texte original</h2>
            <div class="text-box">
                <?= nl2br(htmlspecialchars($originalText)) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="actions">
        <a href="index.php" class="btn-primary">Nouvelle transcription</a>
        <a href="download.php?id=<?= urlencode($resultId) ?>" class="btn-secondary">Télécharger en TXT</a>
        <?php if (!$isParaphrased): ?>
            <a href="paraphrase.php?id=<?= urlencode($resultId) ?>" class="btn-secondary">Paraphraser le texte</a>
        <?php endif; ?>
        <a href="chat.php" class="btn-secondary">Discuter avec l'assistant</a>
    </div>

    <script>
        document.getElementById('copy-button').addEventListener('click', function() {
            const text = <?= json_encode($text) ?>;
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