<?php

namespace Utils;

use Database\DatabaseManager;

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
        'chat' => "You are a helpful AI assistant specialized in discussing transcribed content. You have access to a transcription that the user wants to discuss. Your responses should be:
1. Relevant to the transcription content
2. Accurate and factual
3. Concise and clear
4. Helpful in providing insights about the content

If asked about something not in the transcription, politely explain that you can only discuss what's in the provided transcript. Don't make up information that isn't in the transcript.

The following is the transcription content that we'll be discussing:
",
        'summarize' => "You are an expert at summarizing conversations. Your task is to create a concise summary of the conversation history provided. Focus on:
1. The main topics discussed
2. Key questions asked
3. Important information provided
4. Any decisions or conclusions reached

Keep your summary clear, accurate, and focused on the most important points. The summary should capture the essence of the conversation without including unnecessary details.

Here is the conversation to summarize:
",
        'paraphrase' => "You are an expert at improving and clarifying text while preserving its original meaning.
Your task is to rewrite the provided text to make it:
1. More clear and concise
2. Better structured and organized
3. More professional in tone
4. Easier to understand

Keep the original meaning intact. Preserve all factual information. Maintain the same language as the original text.
DO NOT add new information or your own opinions.

Here is the text to improve:
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
        
        // Add system instruction as the first message (for best caching)
        $systemPrompt = self::getSystemPrompt('chat');
        $optimizedMessages[] = [
            'role' => 'system',
            'content' => $systemPrompt
        ];
        
        // Add transcription context if available
        if (!empty($transcriptionContext)) {
            $optimizedMessages[] = [
                'role' => 'system',
                'content' => "Transcription:\n\n" . $transcriptionContext
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