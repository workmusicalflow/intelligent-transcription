<?php

namespace Application\Handler\Query;

use Application\Handler\QueryHandlerInterface;
use Application\Query\QueryInterface;
use Application\Query\Auth\GetUserQuery;
use Application\DTO\Auth\UserDTO;

/**
 * Handler pour les queries d'authentification
 */
final class AuthQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly mixed $authService // Service existant temporairement - accepte tout pour les tests
    ) {}
    
    public function handle(QueryInterface $query): mixed
    {
        return match (get_class($query)) {
            GetUserQuery::class => $this->handleGetUser($query),
            default => throw new \InvalidArgumentException('Unsupported query: ' . get_class($query))
        };
    }
    
    public function canHandle(QueryInterface $query): bool
    {
        return match (get_class($query)) {
            GetUserQuery::class => true,
            default => false
        };
    }
    
    private function handleGetUser(GetUserQuery $query): ?UserDTO
    {
        $user = null;
        
        // Récupérer selon le type de recherche
        switch ($query->getSearchType()) {
            case 'id':
                $user = $this->authService->getUserById($query->getUserId());
                break;
            case 'username':
                $user = $this->authService->getUserByUsername($query->getUsername());
                break;
            case 'email':
                $user = $this->authService->getUserByEmail($query->getEmail());
                break;
        }
        
        if (!$user) {
            return null;
        }
        
        // Ajouter last_login si demandé
        $userData = $user;
        if ($query->shouldIncludeLastLogin()) {
            $userData['last_login_at'] = $this->authService->getLastLoginTime($user['id']);
        }
        
        return UserDTO::fromArray($userData);
    }
}