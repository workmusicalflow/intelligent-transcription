<?php

namespace Application\Command\Auth;

use Application\Command\AbstractCommand;

/**
 * Command pour authentifier un utilisateur
 */
final class AuthenticateUserCommand extends AbstractCommand
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly bool $rememberMe = false,
        private readonly ?string $ipAddress = null,
        private readonly ?string $userAgent = null
    ) {
        parent::__construct();
        $this->validate();
    }
    
    public function validate(): void
    {
        parent::validate();
        
        if (empty($this->username)) {
            throw new \InvalidArgumentException('Username is required');
        }
        
        if (empty($this->password)) {
            throw new \InvalidArgumentException('Password is required');
        }
        
        if (strlen($this->username) < 3) {
            throw new \InvalidArgumentException('Username must be at least 3 characters long');
        }
        
        if (strlen($this->password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters long');
        }
    }
    
    protected function getPayload(): array
    {
        return [
            'username' => $this->username,
            'password' => '[REDACTED]', // Ne jamais logger le mot de passe
            'remember_me' => $this->rememberMe,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent
        ];
    }
    
    // Getters
    public function getUsername(): string { return $this->username; }
    public function getPassword(): string { return $this->password; }
    public function shouldRememberMe(): bool { return $this->rememberMe; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getUserAgent(): ?string { return $this->userAgent; }
}