<?php

namespace Application\Service;

/**
 * Service de cache pour la couche Application
 */
class CacheService
{
    private array $cache = [];
    private array $expiry = [];
    
    public function get(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }
        
        // Vérifier l'expiration
        if (isset($this->expiry[$key]) && time() > $this->expiry[$key]) {
            unset($this->cache[$key], $this->expiry[$key]);
            return null;
        }
        
        return $this->cache[$key];
    }
    
    public function set(string $key, mixed $value, int $ttl = 300): void
    {
        $this->cache[$key] = $value;
        $this->expiry[$key] = time() + $ttl;
    }
    
    public function delete(string $key): bool
    {
        $existed = isset($this->cache[$key]);
        unset($this->cache[$key], $this->expiry[$key]);
        return $existed;
    }
    
    public function clear(string $pattern = ''): int
    {
        if (empty($pattern)) {
            $count = count($this->cache);
            $this->cache = [];
            $this->expiry = [];
            return $count;
        }
        
        $count = 0;
        foreach (array_keys($this->cache) as $key) {
            if (str_contains($key, $pattern)) {
                unset($this->cache[$key], $this->expiry[$key]);
                $count++;
            }
        }
        
        return $count;
    }
    
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    
    public function getStats(): array
    {
        // Nettoyer les clés expirées
        $now = time();
        foreach ($this->expiry as $key => $expiry) {
            if ($now > $expiry) {
                unset($this->cache[$key], $this->expiry[$key]);
            }
        }
        
        return [
            'total_keys' => count($this->cache),
            'memory_usage' => memory_get_usage(),
            'hit_ratio' => 'N/A' // Pour une vraie implémentation
        ];
    }
}