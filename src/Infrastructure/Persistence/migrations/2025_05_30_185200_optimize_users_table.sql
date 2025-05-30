-- Migration: optimize_users_table
-- Description: Optimiser la table users et ajouter des colonnes pour les statistiques
-- Created: 2025-05-30 18:52:00

-- Ajouter des colonnes de statistiques utilisateur
ALTER TABLE users ADD COLUMN total_transcriptions INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN total_minutes_transcribed DECIMAL(10,2) DEFAULT 0;
ALTER TABLE users ADD COLUMN total_cost_amount DECIMAL(10,2) DEFAULT 0;
ALTER TABLE users ADD COLUMN total_cost_currency TEXT DEFAULT 'USD';
ALTER TABLE users ADD COLUMN last_activity_at TIMESTAMP;

-- Ajouter des colonnes de préférences
ALTER TABLE users ADD COLUMN preferred_language TEXT DEFAULT 'fr';
ALTER TABLE users ADD COLUMN email_notifications BOOLEAN DEFAULT 1;
ALTER TABLE users ADD COLUMN api_key TEXT UNIQUE; -- Pour l'API

-- Mettre à jour les données existantes avec des statistiques
UPDATE users SET 
    total_transcriptions = (
        SELECT COUNT(*) 
        FROM transcriptions 
        WHERE user_id = users.id
    ),
    total_minutes_transcribed = (
        SELECT COALESCE(SUM(duration), 0) / 60.0
        FROM transcriptions 
        WHERE user_id = users.id AND duration IS NOT NULL
    ),
    last_activity_at = COALESCE(last_login, created_at)
WHERE total_transcriptions = 0;

-- Créer des index optimisés
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);
CREATE INDEX IF NOT EXISTS idx_users_last_activity ON users(last_activity_at);
CREATE INDEX IF NOT EXISTS idx_users_api_key ON users(api_key) WHERE api_key IS NOT NULL;