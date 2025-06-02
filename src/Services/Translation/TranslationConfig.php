<?php

declare(strict_types=1);

namespace App\Services\Translation;

/**
 * Configuration pour les services de traduction
 * Centralise tous les paramètres et options de traduction
 */
class TranslationConfig
{
    // Paramètres principaux
    public bool $optimize_for_dubbing;
    public bool $preserve_emotions;
    public bool $use_character_names;
    public bool $technical_terms_handling;
    public bool $length_optimization;
    
    // Style et qualité
    public string $style_adaptation;
    public float $quality_threshold;
    
    // Paramètres avancés
    public int $max_retries;
    public float $timeout_seconds;
    public bool $enable_cache;
    public string $fallback_strategy;
    
    // Métadonnées
    public ?string $context_hint;
    public ?string $domain_specialization;
    public array $custom_glossary;

    /**
     * Styles d'adaptation supportés
     */
    private const SUPPORTED_STYLES = [
        'cinematic',
        'educational', 
        'formal',
        'casual',
        'technical',
        'news'
    ];

    /**
     * Stratégies de fallback
     */
    private const FALLBACK_STRATEGIES = [
        'retry_same_provider',
        'switch_provider',
        'reduce_quality',
        'fail_fast'
    ];

    public function __construct(array $config = [])
    {
        // Valeurs par défaut optimisées
        $this->optimize_for_dubbing = $config['optimize_for_dubbing'] ?? true;
        $this->preserve_emotions = $config['preserve_emotions'] ?? true;
        $this->use_character_names = $config['use_character_names'] ?? false;
        $this->technical_terms_handling = $config['technical_terms_handling'] ?? true;
        $this->length_optimization = $config['length_optimization'] ?? true;
        
        // Style et qualité
        $this->style_adaptation = $config['style_adaptation'] ?? 'cinematic';
        $this->quality_threshold = $config['quality_threshold'] ?? 0.85;
        
        // Paramètres avancés
        $this->max_retries = $config['max_retries'] ?? 3;
        $this->timeout_seconds = $config['timeout_seconds'] ?? 30.0;
        $this->enable_cache = $config['enable_cache'] ?? true;
        $this->fallback_strategy = $config['fallback_strategy'] ?? 'switch_provider';
        
        // Métadonnées optionnelles
        $this->context_hint = $config['context_hint'] ?? null;
        $this->domain_specialization = $config['domain_specialization'] ?? null;
        $this->custom_glossary = $config['custom_glossary'] ?? [];

        $this->validateConfig();
    }

    /**
     * Valider la configuration
     * 
     * @throws \InvalidArgumentException Si la configuration est invalide
     */
    private function validateConfig(): void
    {
        // Validation du style
        if (!in_array($this->style_adaptation, self::SUPPORTED_STYLES)) {
            throw new \InvalidArgumentException(
                "Style d'adaptation '{$this->style_adaptation}' non supporté. " .
                "Styles supportés: " . implode(', ', self::SUPPORTED_STYLES)
            );
        }

        // Validation du seuil de qualité
        if ($this->quality_threshold < 0.0 || $this->quality_threshold > 1.0) {
            throw new \InvalidArgumentException(
                "Le seuil de qualité doit être entre 0.0 et 1.0, reçu: {$this->quality_threshold}"
            );
        }

        // Validation des tentatives
        if ($this->max_retries < 0 || $this->max_retries > 10) {
            throw new \InvalidArgumentException(
                "Le nombre de tentatives doit être entre 0 et 10, reçu: {$this->max_retries}"
            );
        }

        // Validation du timeout
        if ($this->timeout_seconds <= 0.0 || $this->timeout_seconds > 300.0) {
            throw new \InvalidArgumentException(
                "Le timeout doit être entre 0.1 et 300.0 secondes, reçu: {$this->timeout_seconds}"
            );
        }

        // Validation de la stratégie de fallback
        if (!in_array($this->fallback_strategy, self::FALLBACK_STRATEGIES)) {
            throw new \InvalidArgumentException(
                "Stratégie de fallback '{$this->fallback_strategy}' non supportée. " .
                "Stratégies supportées: " . implode(', ', self::FALLBACK_STRATEGIES)
            );
        }
    }

    /**
     * Créer une configuration optimisée pour le doublage
     */
    public static function forDubbing(): self
    {
        return new self([
            'optimize_for_dubbing' => true,
            'preserve_emotions' => true,
            'use_character_names' => true,
            'technical_terms_handling' => true,
            'length_optimization' => true,
            'style_adaptation' => 'cinematic',
            'quality_threshold' => 0.90
        ]);
    }

    /**
     * Créer une configuration pour contenu éducatif
     */
    public static function forEducational(): self
    {
        return new self([
            'optimize_for_dubbing' => false,
            'preserve_emotions' => false,
            'use_character_names' => false,
            'technical_terms_handling' => true,
            'length_optimization' => false,
            'style_adaptation' => 'educational',
            'quality_threshold' => 0.85
        ]);
    }

