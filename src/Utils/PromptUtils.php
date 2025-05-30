<?php

namespace Utils;

use Database\DatabaseManager;
use App\Services\PromptCacheManager;

/**
 * Utility class for managing and optimizing prompts
 */
class PromptUtils
{
    /**
     * Default system prompts
     * 
     * @var array
     */
    private static $defaultPrompts = [
        'chat' => "Tu es un assistant IA serviable spécialisé dans la discussion de contenu transcrit. Tu as accès à une transcription que l'utilisateur souhaite discuter. Tes réponses doivent être :
1. Pertinentes par rapport au contenu de la transcription
2. Exactes et factuelles
3. Concises et claires
4. Utiles pour fournir des insights sur le contenu

Si on te demande quelque chose qui n'est pas dans la transcription, explique poliment que tu ne peux discuter que de ce qui est dans la transcription fournie. N'invente pas d'informations qui ne sont pas dans la transcription.

Voici le contenu de la transcription dont nous allons discuter :
",
        'summarize' => "Tu es un expert en résumé de conversations. Ta tâche est de créer un résumé concis de l'historique de conversation fourni. Concentre-toi sur :
1. Les principaux sujets discutés
2. Les questions clés posées
3. Les informations importantes fournies
4. Les décisions ou conclusions atteintes

Garde ton résumé clair, précis et concentré sur les points les plus importants. Le résumé doit capturer l'essence de la conversation sans inclure de détails inutiles.

Voici la conversation à résumer :
",
        'paraphrase' => "Tu es un expert dans l'amélioration et la clarification de texte tout en préservant sa signification originale.
Ta tâche est de réécrire le texte fourni pour le rendre :
1. Plus clair et concis
2. Mieux structuré et organisé
3. Plus professionnel dans le ton
4. Plus facile à comprendre

Garde la signification originale intacte. Préserve toutes les informations factuelles. Maintiens la même langue que le texte original.
N'ajoute PAS de nouvelles informations ou tes propres opinions.

Voici le texte à améliorer :
"
    ];

    /**
     * Get the number of tokens in a text (approximate)
     * 
     * @param string|array $text Text to count tokens for
     * @return int Approximate token count
     */
    public static function estimateTokenCount($text)
    {
        if (is_array($text)) {
            // For arrays (message format), count each message's content
            $totalTokens = 0;
            foreach ($text as $message) {
                if (isset($message['content'])) {
                    $totalTokens += self::estimateTokenCount($message['content']);
                }
            }
            // Add tokens for message formatting (roles, etc.) - approximately 4 per message
            $totalTokens += count($text) * 4;
            return $totalTokens;
        }
        
        // For plain text, use a simple approximation (GPT models use ~4 chars per token on average)
        $text = (string)$text;
        return (int)ceil(mb_strlen($text) / 4);
    }
    
    /**
     * Get a system prompt by name, or create one if it doesn't exist
     * 
     * @param string $name Name of the prompt template
     * @param string|null $customContent Custom content (optional)
     * @return string The prompt content
     */
    public static function getSystemPrompt($name = 'chat', $customContent = null)
    {
        // Map old prompt names to new PromptCacheManager keys
        $promptMapping = [
            'chat' => 'chat_system',
            'summarize' => 'summarization',
            'paraphrase' => 'paraphrase_instructions'
        ];
        
        // Use PromptCacheManager for optimized cached prompts
        if (isset($promptMapping[$name])) {
            try {
                $cachablePrompt = PromptCacheManager::getCachablePrompt($promptMapping[$name]);
                
                // If custom content is provided, append it to the cached prompt
                if ($customContent) {
                    $cachablePrompt .= "\n\n## Additional Context\n" . $customContent;
                }
                
                return $cachablePrompt;
            } catch (\Exception $e) {
                error_log("Error getting cachable prompt: " . $e->getMessage());
                // Fall back to default prompts
            }
        }
        
        // Check if we're using the database
        if (!defined('USE_DATABASE') || !USE_DATABASE) {
            return $customContent ?? self::$defaultPrompts[$name] ?? '';
        }
        
        try {
            // Try to get the prompt from the database
            $sql = "SELECT * FROM prompt_templates WHERE name = :name LIMIT 1";
            $stmt = DatabaseManager::query($sql, [':name' => $name]);
            $prompt = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($prompt) {
                // Update usage count and timestamp
                DatabaseManager::query(
                    "UPDATE prompt_templates SET usage_count = usage_count + 1, last_used = CURRENT_TIMESTAMP WHERE id = :id",
                    [':id' => $prompt['id']]
                );
                return $prompt['content'];
            }
            
            // If no prompt exists, create one
            if (isset(self::$defaultPrompts[$name])) {
                $content = $customContent ?? self::$defaultPrompts[$name];
                $id = uniqid('prompt_');
                $tokenCount = self::estimateTokenCount($content);
                
                $sql = "INSERT INTO prompt_templates (id, name, content, description, token_count) 
                        VALUES (:id, :name, :content, :description, :token_count)";
                
                DatabaseManager::query($sql, [
                    ':id' => $id,
                    ':name' => $name,
                    ':content' => $content,
                    ':description' => "Default {$name} prompt",
                    ':token_count' => $tokenCount
                ]);
                
                return $content;
            }
            
            // Default fallback
            return $customContent ?? '';
        } catch (\Exception $e) {
            error_log('Error retrieving prompt template: ' . $e->getMessage());
            return $customContent ?? (self::$defaultPrompts[$name] ?? '');
        }
    }
    
