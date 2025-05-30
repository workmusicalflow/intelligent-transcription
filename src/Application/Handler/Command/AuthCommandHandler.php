<?php

namespace Application\Handler\Command;

use Application\Handler\CommandHandlerInterface;
use Application\Command\CommandInterface;
use Application\Command\Auth\AuthenticateUserCommand;
use Application\DTO\Auth\UserDTO;

/**
 * Handler pour les commandes d'authentification
 * 
 * Note: Pour cette implémentation, on utilise les services existants
 * En attendant l'Infrastructure Layer qui implémentera les interfaces Domain
 */
final class AuthCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly mixed $authService // Service existant temporairement - accepte tout pour les tests
    ) {}
    
    public function handle(CommandInterface $command): mixed
    {
        return match (get_class($command)) {
            AuthenticateUserCommand::class => $this->handleAuthenticate($command),
            default => throw new \InvalidArgumentException('Unsupported command: ' . get_class($command))
        };
    }
    
    public function canHandle(CommandInterface $command): bool
    {
        return match (get_class($command)) {
            AuthenticateUserCommand::class => true,
            default => false
        };
    }
    
    private function handleAuthenticate(AuthenticateUserCommand $command): array
    {
        // Utilisation temporaire du service existant
        $result = $this->authService->authenticate(
            $command->getUsername(),
            $command->getPassword()
        );
        
        if (!$result['success']) {
            throw new \DomainException($result['message'] ?? 'Authentication failed');
        }
        
        // Enregistrer les infos de connexion si nécessaire
        if ($command->getIpAddress() || $command->getUserAgent()) {
            // Log de connexion (à implémenter selon besoins)
        }
        
        // Convertir l'utilisateur en DTO
        $user = $result['user'];
        $userDTO = UserDTO::fromArray([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_active' => $user['is_active'] ?? true,
            'created_at' => $user['created_at'] ?? null,
            'last_login_at' => date('Y-m-d H:i:s'), // Maintenant
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null
        ]);
        
        return [
            'success' => true,
            'user' => $userDTO,
            'token' => $result['token'] ?? null,
            'remember_me' => $command->shouldRememberMe()
        ];
    }
}