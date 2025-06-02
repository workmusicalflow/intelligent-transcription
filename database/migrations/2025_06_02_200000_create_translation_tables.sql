-- Migration pour système de traduction révolutionnaire
-- Date: 2025-06-02
-- Description: Création tables pour projets traduction, versions traduites et cache

-- Table principale des projets de traduction
CREATE TABLE IF NOT EXISTS translation_projects (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    transcription_id TEXT NOT NULL,
    target_language TEXT NOT NULL,
    source_language TEXT,
    
    -- Configuration de traduction
    provider_used TEXT NOT NULL DEFAULT 'gpt-4o-mini',
    config_json TEXT, -- Configuration TranslationConfig sérialisée
    
    -- Statut et métadonnées
    status TEXT NOT NULL DEFAULT 'pending', -- pending, processing, completed, failed
    priority INTEGER DEFAULT 3,
    
    -- Métriques de performance
    estimated_cost REAL,
    actual_cost REAL,
    processing_time_seconds REAL,
    quality_score REAL, -- 0.0 à 1.0
    
    -- Métadonnées timing
    segments_count INTEGER,
    total_duration_seconds REAL,
    word_count INTEGER,
    character_count INTEGER,
    
    -- Capacités avancées
    has_word_timestamps BOOLEAN DEFAULT 0,
    has_emotional_context BOOLEAN DEFAULT 0,
    has_character_names BOOLEAN DEFAULT 0,
    has_technical_terms BOOLEAN DEFAULT 0,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    completed_at DATETIME,
    
    -- Contraintes
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transcription_id) REFERENCES transcriptions(id) ON DELETE CASCADE
);

-- Table des versions de traduction (pour versionning et A/B testing)
CREATE TABLE IF NOT EXISTS translation_versions (
    id TEXT PRIMARY KEY,
    project_id TEXT NOT NULL,
    version_number INTEGER NOT NULL DEFAULT 1,
    
    -- Données de traduction
    segments_json TEXT NOT NULL, -- Segments traduits avec métadonnées
    provider_used TEXT NOT NULL,
    config_snapshot TEXT, -- Configuration utilisée pour cette version
    
    -- Métriques qualité spécifiques à cette version
    quality_score REAL,
    timestamp_preservation_score REAL, -- Précision préservation timestamps
    length_adaptation_ratio REAL, -- Ratio adaptation longueur pour doublage
    emotional_preservation_score REAL, -- Conservation émotions (si applicable)
    
    -- Métadonnées version
    is_active BOOLEAN DEFAULT 1,
    notes TEXT, -- Notes utilisateur ou système
    generated_by TEXT, -- user, system, fallback, etc.
    
    -- Performance
    processing_time_seconds REAL,
    tokens_used INTEGER,
    api_calls_made INTEGER,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Contraintes
    FOREIGN KEY (project_id) REFERENCES translation_projects(id) ON DELETE CASCADE,
    UNIQUE(project_id, version_number)
);

-- Table de cache intelligent pour optimisation coûts
CREATE TABLE IF NOT EXISTS translation_cache (
    id TEXT PRIMARY KEY,
    cache_key TEXT UNIQUE NOT NULL, -- Hash des segments + config + langue
    
    -- Données mises en cache
    source_text_hash TEXT NOT NULL, -- Hash du texte source
    target_language TEXT NOT NULL,
    provider_used TEXT NOT NULL,
    config_hash TEXT NOT NULL, -- Hash de la configuration
    
    -- Résultat mis en cache
    translated_segments TEXT NOT NULL, -- JSON des segments traduits
    metadata_json TEXT, -- Métadonnées additionnelles
    
    -- Métriques de cache
    hit_count INTEGER DEFAULT 0,
    last_hit_at DATETIME,
    quality_score REAL,
    
    -- Gestion TTL et invalidation
    expires_at DATETIME NOT NULL,
    is_valid BOOLEAN DEFAULT 1,
    invalidation_reason TEXT,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table pour analytics et métriques utilisateur
CREATE TABLE IF NOT EXISTS translation_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    project_id TEXT,
    
    -- Métriques événement
    event_type TEXT NOT NULL, -- project_created, translation_started, translation_completed, cache_hit, etc.
    event_data TEXT, -- JSON avec détails spécifiques
    
    -- Contexte
    provider_used TEXT,
    target_language TEXT,
    source_language TEXT,
    
    -- Métriques performance
    processing_time_seconds REAL,
    cost_usd REAL,
    quality_score REAL,
    
    -- Métadonnées
    user_agent TEXT,
    ip_address TEXT,
    session_id TEXT,
    
    -- Timestamp
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Contraintes
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES translation_projects(id) ON DELETE SET NULL
);

