<?php

namespace Services;

use Database\DatabaseManager;
use Utils\PromptUtils;

/**
 * Service for managing caching of prompts and conversations
 */
class CacheService
{
    /**
     * Indique si le service utilise la base de données
     * 
     * @var bool
     */
    private $useDatabase;
    
    /**
     * In-memory cache for the current session
     * 
     * @var array
     */
    private $memoryCache = [];
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->useDatabase = defined('USE_DATABASE') ? USE_DATABASE : false;
    }
    
    /**
     * Get a cached conversation by cache ID
     * 
     * @param string $cacheId Cache ID to retrieve
     * @return array|null Cached conversation or null if not found
     */
    public function getCachedConversation($cacheId)
    {
        // Check in-memory cache first (fastest)
        if (isset($this->memoryCache[$cacheId])) {
            return $this->memoryCache[$cacheId];
        }
        
        // If database is available, check there
        if ($this->useDatabase) {
            try {
                $sql = "SELECT * FROM chat_conversations WHERE prompt_cache_id = :cache_id LIMIT 1";
                $stmt = DatabaseManager::query($sql, [':cache_id' => $cacheId]);
                $conversation = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($conversation) {
                    // Retrieve messages for this conversation
                    $sql = "SELECT * FROM chat_messages WHERE conversation_id = :conversation_id ORDER BY id ASC";
                    $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversation['id']]);
                    $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    // Format messages for API
                    $formattedMessages = [];
                    foreach ($messages as $message) {
                        $formattedMessages[] = [
                            'role' => $message['role'],
                            'content' => $message['content']
                        ];
                    }
                    
                    // Store in memory cache
                    $this->memoryCache[$cacheId] = [
                        'conversation_id' => $conversation['id'],
                        'messages' => $formattedMessages
                    ];
                    
                    // Update cache hit counter
                    $this->recordCacheHit($conversation['id']);
                    
                    return $this->memoryCache[$cacheId];
                }
            } catch (\Exception $e) {
                error_log('Error retrieving cached conversation: ' . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Store a conversation in cache
     * 
     * @param string $conversationId Conversation ID
     * @param array $messages Conversation messages
     * @return string Cache ID
     */
    public function cacheConversation($conversationId, $messages)
    {
        // Generate a cache ID based on message content
        $cacheId = PromptUtils::generateCacheKey($messages);
        
        // Store in memory cache
        $this->memoryCache[$cacheId] = [
            'conversation_id' => $conversationId,
            'messages' => $messages
        ];
        
        // If database is available, store there
        if ($this->useDatabase) {
            try {
                // Update conversation with cache ID
                $sql = "UPDATE chat_conversations SET prompt_cache_id = :cache_id WHERE id = :id";
                DatabaseManager::query($sql, [
                    ':cache_id' => $cacheId,
                    ':id' => $conversationId
                ]);
                
                // Record cache miss since this is a new cache entry
                $this->recordCacheMiss($conversationId);
            } catch (\Exception $e) {
                error_log('Error caching conversation: ' . $e->getMessage());
            }
        }
        
        return $cacheId;
    }
    
    /**
     * Record a cache hit for analytics
     * 
     * @param string $conversationId Conversation ID
     * @param int $responseTime Response time in milliseconds
     * @param int $tokensSaved Tokens saved by cache hit
     */
    public function recordCacheHit($conversationId, $responseTime = null, $tokensSaved = null)
    {
        if (!$this->useDatabase) {
            return;
        }
        
        try {
            // Update conversation hit counter
            $sql = "UPDATE chat_conversations SET cache_hit_count = cache_hit_count + 1, last_cache_hit = CURRENT_TIMESTAMP WHERE id = :id";
            DatabaseManager::query($sql, [':id' => $conversationId]);
            
            // If we have analytics data, record it
            if ($responseTime !== null) {
                $sql = "INSERT INTO cache_analytics (conversation_id, is_cache_hit, response_time_ms, tokens_saved) 
                        VALUES (:conversation_id, 1, :response_time, :tokens_saved)";
                
                DatabaseManager::query($sql, [
                    ':conversation_id' => $conversationId,
                    ':response_time' => $responseTime,
                    ':tokens_saved' => $tokensSaved
                ]);
            }
        } catch (\Exception $e) {
            error_log('Error recording cache hit: ' . $e->getMessage());
        }
    }
    
    /**
     * Record a cache miss for analytics
     * 
     * @param string $conversationId Conversation ID
     * @param int $responseTime Response time in milliseconds
     * @param int $tokensUsed Tokens used in request
     */
    public function recordCacheMiss($conversationId, $responseTime = null, $tokensUsed = null)
    {
        if (!$this->useDatabase) {
            return;
        }
        
        try {
            // Update conversation miss counter
            $sql = "UPDATE chat_conversations SET cache_miss_count = cache_miss_count + 1 WHERE id = :id";
            DatabaseManager::query($sql, [':id' => $conversationId]);
            
            // If we have analytics data, record it
            if ($responseTime !== null) {
                $sql = "INSERT INTO cache_analytics (conversation_id, is_cache_hit, response_time_ms, tokens_used) 
                        VALUES (:conversation_id, 0, :response_time, :tokens_used)";
                
                DatabaseManager::query($sql, [
                    ':conversation_id' => $conversationId,
                    ':response_time' => $responseTime,
                    ':tokens_used' => $tokensUsed
                ]);
            }
        } catch (\Exception $e) {
            error_log('Error recording cache miss: ' . $e->getMessage());
        }
    }
    
    /**
     * Record message token counts for analytics
     * 
     * @param string $conversationId Conversation ID
     * @param array $messages Messages to analyze
     */
    public function recordMessageTokens($conversationId, $messages)
    {
        if (!$this->useDatabase) {
            return;
        }
        
        try {
            // Get message IDs for this conversation
            $sql = "SELECT id, role, content FROM chat_messages WHERE conversation_id = :conversation_id ORDER BY id ASC";
            $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
            $dbMessages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Skip if no messages found
            if (empty($dbMessages) || count($dbMessages) != count($messages)) {
                return;
            }
            
            // Update token counts for each message
            foreach ($dbMessages as $index => $dbMessage) {
                if (isset($messages[$index])) {
                    $tokenCount = PromptUtils::estimateTokenCount($messages[$index]['content']);
                    
                    $sql = "UPDATE chat_messages SET token_count = :token_count WHERE id = :id";
                    DatabaseManager::query($sql, [
                        ':token_count' => $tokenCount,
                        ':id' => $dbMessage['id']
                    ]);
                }
            }
        } catch (\Exception $e) {
            error_log('Error recording message tokens: ' . $e->getMessage());
        }
    }
    
    /**
     * Get cache analytics for a conversation
     * 
     * @param string $conversationId Conversation ID
     * @return array Cache analytics data
     */
    public function getCacheAnalytics($conversationId = null)
    {
        if (!$this->useDatabase) {
            return [
                'success' => false,
                'error' => 'Database not enabled'
            ];
        }
        
        try {
            if ($conversationId) {
                // Get analytics for a specific conversation
                $sql = "SELECT 
                            c.id, c.title, c.cache_hit_count, c.cache_miss_count, c.last_cache_hit,
                            COUNT(m.id) as message_count,
                            SUM(m.token_count) as total_tokens,
                            SUM(CASE WHEN m.is_summarized = 1 THEN 1 ELSE 0 END) as summarized_messages,
                            (SELECT COUNT(*) FROM cache_analytics WHERE conversation_id = c.id AND is_cache_hit = 1) as cache_hits,
                            (SELECT COUNT(*) FROM cache_analytics WHERE conversation_id = c.id AND is_cache_hit = 0) as cache_misses,
                            (SELECT AVG(response_time_ms) FROM cache_analytics WHERE conversation_id = c.id AND is_cache_hit = 1) as avg_hit_response_time,
                            (SELECT AVG(response_time_ms) FROM cache_analytics WHERE conversation_id = c.id AND is_cache_hit = 0) as avg_miss_response_time,
                            (SELECT MIN(response_time_ms) FROM cache_analytics WHERE conversation_id = c.id AND is_cache_hit = 1) as min_hit_response_time,
                            (SELECT MAX(response_time_ms) FROM cache_analytics WHERE conversation_id = c.id AND is_cache_hit = 1) as max_hit_response_time,
                            (SELECT SUM(tokens_saved) FROM cache_analytics WHERE conversation_id = c.id) as tokens_saved,
                            (SELECT SUM(tokens_used) FROM cache_analytics WHERE conversation_id = c.id) as tokens_used
                        FROM 
                            chat_conversations c
                        LEFT JOIN 
                            chat_messages m ON c.id = m.conversation_id
                        WHERE 
                            c.id = :conversation_id
                        GROUP BY 
                            c.id";
                
                $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
                $analytics = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$analytics) {
                    return [
                        'success' => false,
                        'error' => 'Conversation not found'
                    ];
                }
                
                // Calculate hit rate and cost savings
                if (($analytics['cache_hit_count'] + $analytics['cache_miss_count']) > 0) {
                    $analytics['cache_hit_rate'] = $analytics['cache_hit_count'] / ($analytics['cache_hit_count'] + $analytics['cache_miss_count']);
                    $analytics['cache_hit_rate_pct'] = number_format($analytics['cache_hit_rate'] * 100, 2) . '%';
                } else {
                    $analytics['cache_hit_rate'] = 0;
                    $analytics['cache_hit_rate_pct'] = '0.00%';
                }
                
                // Calculate estimated cost savings (assuming $0.002 per 1K tokens for GPT-3.5)
                $costPerThousandTokens = 0.002;
                if (!empty($analytics['tokens_saved'])) {
                    $analytics['estimated_cost_saved'] = ($analytics['tokens_saved'] / 1000) * $costPerThousandTokens;
                    $analytics['estimated_cost_saved_formatted'] = '$' . number_format($analytics['estimated_cost_saved'], 4);
                } else {
                    $analytics['estimated_cost_saved'] = 0;
                    $analytics['estimated_cost_saved_formatted'] = '$0.0000';
                }
                
                // Get timestamps of cache hits/misses
                $sql = "SELECT created_at, is_cache_hit, response_time_ms 
                        FROM cache_analytics 
                        WHERE conversation_id = :conversation_id 
                        ORDER BY created_at ASC";
                
                $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
                $analytics['timeline'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                return [
                    'success' => true,
                    'analytics' => $analytics
                ];
            } else {
                // Get overall analytics
                $sql = "SELECT 
                            SUM(cache_hit_count) as total_hits,
                            SUM(cache_miss_count) as total_misses,
                            COUNT(*) as total_conversations,
                            (SELECT COUNT(*) FROM chat_messages) as total_messages,
                            (SELECT COUNT(*) FROM chat_messages WHERE is_summarized = 1) as total_summarized,
                            (SELECT SUM(token_count) FROM chat_messages WHERE token_count IS NOT NULL) as total_tokens,
                            (SELECT SUM(tokens_saved) FROM cache_analytics WHERE tokens_saved IS NOT NULL) as total_tokens_saved,
                            (SELECT SUM(tokens_used) FROM cache_analytics WHERE tokens_used IS NOT NULL) as total_tokens_used,
                            (SELECT AVG(response_time_ms) FROM cache_analytics WHERE is_cache_hit = 1) as avg_hit_response_time,
                            (SELECT AVG(response_time_ms) FROM cache_analytics WHERE is_cache_hit = 0) as avg_miss_response_time,
                            (SELECT MIN(response_time_ms) FROM cache_analytics WHERE is_cache_hit = 1) as min_hit_response_time,
                            (SELECT MAX(response_time_ms) FROM cache_analytics WHERE is_cache_hit = 1) as max_hit_response_time,
                            (SELECT COUNT(*) FROM chat_conversations WHERE prompt_cache_id IS NOT NULL) as conversations_with_cache
                        FROM 
                            chat_conversations";
                
                $stmt = DatabaseManager::query($sql);
                $analytics = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                // Calculate cache hit rate
                if (($analytics['total_hits'] + $analytics['total_misses']) > 0) {
                    $analytics['cache_hit_rate'] = $analytics['total_hits'] / ($analytics['total_hits'] + $analytics['total_misses']);
                } else {
                    $analytics['cache_hit_rate'] = 0;
                }
                
                // Format percentage
                $analytics['cache_hit_rate_pct'] = number_format($analytics['cache_hit_rate'] * 100, 2) . '%';
                
                // Calculate estimated cost savings (assuming $0.002 per 1K tokens for GPT-3.5)
                $costPerThousandTokens = 0.002;
                if (!empty($analytics['total_tokens_saved'])) {
                    $analytics['estimated_cost_saved'] = ($analytics['total_tokens_saved'] / 1000) * $costPerThousandTokens;
                    $analytics['estimated_cost_saved_formatted'] = '$' . number_format($analytics['estimated_cost_saved'], 4);
                } else {
                    $analytics['estimated_cost_saved'] = 0;
                    $analytics['estimated_cost_saved_formatted'] = '$0.0000';
                }
                
                // Get daily analytics for a chart
                $sql = "SELECT 
                           DATE(created_at) as date,
                           SUM(CASE WHEN is_cache_hit = 1 THEN 1 ELSE 0 END) as hits,
                           SUM(CASE WHEN is_cache_hit = 0 THEN 1 ELSE 0 END) as misses,
                           SUM(tokens_saved) as tokens_saved
                        FROM 
                           cache_analytics
                        GROUP BY 
                           DATE(created_at)
                        ORDER BY
                           date ASC";
                
                $stmt = DatabaseManager::query($sql);
                $analytics['daily_stats'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                return [
                    'success' => true,
                    'analytics' => $analytics
                ];
            }
        } catch (\Exception $e) {
            error_log('Error retrieving cache analytics: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error retrieving cache analytics: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clear all cache data for a conversation
     * 
     * @param string $conversationId Conversation ID
     * @return array Result of operation
     */
    public function clearCache($conversationId = null)
    {
        if (!$this->useDatabase) {
            return [
                'success' => false,
                'error' => 'Database not enabled'
            ];
        }
        
        try {
            if ($conversationId) {
                // Clear cache for a specific conversation
                $sql = "UPDATE chat_conversations 
                       SET prompt_cache_id = NULL 
                       WHERE id = :conversation_id";
                
                DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
                
                // Remove from memory cache
                foreach ($this->memoryCache as $key => $value) {
                    if (isset($value['conversation_id']) && $value['conversation_id'] === $conversationId) {
                        unset($this->memoryCache[$key]);
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'Cache cleared for conversation'
                ];
            } else {
                // Clear all cache
                $sql = "UPDATE chat_conversations SET prompt_cache_id = NULL";
                DatabaseManager::query($sql);
                
                // Reset memory cache
                $this->memoryCache = [];
                
                return [
                    'success' => true,
                    'message' => 'All cache cleared'
                ];
            }
        } catch (\Exception $e) {
            error_log('Error clearing cache: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error clearing cache: ' . $e->getMessage()
            ];
        }
    }
}