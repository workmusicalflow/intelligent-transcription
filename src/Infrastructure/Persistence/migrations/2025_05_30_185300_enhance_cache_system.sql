-- Migration: enhance_cache_system
-- Description: Améliorer le système de cache avec des tables optimisées
-- Created: 2025-05-30 18:53:00

-- Table principale de cache (remplace cache_entries si elle existe)
CREATE TABLE IF NOT EXISTS cache_entries (
    cache_key TEXT PRIMARY KEY,
    cache_value TEXT NOT NULL,
    expires_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL,
    access_count INTEGER DEFAULT 0,
    last_accessed_at INTEGER,
    tags TEXT -- JSON array de tags pour invalidation groupée
);

-- Table pour les statistiques de cache
CREATE TABLE IF NOT EXISTS cache_statistics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cache_key TEXT NOT NULL,
    action TEXT NOT NULL, -- 'hit', 'miss', 'set', 'delete'
    execution_time_ms INTEGER,
    data_size_bytes INTEGER,
    timestamp INTEGER NOT NULL,
    user_id TEXT,
    client_ip TEXT
);

-- Index pour les performances du cache
CREATE INDEX IF NOT EXISTS idx_cache_entries_expires_at ON cache_entries(expires_at);
CREATE INDEX IF NOT EXISTS idx_cache_entries_created_at ON cache_entries(created_at);
CREATE INDEX IF NOT EXISTS idx_cache_entries_last_accessed ON cache_entries(last_accessed_at);

-- Index pour les statistiques
CREATE INDEX IF NOT EXISTS idx_cache_stats_timestamp ON cache_statistics(timestamp);
CREATE INDEX IF NOT EXISTS idx_cache_stats_action ON cache_statistics(action, timestamp);
CREATE INDEX IF NOT EXISTS idx_cache_stats_key ON cache_statistics(cache_key);

-- Vue pour les statistiques de cache en temps réel
CREATE VIEW IF NOT EXISTS cache_performance_view AS
SELECT 
    DATE(timestamp, 'unixepoch') as date,
    action,
    COUNT(*) as count,
    AVG(execution_time_ms) as avg_execution_time,
    AVG(data_size_bytes) as avg_data_size
FROM cache_statistics 
WHERE timestamp > strftime('%s', 'now', '-7 days')
GROUP BY DATE(timestamp, 'unixepoch'), action
ORDER BY date DESC, action;

-- Nettoyer les anciennes entrées expirées
DELETE FROM cache_entries WHERE expires_at < strftime('%s', 'now');