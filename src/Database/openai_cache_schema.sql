-- OpenAI Prompt Cache Metrics Schema
-- This schema tracks OpenAI prompt caching performance

-- Table for storing OpenAI cache metrics per request
CREATE TABLE IF NOT EXISTS openai_cache_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    conversation_id TEXT NOT NULL,
    cached_tokens INTEGER DEFAULT 0,
    prompt_tokens INTEGER DEFAULT 0,
    completion_tokens INTEGER DEFAULT 0,
    total_tokens INTEGER DEFAULT 0,
    cache_hit_rate REAL DEFAULT 0,
    cache_eligible BOOLEAN DEFAULT 0,
    estimated_cost_saved REAL DEFAULT 0,
    model TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
);

-- Index for performance
CREATE INDEX IF NOT EXISTS idx_openai_cache_conversation ON openai_cache_metrics(conversation_id);
CREATE INDEX IF NOT EXISTS idx_openai_cache_created ON openai_cache_metrics(created_at);
CREATE INDEX IF NOT EXISTS idx_openai_cache_model ON openai_cache_metrics(model);

-- Table for aggregated daily statistics
CREATE TABLE IF NOT EXISTS openai_cache_daily_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL UNIQUE,
    total_requests INTEGER DEFAULT 0,
    cache_eligible_requests INTEGER DEFAULT 0,
    total_cached_tokens INTEGER DEFAULT 0,
    total_prompt_tokens INTEGER DEFAULT 0,
    avg_cache_hit_rate REAL DEFAULT 0,
    max_cache_hit_rate REAL DEFAULT 0,
    total_cost_saved REAL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index for date lookups
CREATE INDEX IF NOT EXISTS idx_openai_daily_date ON openai_cache_daily_stats(date);

-- View for hourly statistics
CREATE VIEW IF NOT EXISTS openai_cache_hourly_stats AS
SELECT 
    strftime('%Y-%m-%d %H:00:00', created_at) as hour,
    COUNT(*) as requests,
    SUM(cached_tokens) as cached_tokens,
    SUM(prompt_tokens) as prompt_tokens,
    AVG(cache_hit_rate) as avg_cache_hit_rate,
    SUM(estimated_cost_saved) as cost_saved,
    SUM(CASE WHEN cache_eligible = 1 THEN 1 ELSE 0 END) as eligible_requests
FROM openai_cache_metrics
GROUP BY hour
ORDER BY hour DESC;

-- View for model-specific statistics
CREATE VIEW IF NOT EXISTS openai_cache_model_stats AS
SELECT 
    model,
    COUNT(*) as total_requests,
    SUM(cached_tokens) as total_cached_tokens,
    SUM(prompt_tokens) as total_prompt_tokens,
    CASE 
        WHEN SUM(prompt_tokens) > 0 
        THEN ROUND((CAST(SUM(cached_tokens) AS REAL) / SUM(prompt_tokens)) * 100, 2)
        ELSE 0 
    END as overall_cache_hit_rate,
    AVG(cache_hit_rate) as avg_cache_hit_rate,
    SUM(estimated_cost_saved) as total_cost_saved
FROM openai_cache_metrics
GROUP BY model;

-- Trigger to update daily stats automatically
CREATE TRIGGER IF NOT EXISTS update_daily_stats_after_insert
AFTER INSERT ON openai_cache_metrics
BEGIN
    INSERT OR REPLACE INTO openai_cache_daily_stats (
        date,
        total_requests,
        cache_eligible_requests,
        total_cached_tokens,
        total_prompt_tokens,
        avg_cache_hit_rate,
        max_cache_hit_rate,
        total_cost_saved,
        updated_at
    )
    SELECT 
        DATE(NEW.created_at) as date,
        COUNT(*) as total_requests,
        SUM(CASE WHEN cache_eligible = 1 THEN 1 ELSE 0 END) as cache_eligible_requests,
        SUM(cached_tokens) as total_cached_tokens,
        SUM(prompt_tokens) as total_prompt_tokens,
        AVG(cache_hit_rate) as avg_cache_hit_rate,
        MAX(cache_hit_rate) as max_cache_hit_rate,
        SUM(estimated_cost_saved) as total_cost_saved,
        CURRENT_TIMESTAMP
    FROM openai_cache_metrics
    WHERE DATE(created_at) = DATE(NEW.created_at);
END;