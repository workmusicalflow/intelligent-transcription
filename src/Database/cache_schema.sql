-- Schema update for caching implementation

-- Add caching metadata to chat_conversations
ALTER TABLE chat_conversations ADD COLUMN prompt_cache_id TEXT;
ALTER TABLE chat_conversations ADD COLUMN cache_hit_count INTEGER DEFAULT 0;
ALTER TABLE chat_conversations ADD COLUMN cache_miss_count INTEGER DEFAULT 0;
ALTER TABLE chat_conversations ADD COLUMN last_cache_hit TIMESTAMP;

-- Add caching metadata to chat_messages
ALTER TABLE chat_messages ADD COLUMN is_summarized BOOLEAN DEFAULT 0;
ALTER TABLE chat_messages ADD COLUMN original_content TEXT;
ALTER TABLE chat_messages ADD COLUMN token_count INTEGER;

-- Create index on prompt_cache_id for faster lookups
CREATE INDEX IF NOT EXISTS idx_conversations_cache_id ON chat_conversations(prompt_cache_id);

-- Create index on is_summarized for message filtering
CREATE INDEX IF NOT EXISTS idx_messages_summarized ON chat_messages(is_summarized);

-- Create a dedicated table for prompt templates
CREATE TABLE IF NOT EXISTS prompt_templates (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    content TEXT NOT NULL,
    description TEXT,
    token_count INTEGER,
    last_used TIMESTAMP,
    usage_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create a table for caching analytics
CREATE TABLE IF NOT EXISTS cache_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    conversation_id TEXT,
    request_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_cache_hit BOOLEAN,
    response_time_ms INTEGER,
    tokens_used INTEGER,
    tokens_saved INTEGER,
    prompt_size INTEGER,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
);