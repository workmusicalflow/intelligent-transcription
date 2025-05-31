<?php

namespace Infrastructure\Http\Api\v2\Middleware;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;
use Infrastructure\Container\ServiceLocator;

/**
 * Middleware de limitation de débit
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private int $requestsPerMinute = 60;
    private int $requestsPerHour = 1000;
    
    public function handle(ApiRequest $request): ?ApiResponse
    {
        // Identifier le client
        $clientId = $this->getClientIdentifier($request);
        
        // Vérifier les limites
        if (!$this->checkRateLimit($clientId, 'minute', $this->requestsPerMinute)) {
            return ApiResponse::tooManyRequests(60);
        }
        
        if (!$this->checkRateLimit($clientId, 'hour', $this->requestsPerHour)) {
            return ApiResponse::tooManyRequests(3600);
        }
        
        return null;
    }
    
    /**
     * Identifie le client
     */
    private function getClientIdentifier(ApiRequest $request): string
    {
        // Priorité : utilisateur authentifié > IP
        if ($userId = $request->getUserId()) {
            return "user:$userId";
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Vérifier les headers de proxy
        if ($forwarded = $request->getHeader('X-Forwarded-For')) {
            $ips = explode(',', $forwarded);
            $ip = trim($ips[0]);
        }
        
        return "ip:$ip";
    }
    
    /**
     * Vérifie la limite de débit
     */
    private function checkRateLimit(string $clientId, string $window, int $limit): bool
    {
        try {
            $cache = ServiceLocator::getCache();
            $key = "rate_limit:{$window}:{$clientId}";
            
            // Récupérer le compteur actuel
            $count = $cache->get($key) ?? 0;
            
            if ($count >= $limit) {
                return false;
            }
            
            // Incrémenter le compteur
            $count++;
            $ttl = $window === 'minute' ? 60 : 3600;
            $cache->set($key, $count, $ttl);
            
            return true;
            
        } catch (\Exception $e) {
            // En cas d'erreur du cache, autoriser la requête
            error_log("Rate limit error: " . $e->getMessage());
            return true;
        }
    }
}