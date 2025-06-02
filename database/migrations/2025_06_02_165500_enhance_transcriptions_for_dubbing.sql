-- Migration: Enrichir la table transcriptions pour le doublage révolutionnaire
-- Date: 2025-06-02
-- Version: 1.0

-- Ajouter les nouvelles colonnes pour les capacités de doublage
ALTER TABLE transcriptions ADD COLUMN has_word_timestamps BOOLEAN DEFAULT FALSE;
ALTER TABLE transcriptions ADD COLUMN detected_language VARCHAR(10);
ALTER TABLE transcriptions ADD COLUMN speech_rate DECIMAL(4,2);
ALTER TABLE transcriptions ADD COLUMN word_count INTEGER;

-- Ajouter des index pour les performances
CREATE INDEX IF NOT EXISTS idx_transcriptions_has_word_timestamps ON transcriptions(has_word_timestamps);
CREATE INDEX IF NOT EXISTS idx_transcriptions_detected_language ON transcriptions(detected_language);
CREATE INDEX IF NOT EXISTS idx_transcriptions_speech_rate ON transcriptions(speech_rate);

-- Mettre à jour les transcriptions existantes avec les données disponibles
UPDATE transcriptions 
SET detected_language = CASE 
    WHEN whisper_data IS NOT NULL AND json_extract(whisper_data, '$.language') IS NOT NULL 
    THEN json_extract(whisper_data, '$.language')
    ELSE language
END
WHERE detected_language IS NULL;

UPDATE transcriptions 
SET word_count = CASE 
    WHEN text IS NOT NULL 
    THEN length(text) - length(replace(text, ' ', '')) + 1
    ELSE 0
END
WHERE word_count IS NULL;

UPDATE transcriptions 
SET speech_rate = CASE 
    WHEN duration > 0 AND word_count > 0 
    THEN (word_count * 60.0) / duration
    ELSE 0
END
WHERE speech_rate IS NULL;

-- Mettre à jour has_word_timestamps basé sur whisper_data
UPDATE transcriptions 
SET has_word_timestamps = CASE 
    WHEN whisper_data IS NOT NULL AND json_extract(whisper_data, '$.words') IS NOT NULL 
    THEN TRUE
    ELSE FALSE
END
WHERE has_word_timestamps = FALSE;

-- Commentaire sur les nouvelles colonnes
-- has_word_timestamps: TRUE si la transcription contient des timestamps word-level (Whisper-1 révolutionnaire)
-- detected_language: Langue détectée par Whisper (plus précise que la langue demandée)
-- speech_rate: Débit de parole en mots par minute (crucial pour le doublage)
-- word_count: Nombre de mots dans la transcription (pour les calculs de synchronisation)