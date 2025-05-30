-- Migration: update_transcriptions_table
-- Description: Adapter la table transcriptions pour correspondre au domaine
-- Created: 2025-05-30 18:49:00

-- Ajout des nouvelles colonnes pour correspondre au Domain Model

-- Ajouter la colonne user_id
ALTER TABLE transcriptions ADD COLUMN user_id TEXT;

-- Ajouter les colonnes pour les métadonnées YouTube
ALTER TABLE transcriptions ADD COLUMN youtube_title TEXT;
ALTER TABLE transcriptions ADD COLUMN youtube_video_id TEXT;
ALTER TABLE transcriptions ADD COLUMN youtube_duration INTEGER;

-- Ajouter les colonnes de statut et coût
ALTER TABLE transcriptions ADD COLUMN status TEXT DEFAULT 'pending';
ALTER TABLE transcriptions ADD COLUMN cost_amount DECIMAL(10,6);
ALTER TABLE transcriptions ADD COLUMN cost_currency TEXT DEFAULT 'USD';

-- Ajouter les colonnes de timing
ALTER TABLE transcriptions ADD COLUMN processing_started_at TIMESTAMP;
ALTER TABLE transcriptions ADD COLUMN processing_completed_at TIMESTAMP;
ALTER TABLE transcriptions ADD COLUMN updated_at TIMESTAMP;

-- Renommer certaines colonnes pour correspondre au domaine
-- Note: SQLite ne supporte pas ALTER COLUMN, nous devons utiliser une migration de données

-- Mise à jour des données existantes avec les nouvelles colonnes
UPDATE transcriptions SET 
    status = 'pending',
    user_id = 'migration_user_' || substr(id, 1, 8),
    updated_at = datetime('now');

-- Créer les index pour les performances
CREATE INDEX IF NOT EXISTS idx_transcriptions_user_id ON transcriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_transcriptions_status ON transcriptions(status);
CREATE INDEX IF NOT EXISTS idx_transcriptions_created_at ON transcriptions(created_at);
CREATE INDEX IF NOT EXISTS idx_transcriptions_youtube_id ON transcriptions(youtube_video_id);
CREATE INDEX IF NOT EXISTS idx_transcriptions_language ON transcriptions(language);