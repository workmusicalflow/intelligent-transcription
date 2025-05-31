<?php

namespace Infrastructure\Http\Api\v2\Middleware;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Middleware d'authentification JWT
 */
class AuthMiddleware implements MiddlewareInterface
{
    private string $jwtSecret;
    private string $jwtAlgorithm = 'HS256';
    
    public function __construct()
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this-in-production';
    }
    
    public function handle(ApiRequest $request): ?ApiResponse
    {
        $token = $request->getBearerToken();
        
        if (!$token) {
            return ApiResponse::unauthorized('No authentication token provided');
        }
        
        try {
            // Décoder le token JWT
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            
            // Vérifier l'expiration
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return ApiResponse::unauthorized('Token expired');
            }
            
            // Ajouter les informations utilisateur à la requête
            $request->setUser([
                'id' => $decoded->sub ?? null,
                'email' => $decoded->email ?? null,
                'role' => $decoded->role ?? 'user',
                'permissions' => $decoded->permissions ?? []
            ]);
            
            // Continuer vers le handler
            return null;
            
        } catch (\Exception $e) {
            error_log("JWT Error: " . $e->getMessage());
            return ApiResponse::unauthorized('Invalid authentication token');
        }
    }
    
    /**
     * Génère un token JWT
     */
    public static function generateToken(array $user, int $expiresIn = 3600): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this-in-production';
        
        $payload = [
            'iss' => 'intelligent-transcription',
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user',
            'permissions' => $user['permissions'] ?? [],
            'iat' => time(),
            'exp' => time() + $expiresIn
        ];
        
        return JWT::encode($payload, $secret, 'HS256');
    }
    
    /**
     * Génère un refresh token
     */
    public static function generateRefreshToken(string $userId): string
    {
        return bin2hex(random_bytes(32));
    }
}