<?php

namespace Infrastructure\Container;

use DI\ContainerBuilder;
use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use function DI\factory;
use function DI\get;
use function DI\autowire;

// Domain Interfaces
use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Transcription\Service\TranscriberInterface;
use Domain\Analytics\Service\SummarizerInterface;
use Domain\EventSourcing\EventDispatcherInterface;
use Domain\EventSourcing\EventStoreInterface;

// Application Services
use Application\Transcription\Handler\CreateTranscriptionHandler;
use Application\Transcription\Handler\ProcessTranscriptionHandler;
use Application\Analytics\Handler\GenerateSummaryHandler;
use Application\EventSourcing\EventDispatcher;

// Infrastructure Implementations
use Infrastructure\Repository\SQLite\SQLiteTranscriptionRepository;
use Infrastructure\External\OpenAI\WhisperAdapter;
use Infrastructure\External\OpenAI\GPTSummaryAdapter;
use Infrastructure\External\YouTube\YouTubeDownloader;
use Infrastructure\EventSourcing\SQLiteEventStore;
use Infrastructure\Persistence\SQLiteConnection;
use Infrastructure\Cache\MultiLevelCache;
use Infrastructure\Cache\PSRCacheAdapter;

/**
 * Configuration centralisée pour l'injection de dépendances
 */
class DIConfig
{
    /**
     * Configuration du conteneur DI
     */
    public static function getDefinitions(): array
    {
        return [
            // Configuration de base
            'database.path' => __DIR__ . '/../../../database.sqlite',
            'openai.api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
            'cache.enabled' => true,
            
            // Connexion à la base de données
            SQLiteConnection::class => \DI\factory(function (ContainerInterface $c) {
                return new SQLiteConnection($c->get('database.path'));
            }),
            
            // Cache multi-niveaux
            MultiLevelCache::class => \DI\factory(function (ContainerInterface $c) {
                return new MultiLevelCache(
                    $c->get(SQLiteConnection::class),
                    'cache_entries',  // tableName
                    1000             // maxMemoryItems
                );
            }),
            
            // Repositories (Infrastructure → Domain)
            TranscriptionRepository::class => \DI\get(SQLiteTranscriptionRepository::class),
            
            SQLiteTranscriptionRepository::class => \DI\factory(function (ContainerInterface $c) {
                return new SQLiteTranscriptionRepository(
                    $c->get(SQLiteConnection::class),
                    $c->get(MultiLevelCache::class)
                );
            }),
            
            // Services externes (Infrastructure → Domain)
            TranscriberInterface::class => \DI\get(WhisperAdapter::class),
            SummarizerInterface::class => \DI\get(GPTSummaryAdapter::class),
            
            WhisperAdapter::class => \DI\factory(function (ContainerInterface $c) {
                return new WhisperAdapter($c->get('openai.api_key'));
            }),
            
            GPTSummaryAdapter::class => \DI\factory(function (ContainerInterface $c) {
                return new GPTSummaryAdapter($c->get('openai.api_key'));
            }),
            
            // Event Sourcing
            EventStoreInterface::class => \DI\get(SQLiteEventStore::class),
            EventDispatcherInterface::class => \DI\get(EventDispatcher::class),
            
            SQLiteEventStore::class => \DI\factory(function (ContainerInterface $c) {
                return new SQLiteEventStore($c->get(SQLiteConnection::class));
            }),
            
            EventDispatcher::class => \DI\factory(function (ContainerInterface $c) {
                return new EventDispatcher();
            }),
            
            // YouTube Downloader
            YouTubeDownloader::class => \DI\factory(function (ContainerInterface $c) {
                return new YouTubeDownloader();
            }),
            
            // PSR Cache pour GraphQL
            \Psr\SimpleCache\CacheInterface::class => \DI\get(PSRCacheAdapter::class),
            
            PSRCacheAdapter::class => \DI\factory(function (ContainerInterface $c) {
                return new PSRCacheAdapter($c->get(MultiLevelCache::class));
            }),
            
            // Application Handlers (avec auto-wiring)
            CreateTranscriptionHandler::class => \DI\autowire(),
            ProcessTranscriptionHandler::class => \DI\autowire(),
            GenerateSummaryHandler::class => \DI\autowire(),
        ];
    }
    
    /**
     * Crée et configure le conteneur DI
     */
    public static function createContainer(): Container
    {
        $builder = new ContainerBuilder();
        
        // Configuration en mode développement
        if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] === 'development') {
            $builder->useAutowiring(true);
            $builder->useAttributes(true);
        }
        
        // Ajouter nos définitions
        $builder->addDefinitions(self::getDefinitions());
        
        // Construire le conteneur
        return $builder->build();
    }
}