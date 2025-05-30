<?php

namespace Application\Service;

use Application\Handler\CommandBus;
use Application\Handler\QueryBus;
use Application\Command\Auth\AuthenticateUserCommand;
use Application\Query\Auth\GetUserQuery;
use Application\DTO\Auth\UserDTO;

/**
 * Service d'application pour la gestion des utilisateurs
 */
final class UserApplicationService
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus
    ) {}
    
    /**
     * Use Case: Authentifier un utilisateur
     */
    public function authenticateUser(
        string $username,
        string $password,
        bool $rememberMe = false,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $command = new AuthenticateUserCommand(
            username: $username,
            password: $password,
            rememberMe: $rememberMe,
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );
        
        $result = $this->commandBus->execute($command);
        
        // Log de l'événement de connexion
        $this->logAuthenticationEvent($result['user'], $ipAddress, $userAgent, true);
        
        return $result;
    }
    
    /**
     * Use Case: Obtenir les informations d'un utilisateur
     */
    public function getUserById(int $userId): ?UserDTO
    {
        $query = new GetUserQuery(userId: $userId);
        return $this->queryBus->execute($query);
    }
    
    /**
     * Use Case: Obtenir un utilisateur par nom d'utilisateur
     */
    public function getUserByUsername(string $username): ?UserDTO
    {
        $query = new GetUserQuery(username: $username);
        return $this->queryBus->execute($query);
    }
    
    /**
     * Use Case: Obtenir un utilisateur par email
     */
    public function getUserByEmail(string $email): ?UserDTO
    {
        $query = new GetUserQuery(email: $email);
        return $this->queryBus->execute($query);
    }
    
    /**
     * Use Case: Obtenir le profil complet d'un utilisateur avec ses statistiques
     */
    public function getUserProfile(int $userId): array
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            throw new \DomainException("User not found: {$userId}");
        }
        
        // Récupérer les statistiques de transcription de l'utilisateur
        $transcriptionStats = $this->getTranscriptionStatsForUser((string) $userId);
        
        // Récupérer l'activité récente
        $recentActivity = $this->getRecentUserActivity($userId);
        
        return [
            'user' => $user->toArray(),
            'statistics' => $transcriptionStats,
            'recent_activity' => $recentActivity,
            'preferences' => $this->getUserPreferences($userId),
            'subscription' => $this->getUserSubscription($userId)
        ];
    }
    
    /**
     * Use Case: Vérifier les permissions d'un utilisateur
     */
    public function checkUserPermissions(int $userId, string $permission): bool
    {
        $user = $this->getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        // Vérifier selon le rôle et les permissions spécifiques
        return match($permission) {
            'transcribe' => $user->isActive(),
            'admin_access' => $user->isAdmin(),
            'moderate_content' => $user->isModerator(),
            'priority_processing' => $this->hasSubscription($userId, 'premium'),
            'export_conversations' => $user->isActive(),
            'view_analytics' => $user->isModerator(),
            default => false
        };
    }
    
    /**
     * Use Case: Logout d'un utilisateur (nettoyage des sessions)
     */
    public function logoutUser(int $userId, ?string $sessionId = null): void
    {
        // Dans une vraie implémentation, invalider les tokens/sessions
        $this->invalidateUserSessions($userId, $sessionId);
        
        // Log de l'événement
        $user = $this->getUserById($userId);
        if ($user) {
            $this->logAuthenticationEvent($user, null, null, false);
        }
    }
    
    // Méthodes privées pour la logique métier
    
    private function logAuthenticationEvent(UserDTO $user, ?string $ipAddress, ?string $userAgent, bool $isLogin): void
    {
        $event = [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'event_type' => $isLogin ? 'login' : 'logout',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Dans une vraie implémentation, sauvegarder dans un log d'audit
        error_log('AuthEvent: ' . json_encode($event));
    }
    
    private function getTranscriptionStatsForUser(string $userId): array
    {
        // Utiliser le TranscriptionApplicationService pour obtenir les stats
        // Pour simplifier, on simule ici
        return [
            'total_transcriptions' => 25,
            'completed_transcriptions' => 22,
            'failed_transcriptions' => 2,
            'pending_transcriptions' => 1,
            'total_duration_minutes' => 1850.5,
            'total_words_transcribed' => 45000,
            'youtube_transcriptions' => 8,
            'file_transcriptions' => 17,
            'languages_used' => ['fr', 'en', 'es'],
            'current_month_usage' => [
                'transcriptions' => 5,
                'duration_minutes' => 320.5
            ]
        ];
    }
    
    private function getRecentUserActivity(int $userId): array
    {
        // Simuler l'activité récente de l'utilisateur
        return [
            [
                'type' => 'transcription_completed',
                'description' => 'Transcription de "meeting_notes.mp3" terminée',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'resource_id' => 'trans_123'
            ],
            [
                'type' => 'chat_conversation',
                'description' => 'Conversation avec IA sur la transcription "interview.mp4"',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'resource_id' => 'conv_456'
            ],
            [
                'type' => 'transcription_started',
                'description' => 'Nouvelle transcription YouTube démarrée',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'resource_id' => 'trans_789'
            ]
        ];
    }
    
    private function getUserPreferences(int $userId): array
    {
        // Simuler les préférences utilisateur
        return [
            'default_language' => 'fr',
            'auto_start_processing' => true,
            'email_notifications' => true,
            'preferred_export_format' => 'json',
            'ui_theme' => 'light',
            'timezone' => 'Europe/Paris'
        ];
    }
    
    private function getUserSubscription(int $userId): array
    {
        // Simuler les informations d'abonnement
        return [
            'plan' => 'premium',
            'status' => 'active',
            'expires_at' => date('Y-m-d', strtotime('+30 days')),
            'features' => [
                'priority_processing',
                'unlimited_transcriptions',
                'advanced_ai_chat',
                'pdf_export'
            ],
            'usage_limits' => [
                'monthly_minutes' => 1000,
                'used_minutes' => 320,
                'remaining_minutes' => 680
            ]
        ];
    }
    
    private function hasSubscription(int $userId, string $planType): bool
    {
        $subscription = $this->getUserSubscription($userId);
        return $subscription['plan'] === $planType && $subscription['status'] === 'active';
    }
    
    private function invalidateUserSessions(int $userId, ?string $sessionId): void
    {
        // Dans une vraie implémentation :
        // - Invalider les tokens JWT
        // - Supprimer les sessions de la base/cache
        // - Notifier les autres services
        
        if ($sessionId) {
            error_log("Session invalidated: {$sessionId} for user {$userId}");
        } else {
            error_log("All sessions invalidated for user {$userId}");
        }
    }
}