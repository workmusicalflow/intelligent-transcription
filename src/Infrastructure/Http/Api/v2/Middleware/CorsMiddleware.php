<?php

namespace Infrastructure\Http\Api\v2\Middleware;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;

/**
 * Middleware CORS pour l'API
 */
class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins = [];
    private array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
    private array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'];
    private int $maxAge = 86400; // 24 heures
    
    public function __construct()
    {
        // Charger les origines autorisées depuis la config
        $origins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*';
        $this->allowedOrigins = $origins === '*' ? ['*'] : explode(',', $origins);
    }
    
    public function handle(ApiRequest $request): ?ApiResponse
    {
        $origin = $request->getHeader('Origin');
        
        // Ajouter les headers CORS
        $this->setCorsHeaders($origin);
        
        // Gérer les requêtes preflight
        if ($request->getMethod() === 'OPTIONS') {
            return new ApiResponse([], 204);
        }
        
        return null;
    }
    
    /**
     * Configure les headers CORS
     */
    private function setCorsHeaders(?string $origin): void
    {
        if ($this->isOriginAllowed($origin)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
        } elseif (in_array('*', $this->allowedOrigins)) {
            header('Access-Control-Allow-Origin: *');
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));
        header('Access-Control-Max-Age: ' . $this->maxAge);
    }
    
    /**
     * Vérifie si une origine est autorisée
     */
    private function isOriginAllowed(?string $origin): bool
    {
        if (!$origin) {
            return false;
        }
        
        if (in_array('*', $this->allowedOrigins)) {
            return true;
        }
        
        foreach ($this->allowedOrigins as $allowed) {
            if ($allowed === $origin) {
                return true;
            }
            
            // Support des wildcards simples
            if (str_contains($allowed, '*')) {
                $pattern = str_replace('*', '.*', preg_quote($allowed, '/'));
                if (preg_match('/^' . $pattern . '$/', $origin)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}