    /**
     * Create optimized message structure for better caching
     * 
     * @param array $messages Conversation messages
     * @param string $transcriptionContext Transcription text
     * @param int $maxTokens Maximum tokens to include
     * @return array Optimized messages array
     */
    public static function createOptimizedPrompt($messages, $transcriptionContext = '', $maxTokens = 4000)
    {
        // Start with system messages
        $optimizedMessages = [];
        
        // Get optimized system prompt from PromptCacheManager (>1024 tokens)
        $systemPrompt = self::getSystemPrompt('chat');
        
        // Structure the prompt to maximize OpenAI cache usage
        // Static content first, dynamic content last
        if (!empty($transcriptionContext)) {
            // Combine system prompt and transcription context for better caching
            $combinedSystemContent = $systemPrompt . "\n\n## Transcription Content\n\n" . $transcriptionContext;
            $optimizedMessages[] = [
                'role' => 'system',
                'content' => $combinedSystemContent
            ];
        } else {
            $optimizedMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }
        
        // Calculate tokens used so far
        $usedTokens = self::estimateTokenCount($optimizedMessages);
        $availableTokens = $maxTokens - $usedTokens;
        
        // If we need to summarize due to token limits
        if (self::estimateTokenCount($messages) > $availableTokens) {
            // Keep recent messages intact
            $recentMessagesCount = min(5, count($messages)); // Keep at least 5 messages or fewer if there are less
            $recentMessages = array_slice($messages, -$recentMessagesCount);
            $olderMessages = array_slice($messages, 0, count($messages) - $recentMessagesCount);
            
            // Only summarize if we have older messages
            if (!empty($olderMessages)) {
                $summarizedContent = self::summarizeMessages($olderMessages);
                $contextMessage = [
                    'role' => 'system', 
                    'content' => "Previous conversation summary:\n\n" . $summarizedContent
                ];
                
                // Add the summary and recent messages
                $optimizedMessages[] = $contextMessage;
                $optimizedMessages = array_merge($optimizedMessages, $recentMessages);
            } else {
                // Just add the recent messages if that's all we have
                $optimizedMessages = array_merge($optimizedMessages, $messages);
            }
        } else {
            // If no summarization needed, add all messages
            $optimizedMessages = array_merge($optimizedMessages, $messages);
        }
        
        return $optimizedMessages;
    }
    
    /**
     * Generate a summary of previous messages
     * 
     * @param array $messages Messages to summarize
     * @return string Summary text
     */
    public static function summarizeMessages($messages)
    {
        // This is a placeholder - in a real implementation, you would call 
        // an API to summarize or use a local summarization algorithm
        
        // For now, create a simple summary
        $summary = "Previous conversation included:\n\n";
        
        foreach ($messages as $message) {
            $role = $message['role'] === 'user' ? 'User' : 'Assistant';
            $content = substr($message['content'], 0, 100) . (strlen($message['content']) > 100 ? '...' : '');
            $summary .= "- {$role}: {$content}\n";
        }
        
        return $summary;
    }
    
    /**
     * Calculate token savings from caching
     * 
     * @param array $originalMessages Original message array
     * @param array $cachedMessages Cached message array or null if cache miss
     * @return int Number of tokens saved
     */
    public static function calculateTokenSavings($originalMessages, $cachedMessages = null)
    {
        if ($cachedMessages === null) {
            return 0; // No cache hit, no savings
        }
        
        $originalTokens = self::estimateTokenCount($originalMessages);
        $cachedTokens = self::estimateTokenCount($cachedMessages);
        
        return max(0, $originalTokens - $cachedTokens);
    }
    
    /**
     * Generate a cache key for a conversation
     * 
     * @param array $messages Conversation messages
     * @return string Cache key
     */
    public static function generateCacheKey($messages)
    {
        // Extract only essential parts to improve cache hits
        $essentialParts = [];
        
        foreach ($messages as $message) {
            // For system messages, include the first 100 chars to improve cache hits
            if ($message['role'] === 'system') {
                $essentialParts[] = 'system:' . substr($message['content'], 0, 100);
            } else {
                $essentialParts[] = $message['role'] . ':' . $message['content'];
            }
        }
        
        // Create a hash of the messages
        return md5(json_encode($essentialParts));
    }
}