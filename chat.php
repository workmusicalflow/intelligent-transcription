<?php

/**
 * Interface de chat pour interagir avec le contenu transcrit
 */

require_once 'config.php';
require_once 'utils.php';
require_once 'context_manager.php';
require_once 'chat_api.php';

// Initialiser le gestionnaire de contexte
$contextManager = ContextManager::getInstance();
$context = $contextManager->getContext();

// Vérifier si nous avons une transcription
$hasTranscription = !empty($context['transcription']);

// Initialiser l'historique du chat depuis la session
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Traiter le message si soumis
$response = '';
if (isset($_POST['message']) && !empty($_POST['message'])) {
    $message = $_POST['message'];

    // Créer l'API de chat
    $chatAPI = new ChatAPI();

    // Envoyer le message et obtenir la réponse
    $response = $chatAPI->sendMessage($message, $_SESSION['chat_history']);

    // Ajouter à l'historique
    $_SESSION['chat_history'][] = [$message, $response];
}

// Fonction pour effacer l'historique
if (isset($_POST['clear_history'])) {
    $_SESSION['chat_history'] = [];
}

// Fonction pour exporter l'historique
$exportMessage = '';
if (isset($_POST['export_history']) && !empty($_SESSION['chat_history'])) {
    $exportFile = 'chat_export_' . date('Ymd_His') . '.txt';
    $exportPath = __DIR__ . '/exports/' . $exportFile;

    // Créer le répertoire d'exports s'il n'existe pas
    if (!is_dir(__DIR__ . '/exports')) {
        mkdir(__DIR__ . '/exports', 0777, true);
    }

    // Formater l'historique
    $exportContent = "=== Historique de la conversation ===\n\n";
    foreach ($_SESSION['chat_history'] as $exchange) {
        $exportContent .= "Utilisateur: " . $exchange[0] . "\n";
        $exportContent .= "Assistant: " . $exchange[1] . "\n\n";
    }

    // Écrire dans le fichier
    if (file_put_contents($exportPath, $exportContent)) {
        $exportMessage = "Historique exporté avec succès : <a href='exports/{$exportFile}' download>Télécharger</a>";
    } else {
        $exportMessage = "Erreur lors de l'exportation de l'historique";
    }
}

// Afficher la page
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Transcription</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chat-history {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .message {
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 18px;
        }

        .user-message {
            background-color: #e3f2fd;
            margin-left: 20%;
            border-bottom-right-radius: 4px;
        }

        .assistant-message {
            background-color: #f1f1f1;
            margin-right: 20%;
            border-bottom-left-radius: 4px;
        }

        .message-form {
            display: flex;
            margin-bottom: 10px;
        }

        .message-input {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
        }

        .context-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }

        .button-row {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .export-message {
            margin-top: 10px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Chat sur la transcription</h1>

        <?php if (!$hasTranscription): ?>
            <div class="alert alert-warning">
                <p>Aucune transcription disponible. Veuillez d'abord <a href="index.php">transcrire un fichier</a>.</p>
            </div>
        <?php else: ?>
            <div class="chat-container">
                <div class="context-info">
                    <h3>Contexte actuel</h3>
                    <p><strong>Transcription disponible :</strong>
                        <?php echo substr($context['transcription'], 0, 100); ?>...</p>
                    <?php if (!empty($context['translation'])): ?>
                        <p><strong>Traduction disponible :</strong> <?php echo substr($context['translation'], 0, 100); ?>...
                        </p>
                    <?php endif; ?>
                </div>

                <div class="chat-history">
                    <?php if (empty($_SESSION['chat_history'])): ?>
                        <p class="text-muted">Aucun message. Commencez la conversation!</p>
                    <?php else: ?>
                        <?php foreach ($_SESSION['chat_history'] as $exchange): ?>
                            <div class="message user-message">
                                <strong>Vous:</strong> <?php echo htmlspecialchars($exchange[0]); ?>
                            </div>
                            <div class="message assistant-message">
                                <strong>Assistant:</strong> <?php echo nl2br(htmlspecialchars($exchange[1])); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="post" class="message-form">
                    <input type="text" name="message" class="message-input"
                        placeholder="Posez une question sur la transcription..." required>
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </form>

                <div class="button-row">
                    <form method="post">
                        <button type="submit" name="clear_history" class="btn btn-secondary">Effacer l'historique</button>
                    </form>

                    <form method="post">
                        <button type="submit" name="export_history" class="btn btn-secondary">Exporter l'historique</button>
                    </form>

                    <a href="result.php" class="btn btn-link">Retour aux résultats</a>
                </div>

                <?php if (!empty($exportMessage)): ?>
                    <div class="export-message">
                        <?php echo $exportMessage; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Faire défiler automatiquement vers le bas de l'historique du chat
        document.addEventListener('DOMContentLoaded', function() {
            var chatHistory = document.querySelector('.chat-history');
            chatHistory.scrollTop = chatHistory.scrollHeight;
        });
    </script>
</body>

</html>