-- Migration: create_domain_events_table
-- Description: Créer la table pour stocker les événements de domaine
-- Created: 2025-05-30 18:50:00

-- Table pour stocker les événements de domaine
CREATE TABLE IF NOT EXISTS domain_events (
    id TEXT PRIMARY KEY,
    event_name TEXT NOT NULL,
    aggregate_id TEXT NOT NULL,
    aggregate_type TEXT NOT NULL,
    event_data TEXT NOT NULL, -- JSON serialized
    occurred_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed BOOLEAN DEFAULT 0,
    processed_at TIMESTAMP NULL
);

-- Index pour les performances
CREATE INDEX IF NOT EXISTS idx_domain_events_aggregate ON domain_events(aggregate_type, aggregate_id);
CREATE INDEX IF NOT EXISTS idx_domain_events_name ON domain_events(event_name);
CREATE INDEX IF NOT EXISTS idx_domain_events_occurred_at ON domain_events(occurred_at);
CREATE INDEX IF NOT EXISTS idx_domain_events_processed ON domain_events(processed, created_at);

-- Table pour les handlers d'événements (pour tracking)
CREATE TABLE IF NOT EXISTS event_handlers_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id TEXT NOT NULL,
    handler_name TEXT NOT NULL,
    status TEXT NOT NULL, -- 'success', 'failed', 'retry'
    error_message TEXT NULL,
    execution_time_ms INTEGER NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES domain_events(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_event_handlers_log_event_id ON event_handlers_log(event_id);
CREATE INDEX IF NOT EXISTS idx_event_handlers_log_status ON event_handlers_log(status, created_at);