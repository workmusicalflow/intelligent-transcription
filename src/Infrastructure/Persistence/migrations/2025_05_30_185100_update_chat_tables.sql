-- Migration: update_chat_tables
-- Description: Mettre à jour les tables de chat pour ajouter user_id et améliorer la structure
-- Created: 2025-05-30 18:51:00

-- Ajouter user_id aux conversations
ALTER TABLE chat_conversations ADD COLUMN user_id TEXT;

-- Ajouter des colonnes de statistiques
ALTER TABLE chat_conversations ADD COLUMN message_count INTEGER DEFAULT 0;
ALTER TABLE chat_conversations ADD COLUMN total_tokens INTEGER DEFAULT 0;

-- Ajouter des métadonnées aux messages
ALTER TABLE chat_messages ADD COLUMN tokens_used INTEGER DEFAULT 0;
ALTER TABLE chat_messages ADD COLUMN model_used TEXT;
ALTER TABLE chat_messages ADD COLUMN cost_amount DECIMAL(10,6);
ALTER TABLE chat_messages ADD COLUMN cost_currency TEXT DEFAULT 'USD';

-- Mettre à jour les données existantes
UPDATE chat_conversations SET 
    user_id = 'migration_user_' || substr(id, 1, 8),
    message_count = (
        SELECT COUNT(*) 
        FROM chat_messages 
        WHERE conversation_id = chat_conversations.id
    )
WHERE user_id IS NULL;

-- Créer les index pour les performances
CREATE INDEX IF NOT EXISTS idx_chat_conversations_user_id ON chat_conversations(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_conversations_transcription_id ON chat_conversations(transcription_id);
CREATE INDEX IF NOT EXISTS idx_chat_conversations_updated_at ON chat_conversations(updated_at);

CREATE INDEX IF NOT EXISTS idx_chat_messages_conversation_id ON chat_messages(conversation_id);
CREATE INDEX IF NOT EXISTS idx_chat_messages_role ON chat_messages(role);
CREATE INDEX IF NOT EXISTS idx_chat_messages_created_at ON chat_messages(created_at);