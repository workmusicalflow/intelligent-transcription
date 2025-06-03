-- Migration pour ajouter le flag immediate_processing
-- Date: 2025-06-03 18:00:00

-- Ajouter la colonne immediate_processing à la table translation_projects
ALTER TABLE translation_projects 
ADD COLUMN immediate_processing BOOLEAN DEFAULT 0;

-- Créer un index pour optimiser les requêtes sur ce flag
CREATE INDEX IF NOT EXISTS idx_translation_projects_immediate_processing 
ON translation_projects(immediate_processing);

-- Commentaire pour la documentation
-- Cette colonne permet de distinguer les traductions lancées en mode immédiat
-- versus celles traitées par le système cron en arrière-plan