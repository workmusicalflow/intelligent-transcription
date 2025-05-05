<?php

namespace Services;

use Utils\FileUtils;
use Utils\PromptUtils;
use Database\DatabaseManager;

/**
 * Service pour le chat contextuel
 */
class ChatService
{
    /**
     * Chemin du fichier d'export
     * 
     * @var string
     */
    private $exportDir;
    
    /**
     * Indique si le service utilise la base de données
     * 
     * @var bool
     */
    private $useDatabase;
    
    /**
     * ID de la conversation active
     * 
     * @var string|null
     */
    private $currentConversationId;
    
    /**
     * Service de mise en cache
     * 
     * @var CacheService
     */
    private $cacheService;
    
    /**
     * Service de résumé des messages
     * 
     * @var SummarizerService
     */
    private $summarizerService;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->exportDir = EXPORT_DIR;
        $this->useDatabase = defined('USE_DATABASE') ? USE_DATABASE : false;
        $this->currentConversationId = null;
        $this->cacheService = new CacheService();
        $this->summarizerService = new SummarizerService();

        // Créer le répertoire d'export si nécessaire
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0777, true);
        }
    }
    
    /**
     * Crée ou récupère une conversation existante
     * 
     * @param string|null $transcriptionId ID de la transcription associée (optionnel)
     * @param string|null $title Titre de la conversation (optionnel)
     * @return string ID de la conversation
     */
    public function getOrCreateConversation($transcriptionId = null, $title = null)
    {
        // Si on n'utilise pas la base de données, générer un ID temporaire
        if (!$this->useDatabase) {
            if (!$this->currentConversationId) {
                $this->currentConversationId = FileUtils::generateUniqueId();
            }
            return $this->currentConversationId;
        }
        
        // Si on a déjà un ID de conversation, le retourner
        if ($this->currentConversationId) {
            return $this->currentConversationId;
        }
        
        try {
            // Vérifier si une conversation existe déjà pour cette transcription
            if ($transcriptionId) {
                $sql = "SELECT id FROM chat_conversations WHERE transcription_id = :transcription_id ORDER BY created_at DESC LIMIT 1";
                $stmt = DatabaseManager::query($sql, [':transcription_id' => $transcriptionId]);
                $conversation = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($conversation) {
                    $this->currentConversationId = $conversation['id'];
                    return $this->currentConversationId;
                }
            }
            
            // Créer une nouvelle conversation
            $conversationId = FileUtils::generateUniqueId();
            $title = $title ?? "Conversation " . date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO chat_conversations (id, transcription_id, title) VALUES (:id, :transcription_id, :title)";
            DatabaseManager::query($sql, [
                ':id' => $conversationId,
                ':transcription_id' => $transcriptionId,
                ':title' => $title
            ]);
            
            $this->currentConversationId = $conversationId;
            return $conversationId;
        } catch (\Exception $e) {
            error_log('Erreur lors de la création de la conversation: ' . $e->getMessage());
            
            // Fallback: générer un ID temporaire
            $this->currentConversationId = FileUtils::generateUniqueId();
            return $this->currentConversationId;
        }
    }

    /**
     * Envoie un message au modèle de langage et récupère la réponse
     * 
     * @param string $message Message à envoyer
     * @param array $context Contexte de la conversation
     * @param string $transcriptionId ID de la transcription associée (optionnel)
     * @return array Résultat de la conversation
     */
    public function sendMessage($message, $context = [], $transcriptionId = null)
    {
        // Valider le message
        $textValidation = \Utils\ValidationUtils::validateTextMessage($message, [
            'min_length' => 1,  // Au moins un caractère
            'max_length' => 4000,  // Maximum 4000 caractères pour ne pas abuser
            'strip_tags' => true
        ]);
        
        if (!$textValidation['valid']) {
            return [
                'success' => false,
                'error' => $textValidation['error'],
                'category' => 'validation',
                'advice' => 'Le message doit contenir entre 1 et 4000 caractères.'
            ];
        }
        
        // Récupérer le message nettoyé
        $message = $textValidation['sanitized'];
        
        // Valider l'ID de transcription si fourni
        if ($transcriptionId !== null) {
            // Assurez-vous que l'ID est non vide et contient uniquement des caractères alphanumériques
            if (empty($transcriptionId) || !preg_match('/^[a-zA-Z0-9_]+$/', $transcriptionId)) {
                return [
                    'success' => false,
                    'error' => 'ID de transcription invalide',
                    'category' => 'validation',
                    'advice' => 'L\'ID de transcription doit être un identifiant alphanumérique valide.'
                ];
            }
        }
        
        // Récupérer ou créer une conversation
        $conversationId = $this->getOrCreateConversation($transcriptionId);
        
        // Démarrer le chronomètre pour mesurer le temps de réponse
        $startTime = microtime(true);
        
        // Récupérer le contexte de transcription si un ID est fourni
        $transcriptionContext = '';
        if ($transcriptionId) {
            $transcriptionService = new TranscriptionService();
            $transcriptionResult = $transcriptionService->getTranscriptionResult($transcriptionId);

            if ($transcriptionResult['success']) {
                $transcriptionContext = $transcriptionResult['text'];
            }
        }
        
        // Ajouter le nouveau message de l'utilisateur au contexte
        $updatedContext = $context;
        $updatedContext[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        // Si on utilise la base de données, vérifier si on doit résumer la conversation
        if ($this->useDatabase) {
            // Récupérer tous les messages pour la conversation
            $dbMessages = $this->getConversationMessagesFormatted($conversationId);
            
            if (!empty($dbMessages)) {
                // Si on a des messages dans la base de données, fusionner avec le contexte
                $updatedContext = array_merge($dbMessages, [
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ]);
            }
            
            // Si le contexte est trop long, le résumer
            if (PromptUtils::estimateTokenCount($updatedContext) > 3000) {
                // Demander au service de résumé de condenser la conversation
                $summarizeResult = $this->summarizerService->summarizeConversation($conversationId);
                
                if ($summarizeResult['success'] && $summarizeResult['summarized']) {
                    // Utiliser les messages résumés
                    $updatedContext = array_merge($summarizeResult['messages'], [
                        [
                            'role' => 'user',
                            'content' => $message
                        ]
                    ]);
                }
            }
        }
        
        // Créer un prompt optimisé pour le cache
        $optimizedPrompt = PromptUtils::createOptimizedPrompt($updatedContext, $transcriptionContext);
        
        // Générer une clé de cache basée sur le contenu du prompt
        $cacheKey = PromptUtils::generateCacheKey($optimizedPrompt);
        
        // Vérifier s'il y a une réponse en cache
        $cachedResponse = $this->cacheService->getCachedConversation($cacheKey);
        
        if ($cachedResponse !== null) {
            // Calcul du temps de réponse pour la requête en cache
            $responseTime = (microtime(true) - $startTime) * 1000; // en ms
            
            // Mise à jour des statistiques de cache
            $tokensSaved = PromptUtils::calculateTokenSavings($optimizedPrompt);
            $this->cacheService->recordCacheHit($conversationId, $responseTime, $tokensSaved);
            
            // Formater les derniers messages pour l'assistant
            $cachedMessages = $cachedResponse['messages'];
            $lastAssistantMessage = null;
            
            for ($i = count($cachedMessages) - 1; $i >= 0; $i--) {
                if ($cachedMessages[$i]['role'] === 'assistant') {
                    $lastAssistantMessage = $cachedMessages[$i]['content'];
                    break;
                }
            }
            
            if ($lastAssistantMessage) {
                // Sauvegarder le message de l'utilisateur dans la BDD
                if ($this->useDatabase) {
                    $this->saveMessage($conversationId, 'user', $message);
                    // Sauvegarder la réponse de l'assistant depuis le cache
                    $this->saveMessage($conversationId, 'assistant', $lastAssistantMessage);
                }
                
                return [
                    'success' => true,
                    'response' => $lastAssistantMessage,
                    'from_cache' => true,
                    'response_time_ms' => $responseTime
                ];
            }
        }
        
        // Si pas de cache hit, procéder avec l'appel à l'API
        // Exécuter le script Python de chat
        $pythonPath = PYTHON_PATH;
        $scriptPath = BASE_DIR . '/chat_api.py';

        // Créer un fichier temporaire pour le message
        $messageFile = tempnam(sys_get_temp_dir(), 'chat_message_');
        file_put_contents($messageFile, $message);

        // Préparer le contexte optimisé pour le prompt OpenAI
        $contextFile = tempnam(sys_get_temp_dir(), 'chat_context_');
        file_put_contents($contextFile, json_encode([
            'messages' => $optimizedPrompt,
            'transcription' => '' // Le contexte de transcription est déjà inclus dans optimizedPrompt
        ]));

        // Créer un fichier temporaire pour le résultat
        $outputFile = tempnam(sys_get_temp_dir(), 'chat_output_');

        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($scriptPath) . ' ' .
            '--message=' . escapeshellarg($messageFile) . ' ' .
            '--context=' . escapeshellarg($contextFile) . ' ' .
            '--output=' . escapeshellarg($outputFile);

        // Utiliser notre utilitaire d'exécution Python
        $result = \Utils\PythonErrorUtils::executePythonProcess($command, 'chat', $outputFile);
        
        // Nettoyer les fichiers temporaires
        @unlink($messageFile);
        @unlink($contextFile);
        @unlink($outputFile);
        
        // Si l'opération a échoué, retourner l'erreur enrichie
        if (!isset($result['success']) || !$result['success']) {
            // Ajouter des conseils spécifiques au chat en cas d'erreur
            if (!isset($result['advice'])) {
                $result['advice'] = "Essayez de reformuler votre question ou de réessayer plus tard.";
            }
            return $result;
        }
        
        // Calcul du temps de réponse total
        $responseTime = (microtime(true) - $startTime) * 1000; // en ms
        
        // Enregistrer les messages dans la base de données
        if ($this->useDatabase) {
            try {
                // Sauvegarder le message de l'utilisateur
                $this->saveMessage($conversationId, 'user', $message);
                
                // Sauvegarder la réponse de l'assistant
                $this->saveMessage($conversationId, 'assistant', $result['response']);
                
                // Mettre à jour les statistiques de cache
                $this->cacheService->recordCacheMiss($conversationId, $responseTime, PromptUtils::estimateTokenCount($optimizedPrompt));
                
                // Mettre en cache la conversation pour les prochaines requêtes
                $optimizedPrompt[] = [
                    'role' => 'assistant',
                    'content' => $result['response']
                ];
                $this->cacheService->cacheConversation($conversationId, $optimizedPrompt);
                
                // Enregistrer les tokens pour analytics
                $this->cacheService->recordMessageTokens($conversationId, $optimizedPrompt);
            } catch (\Exception $e) {
                error_log('Erreur lors de la sauvegarde des messages: ' . $e->getMessage());
                // Continuer même en cas d'erreur pour ne pas bloquer l'utilisateur
            }
        }
        
        $result['from_cache'] = false;
        $result['response_time_ms'] = $responseTime;
        return $result;
    }
    
    /**
     * Sauvegarde un message dans la base de données
     * 
     * @param string $conversationId ID de la conversation
     * @param string $role Rôle du message (user/assistant)
     * @param string $content Contenu du message
     * @return int|false ID du message ou false en cas d'erreur
     */
    private function saveMessage($conversationId, $role, $content)
    {
        try {
            $sql = "INSERT INTO chat_messages (conversation_id, role, content, token_count) 
                    VALUES (:conversation_id, :role, :content, :token_count)";
            
            $tokenCount = PromptUtils::estimateTokenCount($content);
            
            DatabaseManager::query($sql, [
                ':conversation_id' => $conversationId,
                ':role' => $role,
                ':content' => $content,
                ':token_count' => $tokenCount
            ]);
            
            return DatabaseManager::lastInsertId();
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'enregistrement du message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Exporte une conversation
     * 
     * @param array $messages Messages de la conversation
     * @param string|null $conversationId ID de la conversation (optionnel)
     * @return array Résultat de l'export
     */
    public function exportConversation($messages = [], $conversationId = null)
    {
        // Si on a un ID de conversation et qu'on utilise la base de données, récupérer les messages depuis la base
        if ($this->useDatabase && $conversationId) {
            $dbMessages = $this->getConversationMessagesFormatted($conversationId);
            
            // Si on a des messages dans la base et qu'aucun message n'a été fourni, utiliser ceux de la base
            if (!empty($dbMessages) && empty($messages)) {
                $messages = $dbMessages;
            }
        }
        
        if (empty($messages)) {
            return [
                'success' => false,
                'error' => 'Aucun message à exporter'
            ];
        }

        // Générer un nom de fichier unique
        $filename = 'chat_export_' . date('Ymd_His') . '.txt';
        $filePath = $this->exportDir . '/' . $filename;

        // Formater la conversation
        $content = "# Conversation exportée le " . date('d/m/Y à H:i:s') . "\n\n";

        foreach ($messages as $message) {
            $role = $message['role'] === 'user' ? 'Vous' : 'Assistant';
            $content .= "## $role\n\n";
            $content .= $message['content'] . "\n\n";
        }

        // Enregistrer le fichier
        if (file_put_contents($filePath, $content) === false) {
            return [
                'success' => false,
                'error' => 'Impossible d\'enregistrer le fichier d\'export'
            ];
        }
        
        // Si on utilise la base de données, enregistrer l'export
        if ($this->useDatabase && $conversationId) {
            try {
                $exportId = FileUtils::generateUniqueId();
                $sql = "INSERT INTO chat_exports (id, conversation_id, file_path, file_name) VALUES (:id, :conversation_id, :file_path, :file_name)";
                DatabaseManager::query($sql, [
                    ':id' => $exportId,
                    ':conversation_id' => $conversationId,
                    ':file_path' => $filePath,
                    ':file_name' => $filename
                ]);
            } catch (\Exception $e) {
                error_log('Erreur lors de l\'enregistrement de l\'export: ' . $e->getMessage());
                // Continuer même en cas d'erreur
            }
        }

        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $filename
        ];
    }
    
    /**
     * Récupère les messages d'une conversation
     * 
     * @param string $conversationId ID de la conversation
     * @return array Messages de la conversation
     */
    public function getConversationMessages($conversationId)
    {
        if (!$this->useDatabase) {
            return [];
        }
        
        try {
            $sql = "SELECT id, role, content, is_summarized, token_count, created_at FROM chat_messages 
                    WHERE conversation_id = :conversation_id ORDER BY id ASC";
            $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération des messages: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les messages d'une conversation formatés pour l'API
     * 
     * @param string $conversationId ID de la conversation
     * @return array Messages de la conversation formatés
     */
    public function getConversationMessagesFormatted($conversationId)
    {
        $messages = $this->getConversationMessages($conversationId);
        
        if (empty($messages)) {
            return [];
        }
        
        $formatted = [];
        foreach ($messages as $message) {
            $formatted[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Liste les conversations de l'utilisateur
     * 
     * @param int $limit Nombre maximum de résultats
     * @param int $offset Décalage pour la pagination
     * @return array Liste des conversations
     */
    public function listConversations($limit = 10, $offset = 0)
    {
        if (!$this->useDatabase) {
            return [
                'success' => true,
                'conversations' => [],
                'count' => 0
            ];
        }
        
        try {
            $sql = "SELECT c.*, t.text as transcription_preview,
                        c.cache_hit_count, c.cache_miss_count, c.last_cache_hit
                    FROM chat_conversations c 
                    LEFT JOIN transcriptions t ON c.transcription_id = t.id 
                    ORDER BY c.updated_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = DatabaseManager::query($sql, [
                ':limit' => $limit,
                ':offset' => $offset
            ]);
            
            $conversations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Pour chaque conversation, récupérer le premier et le dernier message
            foreach ($conversations as &$conversation) {
                // Récupérer le premier message
                $sql = "SELECT role, content FROM chat_messages WHERE conversation_id = :conversation_id ORDER BY id ASC LIMIT 1";
                $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversation['id']]);
                $firstMessage = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                // Récupérer le dernier message
                $sql = "SELECT role, content FROM chat_messages WHERE conversation_id = :conversation_id ORDER BY id DESC LIMIT 1";
                $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversation['id']]);
                $lastMessage = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                // Ajouter les messages à la conversation
                $conversation['first_message'] = $firstMessage;
                $conversation['last_message'] = $lastMessage;
                
                // Compter le nombre de messages
                $sql = "SELECT COUNT(*) as count FROM chat_messages WHERE conversation_id = :conversation_id";
                $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversation['id']]);
                $countResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                $conversation['message_count'] = $countResult ? $countResult['count'] : 0;
                
                // Compter le nombre de messages résumés
                $sql = "SELECT COUNT(*) as count FROM chat_messages WHERE conversation_id = :conversation_id AND is_summarized = 1";
                $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversation['id']]);
                $countResult = $stmt->fetch(\PDO::FETCH_ASSOC);
                $conversation['summarized_count'] = $countResult ? $countResult['count'] : 0;
                
                // Si la transcription est trop longue, la tronquer
                if (isset($conversation['transcription_preview']) && $conversation['transcription_preview']) {
                    $conversation['transcription_preview'] = mb_substr($conversation['transcription_preview'], 0, 100) . '...';
                }
                
                // Ajouter les statistiques de cache
                if (isset($conversation['cache_hit_count']) && isset($conversation['cache_miss_count'])) {
                    $total = $conversation['cache_hit_count'] + $conversation['cache_miss_count'];
                    $conversation['cache_hit_rate'] = $total > 0 ? ($conversation['cache_hit_count'] / $total) : 0;
                    $conversation['cache_hit_rate_formatted'] = number_format($conversation['cache_hit_rate'] * 100, 1) . '%';
                }
            }
            
            return [
                'success' => true,
                'conversations' => $conversations,
                'count' => count($conversations)
            ];
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération des conversations: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération des conversations'
            ];
        }
    }
    
    /**
     * Supprime une conversation
     * 
     * @param string $conversationId ID de la conversation
     * @return array Résultat de la suppression
     */
    public function deleteConversation($conversationId)
    {
        if (!$this->useDatabase) {
            return [
                'success' => false,
                'error' => 'Suppression non prise en charge sans base de données'
            ];
        }
        
        try {
            // Suppression des exports liés
            $sql = "SELECT file_path FROM chat_exports WHERE conversation_id = :conversation_id";
            $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
            $exports = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($exports as $export) {
                if (file_exists($export['file_path'])) {
                    unlink($export['file_path']);
                }
            }
            
            // La suppression en cascade est gérée par les contraintes de clé étrangère
            $sql = "DELETE FROM chat_conversations WHERE id = :conversation_id";
            DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
            
            return [
                'success' => true,
                'message' => 'Conversation supprimée avec succès'
            ];
        } catch (\Exception $e) {
            error_log('Erreur lors de la suppression de la conversation: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur lors de la suppression de la conversation'
            ];
        }
    }
    
    /**
     * Récupère les détails d'une conversation
     * 
     * @param string $conversationId ID de la conversation
     * @return array Détails de la conversation
     */
    public function getConversation($conversationId)
    {
        if (!$this->useDatabase) {
            return [
                'success' => false,
                'error' => 'Récupération non prise en charge sans base de données'
            ];
        }
        
        try {
            $sql = "SELECT c.*, t.text as transcription_text, t.language, t.youtube_url,
                        c.cache_hit_count, c.cache_miss_count, c.last_cache_hit, c.prompt_cache_id
                    FROM chat_conversations c 
                    LEFT JOIN transcriptions t ON c.transcription_id = t.id 
                    WHERE c.id = :conversation_id LIMIT 1";
            
            $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
            $conversation = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$conversation) {
                return [
                    'success' => false,
                    'error' => 'Conversation non trouvée'
                ];
            }
            
            // Récupérer les messages de la conversation
            $conversation['messages'] = $this->getConversationMessages($conversationId);
            
            // Récupérer les exports de la conversation
            $sql = "SELECT id, file_name, file_path, created_at FROM chat_exports WHERE conversation_id = :conversation_id ORDER BY created_at DESC";
            $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
            $conversation['exports'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Ajouter les statistiques de cache
            if (isset($conversation['cache_hit_count']) && isset($conversation['cache_miss_count'])) {
                $total = $conversation['cache_hit_count'] + $conversation['cache_miss_count'];
                $conversation['cache_hit_rate'] = $total > 0 ? ($conversation['cache_hit_count'] / $total) : 0;
                $conversation['cache_hit_rate_formatted'] = number_format($conversation['cache_hit_rate'] * 100, 1) . '%';
            }
            
            // Récupérer les statistiques de cache supplémentaires
            $cacheAnalytics = $this->cacheService->getCacheAnalytics($conversationId);
            if ($cacheAnalytics['success']) {
                $conversation['cache_analytics'] = $cacheAnalytics['analytics'];
            }
            
            return [
                'success' => true,
                'conversation' => $conversation
            ];
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération de la conversation: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération de la conversation'
            ];
        }
    }
    
    /**
     * Obtient les statistiques de cache
     * 
     * @return array Statistiques de cache
     */
    public function getCacheStatistics()
    {
        return $this->cacheService->getCacheAnalytics();
    }
    
    /**
     * Développe un résumé pour voir les messages originaux
     * 
     * @param int $messageId ID du message résumé
     * @return array Messages originaux
     */
    public function expandSummary($messageId)
    {
        return $this->summarizerService->expandSummary($messageId);
    }
}