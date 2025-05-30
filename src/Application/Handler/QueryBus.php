<?php

namespace Application\Handler;

use Application\Query\QueryInterface;

/**
 * Bus pour router les Queries vers les bons Handlers
 */
final class QueryBus
{
    /** @var QueryHandlerInterface[] */
    private array $handlers = [];
    
    /** @var array Cache des résultats de queries */
    private array $cache = [];
    
    /** @var bool Cache activé */
    private bool $cacheEnabled = true;
    
    public function __construct()
    {
        // Les handlers seront injectés via registerHandler()
    }
    
    /**
     * Enregistre un handler
     */
    public function registerHandler(QueryHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }
    
    /**
     * Exécute une query en la routant vers le bon handler
     */
    public function execute(QueryInterface $query): mixed
    {
        // Vérifier le cache si activé
        if ($this->cacheEnabled) {
            $cacheKey = $query->getCacheKey();
            if (isset($this->cache[$cacheKey])) {
                $this->logQueryExecution($query, 'cache_hit');
                return $this->cache[$cacheKey];
            }
        }
        
        $handler = $this->findHandler($query);
        
        if (!$handler) {
            throw new \RuntimeException('No handler found for query: ' . get_class($query));
        }
        
        try {
            // Log du début d'exécution
            $this->logQueryExecution($query, 'started');
            
            $result = $handler->handle($query);
            
            // Mettre en cache si activé
            if ($this->cacheEnabled) {
                $this->cache[$query->getCacheKey()] = $result;
            }
            
            // Log du succès
            $this->logQueryExecution($query, 'completed');
            
            return $result;
            
        } catch (\Exception $e) {
            // Log de l'erreur
            $this->logQueryExecution($query, 'failed', $e);
            throw $e;
        }
    }
    
    /**
     * Trouve le handler approprié pour une query
     */
    private function findHandler(QueryInterface $query): ?QueryHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($query)) {
                return $handler;
            }
        }
        
        return null;
    }
    
    /**
     * Vide le cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
    
    /**
     * Active/désactive le cache
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }
    
    /**
     * Log l'exécution d'une query
     */
    private function logQueryExecution(QueryInterface $query, string $status, ?\Exception $error = null): void
    {
        $logData = [
            'query_id' => $query->getQueryId(),
            'query_class' => get_class($query),
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'cache_key' => $query->getCacheKey()
        ];
        
        if ($error) {
            $logData['error'] = $error->getMessage();
        }
        
        // Pour l'instant, on log dans le fichier d'erreur PHP
        // Dans une vraie implémentation, utiliser un logger approprié
        error_log('QueryBus: ' . json_encode($logData));
    }
    
    /**
     * Retourne la liste des handlers enregistrés
     */
    public function getRegisteredHandlers(): array
    {
        return $this->handlers;
    }
    
    /**
     * Retourne les statistiques du cache
     */
    public function getCacheStats(): array
    {
        return [
            'enabled' => $this->cacheEnabled,
            'entries' => count($this->cache),
            'keys' => array_keys($this->cache)
        ];
    }
}