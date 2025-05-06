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
        /* Styles mobile-first pour le chat */
        :root {
            --color-primary: #3182ce;
            --color-primary-dark: #2c5282;
            --color-secondary: #48bb78;
            --color-secondary-dark: #276749;
            --color-bg: #f9fafb;
            --color-text: #1a202c;
            --color-text-light: #4a5568;
            --color-user-message: #e3f2fd;
            --color-assistant-message: #f1f1f1;
            --color-border: #e2e8f0;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
        }
        
        body {
            padding: 0;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .app-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            height: 100vh;
        }
        
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid var(--color-border);
            background-color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .chat-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }
        
        .chat-subtitle {
            font-size: 0.875rem;
            color: var(--color-text-light);
            margin: 0;
        }
        
        .context-container {
            padding: 0.75rem 1rem;
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            margin: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .context-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 600;
        }
        
        .context-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .context-container.expanded .context-content {
            max-height: 200px;
            margin-top: 0.75rem;
        }
        
        .context-arrow {
            transition: transform 0.3s ease;
        }
        
        .context-container.expanded .context-arrow {
            transform: rotate(180deg);
        }
        
        .chat-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background-color: #f9fafb;
        }
        
        .message {
            max-width: 85%;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            animation: fadeIn 0.3s ease;
            word-wrap: break-word;
            position: relative;
            line-height: 1.5;
        }
        
        .user-message {
            background-color: var(--color-user-message);
            align-self: flex-end;
            border-bottom-right-radius: 0.25rem;
        }
        
        .assistant-message {
            background-color: var(--color-assistant-message);
            align-self: flex-start;
            border-bottom-left-radius: 0.25rem;
        }
        
        .message-sender {
            font-weight: 600;
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
            color: var(--color-text-light);
        }
        
        .message-time {
            font-size: 0.7rem;
            color: var(--color-text-light);
            margin-top: 0.5rem;
            text-align: right;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-input-container {
            padding: 0.75rem;
            background-color: white;
            border-top: 1px solid var(--color-border);
            position: sticky;
            bottom: 0;
        }
        
        .message-form {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }
        
        .message-input-wrapper {
            flex-grow: 1;
            position: relative;
        }
        
        .message-input {
            width: 100%;
            resize: none;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 0.75rem;
            font-family: inherit;
            font-size: 1rem;
            max-height: 6rem;
            min-height: 2.5rem;
            line-height: 1.5;
            transition: border-color 0.2s ease;
            outline: none;
        }
        
        .message-input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(49, 130, 206, 0.1);
        }
        
        .send-button {
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease;
            flex-shrink: 0;
        }
        
        .send-button:hover {
            background-color: var(--color-primary-dark);
        }
        
        .send-button:active {
            transform: scale(0.97);
        }
        
        .send-button svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .actions-bar {
            padding: 0.75rem;
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            border-top: 1px solid var(--color-border);
            background-color: white;
        }
        
        .action-button {
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            background-color: white;
            border: 1px solid var(--color-border);
            color: var(--color-text);
        }
        
        .action-button:hover {
            background-color: #f3f4f6;
        }
        
        .action-button.primary {
            background-color: var(--color-primary);
            color: white;
            border: 1px solid var(--color-primary);
        }
        
        .action-button.primary:hover {
            background-color: var(--color-primary-dark);
        }
        
        .export-message {
            margin: 0.75rem;
            padding: 0.75rem;
            background-color: #e8f5e9;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .export-message a {
            color: var(--color-primary);
            font-weight: 500;
            text-decoration: none;
        }
        
        .export-message a:hover {
            text-decoration: underline;
        }
        
        .empty-message {
            text-align: center;
            padding: 2rem;
            color: var(--color-text-light);
            font-style: italic;
        }
        
        .alert-warning {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-warning svg {
            color: #e65100;
            flex-shrink: 0;
        }
        
        .alert-warning a {
            color: var(--color-primary);
            font-weight: 500;
            text-decoration: none;
        }
        
        .alert-warning a:hover {
            text-decoration: underline;
        }
        
        /* Media queries pour les écrans plus grands */
        @media (min-width: 640px) {
            .app-container {
                max-width: 640px;
            }
            
            .message {
                max-width: 75%;
            }
        }
        
        @media (min-width: 768px) {
            .app-container {
                max-width: 768px;
            }
            
            .actions-bar {
                justify-content: flex-end;
            }
            
            .action-button {
                flex-grow: 0;
            }
        }
        
        @media (min-width: 1024px) {
            .app-container {
                max-width: 900px;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <div class="chat-header">
            <h1 class="chat-title">Chat avec l'assistant</h1>
            <p class="chat-subtitle">Discutez de votre transcription et obtenez des insights</p>
        </div>
        
        <?php if (!$hasTranscription): ?>
            <div class="alert-warning">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 9V11M12 15H12.01M5.07183 19H18.9282C20.4678 19 21.4301 17.3333 20.6603 16L13.7321 4C12.9623 2.66667 11.0378 2.66667 10.268 4L3.33978 16C2.56998 17.3333 3.53223 19 5.07183 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p>Aucune transcription disponible. Veuillez d'abord <a href="index.php">transcrire un fichier</a>.</p>
            </div>
        <?php else: ?>
            <div class="context-container" id="context-container">
                <div class="context-toggle" id="context-toggle">
                    <span>Contexte de transcription</span>
                    <svg class="context-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 9L12 16L5 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="context-content">
                    <p><strong>Transcription :</strong> <?php echo substr($context['transcription'], 0, 150); ?>...</p>
                    <?php if (!empty($context['translation'])): ?>
                        <p><strong>Traduction :</strong> <?php echo substr($context['translation'], 0, 150); ?>...</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chat-messages" id="chat-messages">
                <?php if (empty($_SESSION['chat_history'])): ?>
                    <div class="message assistant-message">
                        <div class="message-sender">Assistant</div>
                        <div>Bonjour ! Je peux vous aider à analyser votre transcription. Posez-moi des questions à ce sujet.</div>
                        <div class="message-time"><?php echo date('H:i'); ?></div>
                    </div>
                <?php else: ?>
                    <?php foreach ($_SESSION['chat_history'] as $exchange): ?>
                        <div class="message user-message">
                            <div class="message-sender">Vous</div>
                            <div><?php echo htmlspecialchars($exchange[0]); ?></div>
                            <div class="message-time"><?php echo date('H:i'); ?></div>
                        </div>
                        <div class="message assistant-message">
                            <div class="message-sender">Assistant</div>
                            <div><?php echo nl2br(htmlspecialchars($exchange[1])); ?></div>
                            <div class="message-time"><?php echo date('H:i'); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="message-input-container">
                <form method="post" class="message-form" id="message-form">
                    <div class="message-input-wrapper">
                        <textarea name="message" id="message-input" class="message-input" placeholder="Posez une question sur la transcription..." required></textarea>
                    </div>
                    <button type="submit" class="send-button">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22 2L11 13M22 2L15 22L11 13M11 13L2 9L22 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>

            <div class="actions-bar">
                <form method="post" style="flex-grow: 1; display: flex; gap: 0.5rem;">
                    <button type="submit" name="clear_history" class="action-button">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M3 7H21M16 7L15.2976 4.78311C15.1123 4.22746 14.5889 3.85714 14 3.85714H10C9.41107 3.85714 8.88766 4.22746 8.70237 4.78311L8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Effacer
                    </button>
                </form>
                
                <form method="post" style="flex-grow: 1; display: flex; gap: 0.5rem;">
                    <button type="submit" name="export_history" class="action-button">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 10V16M12 16L9 13M12 16L15 13M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H14L19 8V19C19 20.1046 18.1046 21 17 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Exporter
                    </button>
                </form>
                
                <a href="result.php" class="action-button primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 17L6 12M6 12L11 7M6 12L18 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Retour aux résultats
                </a>
            </div>

            <?php if (!empty($exportMessage)): ?>
                <div class="export-message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $exportMessage; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gérer le textarea auto-expandable
            const messageInput = document.getElementById('message-input');
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
                
                // Permettre d'envoyer avec Entrée (mais Shift+Entrée pour un saut de ligne)
                messageInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        document.getElementById('message-form').submit();
                    }
                });
            }
            
            // Faire défiler automatiquement vers le bas de l'historique du chat
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Gérer le toggle du contexte
            const contextToggle = document.getElementById('context-toggle');
            const contextContainer = document.getElementById('context-container');
            if (contextToggle && contextContainer) {
                contextToggle.addEventListener('click', function() {
                    contextContainer.classList.toggle('expanded');
                });
            }
        });
    </script>
</body>

</html>