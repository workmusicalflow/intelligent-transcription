# Prompt and Conversation Caching Implementation Plan

## Overview

This document outlines the plan to implement prompt caching and conversation caching for the Intelligent Transcription application, based on OpenAI's best practices. Implementing caching will significantly improve performance, reduce API costs, and enhance the user experience.

## Benefits of Caching

1. **Reduced Latency**: Up to 80% reduction in response time for prompts over 10,000 tokens
2. **Lower API Costs**: Fewer tokens processed means lower OpenAI API costs
3. **Improved User Experience**: Faster responses create a more fluid conversation experience
4. **Consistent Responses**: Caching helps maintain consistency in AI responses

## Implementation Approach

### 1. Prompt Caching

We'll implement prompt caching by structuring our prompts to take advantage of OpenAI's automatic caching:

#### A. System Prompt Optimization
- Place static content (instructions, context templates) at the beginning of prompts
- Keep dynamic content (user queries, specific transcription context) in later portions
- Maintain consistent formatting across requests

#### B. Cache Strategy
- Implement a two-level cache:
  1. **Client-side cache**: Store repetitive system prompts locally
  2. **OpenAI's automatic caching**: Leverage built-in caching for prompts >1,024 tokens

### 2. Conversation Context Caching

We'll implement a database-backed conversation context cache:

#### A. Database Structure
- Use the existing `chat_conversations` and `chat_messages` tables
- Add caching metadata columns for tracking cache efficiency

#### B. Message Compression
- Implement message summarization for long conversations
- Store full context for recent messages, summarized context for older ones

### 3. Technical Implementation

#### Phase 1: Cache-Optimized Prompt Structure

1. **Create Prompt Templates**:
   - Refactor system prompts to place static content first
   - Create template structure for consistent ordering

2. **Implement Message Management**:
   - Create message history compression/summarization
   - Track which messages are included in the context

#### Phase 2: Database Implementation

1. **Database Enhancements**:
   - Add cache metadata columns to track caching efficiency
   - Add indexes for quick retrieval of cached conversations

2. **Interface with OpenAI API**:
   - Modify API calls to leverage caching
   - Implement consistent tool ordering for better cache hits

#### Phase 3: Cache Monitoring and Optimization

1. **Cache Analytics**:
   - Track cache hit rates and latency metrics
   - Implement diagnostic logging for cache performance

2. **Performance Tuning**:
   - Optimize based on usage patterns
   - Implement automatic cache warm-up for common scenarios

## Implementation Details

### File Changes

1. **New Files**:
   - `src/Services/CacheService.php`: Central service for cache management
   - `src/Utils/PromptUtils.php`: Utilities for prompt optimization

2. **Modified Files**:
   - `src/Services/ChatService.php`: Add caching support
   - `src/Database/schema.sql`: Add caching metadata columns
   - `chat_api.py`: Optimize for better caching

### Database Schema Updates

```sql
-- Add caching metadata to chat_conversations
ALTER TABLE chat_conversations 
ADD COLUMN prompt_cache_id TEXT,
ADD COLUMN cache_hit_count INTEGER DEFAULT 0,
ADD COLUMN cache_miss_count INTEGER DEFAULT 0,
ADD COLUMN last_cache_hit TIMESTAMP;

-- Add caching metadata to chat_messages
ALTER TABLE chat_messages
ADD COLUMN is_summarized BOOLEAN DEFAULT 0,
ADD COLUMN original_content TEXT,
ADD COLUMN token_count INTEGER;
```

### Code Approach

1. **Prompt Template Implementation**:
```php
// Example of cache-optimized prompt structure
$systemPrompt = PromptUtils::getSystemPrompt(); // Static content
$transcriptionContext = $this->getTranscriptionContext($transcriptionId); // Dynamic content
$recentMessages = $this->getRecentMessages($conversationId, 10); // Dynamic messages

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'system', 'content' => "Transcription Context:\n$transcriptionContext"],
    // Dynamic user messages follow...
];
```

2. **Message Summarization**:
```php
// Example of message summarization for long conversations
public function summarizeMessages($messages, $maxTokens = 1000) {
    if (TokenUtils::countTokens($messages) <= $maxTokens) {
        return $messages; // No summarization needed
    }
    
    // Keep recent messages intact
    $recentMessages = array_slice($messages, -5);
    $olderMessages = array_slice($messages, 0, count($messages) - 5);
    
    // Summarize older messages
    $summarizedContent = $this->generateSummary($olderMessages);
    $summarizedMessage = [
        'role' => 'system',
        'content' => "Previous conversation summary:\n$summarizedContent"
    ];
    
    return array_merge([$summarizedMessage], $recentMessages);
}
```

3. **Cache Analytics**:
```php
// Example of cache hit tracking
public function trackCacheUsage($conversationId, $isCacheHit) {
    $sql = $isCacheHit 
        ? "UPDATE chat_conversations SET cache_hit_count = cache_hit_count + 1, last_cache_hit = CURRENT_TIMESTAMP WHERE id = :id"
        : "UPDATE chat_conversations SET cache_miss_count = cache_miss_count + 1 WHERE id = :id";
    
    DatabaseManager::query($sql, [':id' => $conversationId]);
}
```

## Implementation Timeline

1. **Phase 1 (Day 1-2)**: Prompt structure optimization
2. **Phase 2 (Day 3-4)**: Database schema updates and cache service implementation
3. **Phase 3 (Day 5)**: Testing and performance tuning

## Success Metrics

1. **Response Time**: Measure improvement in API response times
2. **API Costs**: Track reduction in token usage
3. **Cache Hit Rate**: Monitor percentage of requests leveraging caching

## Conclusion

By implementing prompt caching and conversation context optimization, we'll significantly improve the application's performance while reducing costs. The implementation will focus on both leveraging OpenAI's built-in caching and implementing our own optimizations at the application level.