<?php

namespace Infrastructure\Container;

use DI\Container;
use Psr\Container\ContainerInterface;

/**
 * Service Locator pour accéder facilement aux services depuis l'ancien code
 * 
 * Cette classe facilite la migration progressive vers DI en fournissant
 * un point d'accès global aux services configurés.
 */
class ServiceLocator
{
    private static ?Container $container = null;
    
    /**
     * Initialise le conteneur DI
     */
    public static function init(): void
    {
        if (self::$container === null) {
            self::$container = DIConfig::createContainer();
        }
    }
    
    /**
     * Récupère le conteneur DI
     */
    public static function getContainer(): ContainerInterface
    {
        if (self::$container === null) {
            self::init();
        }
        
        return self::$container;
    }
    
    /**
     * Récupère un service par son nom de classe ou son alias
     */
    public static function get(string $serviceId)
    {
        return self::getContainer()->get($serviceId);
    }
    
    /**
     * Vérifie si un service existe
     */
    public static function has(string $serviceId): bool
    {
        return self::getContainer()->has($serviceId);
    }
    
    /**
     * Réinitialise le conteneur (utile pour les tests)
     */
    public static function reset(): void
    {
        self::$container = null;
    }
    
    // Méthodes de raccourci pour les services les plus utilisés
    
    /**
     * Raccourci pour récupérer le repository de transcriptions
     */
    public static function getTranscriptionRepository()
    {
        return self::get(\Domain\Transcription\Repository\TranscriptionRepository::class);
    }
    
    /**
     * Raccourci pour récupérer le service de transcription
     */
    public static function getTranscriber()
    {
        return self::get(\Domain\Transcription\Service\TranscriberInterface::class);
    }
    
    /**
     * Raccourci pour récupérer le service de résumé
     */
    public static function getSummarizer()
    {
        return self::get(\Domain\Analytics\Service\SummarizerInterface::class);
    }
    
    /**
     * Raccourci pour récupérer le cache
     */
    public static function getCache()
    {
        return self::get(\Infrastructure\Cache\MultiLevelCache::class);
    }
    
    /**
     * Raccourci pour récupérer la connexion base de données
     */
    public static function getDatabase()
    {
        return self::get(\Infrastructure\Persistence\SQLiteConnection::class);
    }
    
    /**
     * Raccourci pour récupérer l'event dispatcher
     */
    public static function getEventDispatcher()
    {
        return self::get(\Domain\EventSourcing\EventDispatcherInterface::class);
    }
}