<?php

namespace Services;

use Utils\PromptUtils;
use Database\DatabaseManager;

/**
 * Service for summarizing conversation messages
 */
class SummarizerService
{
    /**
     * Indique si le service utilise la base de données
     * 
     * @var bool
     */
    private $useDatabase;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->useDatabase = defined('USE_DATABASE') ? USE_DATABASE : false;
    }
    
    /**
     * Summarize a conversation to reduce token usage
     * 
     * @param string $conversationId Conversation ID
     * @param int $maxTokens Maximum tokens to include
     * @return array Result with summarized messages
     */
    public function summarizeConversation($conversationId, $maxTokens = 3000)
    {
        if (!$this->useDatabase) {
            return [
                'success' => false,
                'error' => 'Database not enabled'
            ];
        }
        
        try {
            // Get all messages for this conversation
            $sql = "SELECT * FROM chat_messages WHERE conversation_id = :conversation_id ORDER BY id ASC";
            $stmt = DatabaseManager::query($sql, [':conversation_id' => $conversationId]);
            $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (empty($messages)) {
                return [
                    'success' => false,
                    'error' => 'No messages found'
                ];
            }
            
            // Calculate total tokens
            $totalTokens = 0;
            foreach ($messages as $message) {
                // Use stored token count if available, otherwise estimate
                if (isset($message['token_count']) && $message['token_count'] > 0) {
                    $totalTokens += $message['token_count'];
                } else {
                    $tokenCount = PromptUtils::estimateTokenCount($message['content']);
                    $totalTokens += $tokenCount;
                    
                    // Update token count in database
                    if (!$message['is_summarized']) {
                        $sql = "UPDATE chat_messages SET token_count = :token_count WHERE id = :id";
                        DatabaseManager::query($sql, [
                            ':token_count' => $tokenCount,
                            ':id' => $message['id']
                        ]);
                    }
                }
            }
            
            // If we're under the token limit, no need to summarize
            if ($totalTokens <= $maxTokens) {
                return [
                    'success' => true,
                    'messages' => $this->formatMessages($messages),
                    'summarized' => false,
                    'total_tokens' => $totalTokens
                ];
            }
            
            // We need to summarize
            // Keep the most recent messages intact (max 5)
            $recentMessagesCount = min(5, count($messages));
            $recentMessages = array_slice($messages, -$recentMessagesCount);
            $olderMessages = array_slice($messages, 0, count($messages) - $recentMessagesCount);
            
            // If no older messages, just return recent ones
            if (empty($olderMessages)) {
                return [
                    'success' => true,
                    'messages' => $this->formatMessages($recentMessages),
                    'summarized' => false,
                    'total_tokens' => $totalTokens
                ];
            }
            
            // Check if we already have a summary
            $existingSummary = null;
            foreach ($messages as $message) {
                if ($message['is_summarized']) {
                    $existingSummary = $message;
                    break;
                }
            }
            
            if ($existingSummary) {
                // Use existing summary
                $summaryMessage = $existingSummary;
            } else {
                // Create a new summary
                $summaryText = $this->createSummary($olderMessages);
                
                // Store the summary in the database
                $sql = "INSERT INTO chat_messages (conversation_id, role, content, is_summarized, original_content, token_count) 
                        VALUES (:conversation_id, :role, :content, 1, :original_content, :token_count)";
                
                // Encode original messages as JSON
                $originalContent = json_encode($this->formatMessages($olderMessages));
                $tokenCount = PromptUtils::estimateTokenCount($summaryText);
                
                DatabaseManager::query($sql, [
                    ':conversation_id' => $conversationId,
                    ':role' => 'system',
                    ':content' => $summaryText,
                    ':original_content' => $originalContent,
                    ':token_count' => $tokenCount
                ]);
                
                // Get the inserted summary
                $summaryId = DatabaseManager::lastInsertId();
                $sql = "SELECT * FROM chat_messages WHERE id = :id";
                $stmt = DatabaseManager::query($sql, [':id' => $summaryId]);
                $summaryMessage = $stmt->fetch(\PDO::FETCH_ASSOC);
            }
            
            // Combine summary with recent messages
            $summarizedMessages = [$summaryMessage];
            $summarizedMessages = array_merge($summarizedMessages, $recentMessages);
            
            // Calculate new token count
            $newTokenCount = 0;
            foreach ($summarizedMessages as $message) {
                $newTokenCount += isset($message['token_count']) ? $message['token_count'] : PromptUtils::estimateTokenCount($message['content']);
            }
            
            return [
                'success' => true,
                'messages' => $this->formatMessages($summarizedMessages),
                'summarized' => true,
                'original_token_count' => $totalTokens,
                'new_token_count' => $newTokenCount,
                'tokens_saved' => $totalTokens - $newTokenCount
            ];
        } catch (\Exception $e) {
            error_log('Error summarizing conversation: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error summarizing conversation: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Format database messages for API
     * 
     * @param array $dbMessages Messages from database
     * @return array Formatted messages
     */
    private function formatMessages($dbMessages)
    {
        $formattedMessages = [];
        foreach ($dbMessages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
                'id' => $message['id'],
                'is_summarized' => (bool)$message['is_summarized']
            ];
        }
        return $formattedMessages;
    }
    
    /**
     * Create a summary of conversation messages
     * 
     * @param array $messages Messages to summarize
     * @return string Summary text
     */
    private function createSummary($messages)
    {
        // Format messages for display
        $formattedMessages = $this->formatMessages($messages);
        
        // Create API-compatible message format
        $apiMessages = [];
        
        // Add system instruction as the first message
        $apiMessages[] = [
            'role' => 'system',
            'content' => PromptUtils::getSystemPrompt('summarize')
        ];
        
        // Add messages to summarize
        foreach ($formattedMessages as $message) {
            $apiMessages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        
        // Create a file with the content to summarize
        $contextFile = tempnam(sys_get_temp_dir(), 'summarize_context_');
        file_put_contents($contextFile, json_encode(['messages' => $apiMessages]));
        
        // Create a file for the output
        $outputFile = tempnam(sys_get_temp_dir(), 'summarize_output_');
        
        // Execute the Python script for summarization
        $pythonPath = PYTHON_PATH;
        $scriptPath = BASE_DIR . '/chat_api.py';
        
        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($scriptPath) . ' ' .
            '--summarize=true ' . 
            '--context=' . escapeshellarg($contextFile) . ' ' .
            '--output=' . escapeshellarg($outputFile);
        
        // Execute the command using our Python error utility
        $result = \Utils\PythonErrorUtils::executePythonProcess($command, 'résumé de conversation', $outputFile);
        
        // Clean up temporary files
        @unlink($contextFile);
        @unlink($outputFile);
        
        // Check if the operation was successful
        if (isset($result['success']) && $result['success'] && isset($result['response'])) {
            return $result['response'] . "\n\n(This is an automatically generated summary of the conversation history to optimize context length.)";
        } else {
            // Log the error but don't expose it to the user - just fall back to simple summary
            error_log("Error in AI-based summarization: " . ($result['error'] ?? 'Unknown error'));
            return $this->createSimpleSummary($messages);
        }
    }
    
    /**
     * Create a simple summary as fallback method
     * 
     * @param array $messages Messages to summarize
     * @return string Simple summary text
     */
    private function createSimpleSummary($messages)
    {
        // Format messages for display
        $formattedMessages = $this->formatMessages($messages);
        
        // Create a simple summary introduction
        $summary = "This is a summary of " . count($messages) . " previous messages:\n\n";
        
        // Group messages by role
        $userMessages = array_filter($formattedMessages, function($msg) { return $msg['role'] === 'user'; });
        $assistantMessages = array_filter($formattedMessages, function($msg) { return $msg['role'] === 'assistant'; });
        
        // Add user questions/topics
        $summary .= "User discussed the following topics:\n";
        foreach ($userMessages as $message) {
            // Get first 50 chars of each message
            $preview = mb_substr($message['content'], 0, 50) . (mb_strlen($message['content']) > 50 ? '...' : '');
            $summary .= "- " . $preview . "\n";
        }
        
        // Add summary of assistant responses
        $summary .= "\nAssistant provided information about:\n";
        foreach ($assistantMessages as $message) {
            // Get first 50 chars of each message
            $preview = mb_substr($message['content'], 0, 50) . (mb_strlen($message['content']) > 50 ? '...' : '');
            $summary .= "- " . $preview . "\n";
        }
        
        // Note about summarization
        $summary .= "\n(This is an automatically generated summary of the conversation history to optimize context length.)";
        
        return $summary;
    }
    
    /**
     * Expand a summarized message to see original messages
     * 
     * @param int $messageId ID of summarized message
     * @return array Original messages
     */
    public function expandSummary($messageId)
    {
        if (!$this->useDatabase) {
            return [
                'success' => false,
                'error' => 'Database not enabled'
            ];
        }
        
        try {
            // Get the summarized message
            $sql = "SELECT * FROM chat_messages WHERE id = :id AND is_summarized = 1 LIMIT 1";
            $stmt = DatabaseManager::query($sql, [':id' => $messageId]);
            $message = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$message) {
                return [
                    'success' => false,
                    'error' => 'Summarized message not found'
                ];
            }
            
            // Decode original messages
            $originalMessages = json_decode($message['original_content'], true);
            
            return [
                'success' => true,
                'summary' => $message['content'],
                'original_messages' => $originalMessages
            ];
        } catch (\Exception $e) {
            error_log('Error expanding summary: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error expanding summary'
            ];
        }
    }
}