<?php

namespace Infrastructure\Http\Api\v2\Controller;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;
use Infrastructure\Http\Api\v2\Middleware\AuthMiddleware;
use Infrastructure\Container\ServiceLocator;

/**
 * Controller API pour l'authentification
 */
class AuthApiController extends BaseApiController
{
    /**
     * Connexion utilisateur
     */
    public function login(ApiRequest $request): ApiResponse
    {
        // Validation
        $validationResponse = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        
        if ($validationResponse) {
            return $validationResponse;
        }
        
        try {
            $authService = ServiceLocator::get(\Services\AuthService::class);
            
            // Vérifier les credentials
            $user = $authService->authenticate(
                $request->getBody('email'),
                $request->getBody('password')
            );
            
            if (!$user) {
                return ApiResponse::unauthorized('Invalid credentials');
            }
            
            // Générer les tokens
            $accessToken = AuthMiddleware::generateToken($user, 3600); // 1 heure
            $refreshToken = AuthMiddleware::generateRefreshToken($user['id']);
            
            // Sauvegarder le refresh token
            $this->saveRefreshToken($user['id'], $refreshToken);
            
            return ApiResponse::success([
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'] ?? null,
                    'role' => $user['role'] ?? 'user'
                ],
                'tokens' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ]
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Inscription utilisateur
     */
    public function register(ApiRequest $request): ApiResponse
    {
        // Validation
        $validationResponse = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'name' => 'required|min:2|max:100'
        ]);
        
        if ($validationResponse) {
            return $validationResponse;
        }
        
        try {
            $authService = ServiceLocator::get(\Services\AuthService::class);
            
            // Vérifier si l'email existe déjà
            if ($authService->emailExists($request->getBody('email'))) {
                return ApiResponse::conflict('Email already registered');
            }
            
            // Créer l'utilisateur
            $user = $authService->register([
                'email' => $request->getBody('email'),
                'password' => $request->getBody('password'),
                'name' => $request->getBody('name')
            ]);
            
            // Générer les tokens
            $accessToken = AuthMiddleware::generateToken($user, 3600);
            $refreshToken = AuthMiddleware::generateRefreshToken($user['id']);
            
            // Sauvegarder le refresh token
            $this->saveRefreshToken($user['id'], $refreshToken);
            
            return ApiResponse::created([
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ],
                'tokens' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ]
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Rafraîchir le token d'accès
     */
    public function refresh(ApiRequest $request): ApiResponse
    {
        // Validation
        $validationResponse = $this->validate($request, [
            'refresh_token' => 'required'
        ]);
        
        if ($validationResponse) {
            return $validationResponse;
        }
        
        try {
            $refreshToken = $request->getBody('refresh_token');
            
            // Vérifier le refresh token
            $userId = $this->verifyRefreshToken($refreshToken);
            
            if (!$userId) {
                return ApiResponse::unauthorized('Invalid refresh token');
            }
            
            // Récupérer l'utilisateur
            $authService = ServiceLocator::get(\Services\AuthService::class);
            $user = $authService->getUserById($userId);
            
            if (!$user) {
                return ApiResponse::unauthorized('User not found');
            }
            
            // Générer un nouveau access token
            $accessToken = AuthMiddleware::generateToken($user, 3600);
            
            // Optionnel : générer un nouveau refresh token
            $newRefreshToken = AuthMiddleware::generateRefreshToken($user['id']);
            $this->saveRefreshToken($user['id'], $newRefreshToken);
            $this->revokeRefreshToken($refreshToken);
            
            return ApiResponse::success([
                'tokens' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $newRefreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ]
            ]);
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Déconnexion
     */
    public function logout(ApiRequest $request): ApiResponse
    {
        try {
            $userId = $request->getUserId();
            
            if ($userId) {
                // Révoquer tous les refresh tokens de l'utilisateur
                $this->revokeAllUserRefreshTokens($userId);
            }
            
            return ApiResponse::success(['message' => 'Logged out successfully']);
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Sauvegarde un refresh token
     */
    private function saveRefreshToken(string $userId, string $token): void
    {
        $cache = ServiceLocator::getCache();
        $key = "refresh_token:$token";
        $cache->set($key, $userId, 7 * 24 * 3600); // 7 jours
        
        // Ajouter à la liste des tokens de l'utilisateur
        $userTokensKey = "user_refresh_tokens:$userId";
        $tokens = $cache->get($userTokensKey) ?? [];
        $tokens[] = $token;
        $cache->set($userTokensKey, $tokens, 7 * 24 * 3600);
    }
    
    /**
     * Vérifie un refresh token
     */
    private function verifyRefreshToken(string $token): ?string
    {
        $cache = ServiceLocator::getCache();
        $key = "refresh_token:$token";
        return $cache->get($key);
    }
    
    /**
     * Révoque un refresh token
     */
    private function revokeRefreshToken(string $token): void
    {
        $cache = ServiceLocator::getCache();
        $cache->delete("refresh_token:$token");
    }
    
    /**
     * Révoque tous les refresh tokens d'un utilisateur
     */
    private function revokeAllUserRefreshTokens(string $userId): void
    {
        $cache = ServiceLocator::getCache();
        $userTokensKey = "user_refresh_tokens:$userId";
        $tokens = $cache->get($userTokensKey) ?? [];
        
        foreach ($tokens as $token) {
            $this->revokeRefreshToken($token);
        }
        
        $cache->delete($userTokensKey);
    }
}