    /**
     * Créer une configuration pour contenu technique
     */
    public static function forTechnical(): self
    {
        return new self([
            'optimize_for_dubbing' => false,
            'preserve_emotions' => false,
            'use_character_names' => false,
            'technical_terms_handling' => true,
            'length_optimization' => false,
            'style_adaptation' => 'technical',
            'quality_threshold' => 0.95
        ]);
    }

    /**
     * Créer une configuration rapide (qualité réduite)
     */
    public static function forQuick(): self
    {
        return new self([
            'optimize_for_dubbing' => false,
            'preserve_emotions' => false,
            'use_character_names' => false,
            'technical_terms_handling' => false,
            'length_optimization' => false,
            'style_adaptation' => 'casual',
            'quality_threshold' => 0.75,
            'max_retries' => 1,
            'timeout_seconds' => 10.0
        ]);
    }

    /**
     * Obtenir toutes les configurations sous forme de tableau
     */
    public function toArray(): array
    {
        return [
            'optimize_for_dubbing' => $this->optimize_for_dubbing,
            'preserve_emotions' => $this->preserve_emotions,
            'use_character_names' => $this->use_character_names,
            'technical_terms_handling' => $this->technical_terms_handling,
            'length_optimization' => $this->length_optimization,
            'style_adaptation' => $this->style_adaptation,
            'quality_threshold' => $this->quality_threshold,
            'max_retries' => $this->max_retries,
            'timeout_seconds' => $this->timeout_seconds,
            'enable_cache' => $this->enable_cache,
            'fallback_strategy' => $this->fallback_strategy,
            'context_hint' => $this->context_hint,
            'domain_specialization' => $this->domain_specialization,
            'custom_glossary' => $this->custom_glossary
        ];
    }

    /**
     * Générer une clé de cache unique basée sur la configuration
     */
    public function getCacheKey(): string
    {
        $configData = $this->toArray();
        
        // Exclure les métadonnées variables du cache
        unset($configData['timeout_seconds'], $configData['max_retries']);
        
        return 'translation_config_' . md5(json_encode($configData));
    }

    /**
     * Vérifier si cette configuration est optimisée pour des performances élevées
     */
    public function isHighPerformance(): bool
    {
        return !$this->optimize_for_dubbing && 
               !$this->preserve_emotions && 
               !$this->use_character_names &&
               $this->quality_threshold < 0.85;
    }

    /**
     * Vérifier si cette configuration est optimisée pour une qualité élevée
     */
    public function isHighQuality(): bool
    {
        return $this->optimize_for_dubbing && 
               $this->preserve_emotions && 
               $this->quality_threshold >= 0.90;
    }

    /**
     * Obtenir la configuration recommandée pour un type de contenu
     */
    public static function getRecommendedFor(string $contentType): self
    {
        return match ($contentType) {
            'film', 'movie', 'series' => self::forDubbing(),
            'education', 'tutorial', 'course' => self::forEducational(),
            'technical', 'documentation', 'manual' => self::forTechnical(),
            'social', 'quick', 'simple' => self::forQuick(),
            default => new self() // Configuration par défaut
        };
    }

    /**
     * Créer une copie avec des modifications
     */
    public function withChanges(array $changes): self
    {
        $currentConfig = $this->toArray();
        $newConfig = array_merge($currentConfig, $changes);
        
        return new self($newConfig);
    }

    /**
     * Serialiser pour stockage en base de données
     */
    public function serialize(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Désérialiser depuis la base de données
     */
    public static function unserialize(string $data): self
    {
        $config = json_decode($data, true);
        
        if ($config === null) {
            throw new \InvalidArgumentException("Données de configuration invalides");
        }
        
        return new self($config);
    }

    /**
     * Obtenir la description textuelle de la configuration
     */
    public function getDescription(): string
    {
        $features = [];
        
        if ($this->optimize_for_dubbing) $features[] = 'Doublage optimisé';
        if ($this->preserve_emotions) $features[] = 'Émotions préservées';
        if ($this->use_character_names) $features[] = 'Noms personnages';
        if ($this->technical_terms_handling) $features[] = 'Termes techniques';
        if ($this->length_optimization) $features[] = 'Longueur optimisée';
        
        $description = sprintf(
            "Style: %s, Qualité: %d%%, Features: %s",
            ucfirst($this->style_adaptation),
            round($this->quality_threshold * 100),
            implode(', ', $features) ?: 'Standard'
        );
        
        return $description;
    }

    /**
     * Obtenir les styles supportés
     */
    public static function getSupportedStyles(): array
    {
        return self::SUPPORTED_STYLES;
    }

    /**
     * Obtenir les stratégies de fallback supportées
     */
    public static function getSupportedFallbackStrategies(): array
    {
        return self::FALLBACK_STRATEGIES;
    }
}