-- Table pour gestion des erreurs et debugging
CREATE TABLE IF NOT EXISTS translation_errors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id TEXT,
    user_id INTEGER,
    
    -- Détails erreur
    error_type TEXT NOT NULL, -- api_error, validation_error, timeout, etc.
    error_code TEXT,
    error_message TEXT NOT NULL,
    error_context TEXT, -- JSON avec contexte détaillé
    
    -- Stack trace et debugging
    stack_trace TEXT,
    provider_response TEXT, -- Réponse brute du provider si disponible
    request_data TEXT, -- Données de la requête qui a échoué
    
    -- Résolution
    is_resolved BOOLEAN DEFAULT 0,
    resolution_notes TEXT,
    resolved_at DATETIME,
    resolved_by INTEGER, -- user_id qui a résolu
    
    -- Timestamp
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Contraintes
    FOREIGN KEY (project_id) REFERENCES translation_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Index pour performance optimale
CREATE INDEX IF NOT EXISTS idx_translation_projects_user_id ON translation_projects(user_id);
CREATE INDEX IF NOT EXISTS idx_translation_projects_status ON translation_projects(status);
CREATE INDEX IF NOT EXISTS idx_translation_projects_target_language ON translation_projects(target_language);
CREATE INDEX IF NOT EXISTS idx_translation_projects_created_at ON translation_projects(created_at);
CREATE INDEX IF NOT EXISTS idx_translation_projects_transcription_id ON translation_projects(transcription_id);

CREATE INDEX IF NOT EXISTS idx_translation_versions_project_id ON translation_versions(project_id);
CREATE INDEX IF NOT EXISTS idx_translation_versions_is_active ON translation_versions(is_active);

CREATE INDEX IF NOT EXISTS idx_translation_cache_cache_key ON translation_cache(cache_key);
CREATE INDEX IF NOT EXISTS idx_translation_cache_expires_at ON translation_cache(expires_at);
CREATE INDEX IF NOT EXISTS idx_translation_cache_target_language ON translation_cache(target_language);
CREATE INDEX IF NOT EXISTS idx_translation_cache_provider_used ON translation_cache(provider_used);

CREATE INDEX IF NOT EXISTS idx_translation_analytics_user_id ON translation_analytics(user_id);
CREATE INDEX IF NOT EXISTS idx_translation_analytics_event_type ON translation_analytics(event_type);
CREATE INDEX IF NOT EXISTS idx_translation_analytics_created_at ON translation_analytics(created_at);

CREATE INDEX IF NOT EXISTS idx_translation_errors_project_id ON translation_errors(project_id);
CREATE INDEX IF NOT EXISTS idx_translation_errors_is_resolved ON translation_errors(is_resolved);
CREATE INDEX IF NOT EXISTS idx_translation_errors_error_type ON translation_errors(error_type);

-- Vues utiles pour analytics et reporting
CREATE VIEW IF NOT EXISTS v_translation_summary AS
SELECT 
    tp.id,
    tp.user_id,
    tp.transcription_id,
    tp.target_language,
    tp.source_language,
    tp.provider_used,
    tp.status,
    tp.quality_score,
    tp.processing_time_seconds,
    tp.actual_cost,
    tp.segments_count,
    tp.total_duration_seconds,
    tp.created_at,
    tp.completed_at,
    tv.version_number as active_version,
    tv.timestamp_preservation_score,
    tv.length_adaptation_ratio,
    tv.emotional_preservation_score,
    -- Calculer temps total de traitement
    CASE 
        WHEN tp.completed_at IS NOT NULL AND tp.started_at IS NOT NULL 
        THEN (julianday(tp.completed_at) - julianday(tp.started_at)) * 24 * 3600
        ELSE NULL 
    END as total_processing_seconds,
    -- Calculer coût par minute
    CASE 
        WHEN tp.total_duration_seconds > 0 AND tp.actual_cost > 0
        THEN tp.actual_cost / (tp.total_duration_seconds / 60.0)
        ELSE NULL 
    END as cost_per_minute
FROM translation_projects tp
LEFT JOIN translation_versions tv ON tp.id = tv.project_id AND tv.is_active = 1;

-- Vue pour statistiques utilisateur
CREATE VIEW IF NOT EXISTS v_user_translation_stats AS
SELECT 
    user_id,
    COUNT(*) as total_projects,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_projects,
    COUNT(CASE WHEN status IN ('pending', 'processing') THEN 1 END) as active_projects,
    ROUND(AVG(quality_score), 3) as avg_quality_score,
    ROUND(SUM(actual_cost), 4) as total_cost_usd,
    ROUND(SUM(total_duration_seconds), 2) as total_audio_seconds,
    COUNT(DISTINCT target_language) as languages_used,
    COUNT(DISTINCT provider_used) as providers_used,
    MIN(created_at) as first_translation,
    MAX(created_at) as last_translation
FROM translation_projects 
GROUP BY user_id;