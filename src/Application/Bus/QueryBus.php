<?php

namespace Application\Bus;

use Application\Query\QueryInterface;
use Application\Handler\HandlerInterface;
use Application\Service\CacheService;
use Exception;

/**
 * Query Bus - Pattern CQRS pour l'exécution des requêtes avec cache
 */
class QueryBus
{
    private array $handlers = [];
    private bool $enableLogging = true;
    private bool $enableCaching = true;
    private ?CacheService $cache = null;
    private int $defaultCacheTTL = 300; // 5 minutes
    
    public function __construct(?CacheService $cache = null)
    {
        $this->cache = $cache ?? new CacheService();
    }
    
    public function registerHandler(string $queryClass, callable $handler): void
    {
        $this->handlers[$queryClass] = $handler;
        
        if ($this->enableLogging) {
            $this->log('handler_registered', $queryClass, gettype($handler));
        }
    }
    
    public function dispatch($query): mixed
    {
        $queryClass = get_class($query);
        $queryId = 'qry_' . uniqid();
        $cacheKey = $this->generateCacheKey($query);
        
        if ($this->enableLogging) {
            $this->log('started', $queryClass, null, $queryId, $cacheKey);
        }
        
        // Vérifier le cache si activé
        if ($this->enableCaching && $this->cache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                if ($this->enableLogging) {
                    $this->log('cache_hit', $queryClass, null, $queryId, $cacheKey);
                }
                return $cached;
            }
        }
        
        if (!isset($this->handlers[$queryClass])) {
            throw new Exception("No handler registered for query: {$queryClass}");
        }
        
        $handler = $this->handlers[$queryClass];
        
        try {
            $result = $handler($query);
            
            // Mettre en cache le résultat
            if ($this->enableCaching && $this->cache && $result !== null) {
                $this->cache->set($cacheKey, $result, $this->defaultCacheTTL);
            }
            
            if ($this->enableLogging) {
                $this->log('completed', $queryClass, null, $queryId, $cacheKey);
            }
            
            return $result;
        } catch (Exception $e) {
            if ($this->enableLogging) {
                $this->log('failed', $queryClass, $e->getMessage(), $queryId, $cacheKey);
            }
            throw $e;
        }
    }
    
    public function hasHandler(string $queryClass): bool
    {
        return isset($this->handlers[$queryClass]);
    }
    
    public function getRegisteredQueries(): array
    {
        return array_keys($this->handlers);
    }
    
    public function setCaching(bool $enabled): void
    {
        $this->enableCaching = $enabled;
    }
    
    public function setLogging(bool $enabled): void
    {
        $this->enableLogging = $enabled;
    }
    
    public function setCacheTTL(int $seconds): void
    {
        $this->defaultCacheTTL = $seconds;
    }
    
    public function clearCache(string $pattern = ''): int
    {
        if (!$this->cache) {
            return 0;
        }
        
        return $this->cache->clear($pattern);
    }
    
    private function generateCacheKey($query): string
    {
        return get_class($query) . ':' . md5(serialize($query));
    }
    
    private function log(string $status, string $queryClass, ?string $extra = null, ?string $queryId = null, ?string $cacheKey = null): void
    {
        $logData = [
            'query_class' => $queryClass,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($queryId) {
            $logData['query_id'] = $queryId;
        }
        
        if ($cacheKey) {
            $logData['cache_key'] = $cacheKey;
        }
        
        if ($extra) {
            $logData['extra'] = $extra;
        }
        
        error_log("QueryBus: " . json_encode($logData));
    }
}