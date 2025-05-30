<?php

namespace Application\Query\Auth;

use Application\Query\AbstractQuery;

/**
 * Query pour récupérer un utilisateur
 */
final class GetUserQuery extends AbstractQuery
{
    public function __construct(
        private readonly ?int $userId = null,
        private readonly ?string $username = null,
        private readonly ?string $email = null,
        private readonly bool $includeLastLogin = true
    ) {
        parent::__construct();
        $this->validate();
    }
    
    public function validate(): void
    {
        parent::validate();
        
        $criteria = array_filter([
            $this->userId,
            $this->username,
            $this->email
        ], fn($value) => $value !== null);
        
        if (empty($criteria)) {
            throw new \InvalidArgumentException('At least one search criteria (userId, username, or email) is required');
        }
        
        if (count($criteria) > 1) {
            throw new \InvalidArgumentException('Only one search criteria should be provided');
        }
        
        if ($this->userId !== null && $this->userId <= 0) {
            throw new \InvalidArgumentException('User ID must be positive');
        }
        
        if ($this->email !== null && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
    }
    
    protected function getParameters(): array
    {
        return [
            'user_id' => $this->userId,
            'username' => $this->username,
            'email' => $this->email,
            'include_last_login' => $this->includeLastLogin
        ];
    }
    
    // Getters
    public function getUserId(): ?int { return $this->userId; }
    public function getUsername(): ?string { return $this->username; }
    public function getEmail(): ?string { return $this->email; }
    public function shouldIncludeLastLogin(): bool { return $this->includeLastLogin; }
    
    // Méthodes utilitaires
    public function getSearchType(): string
    {
        if ($this->userId !== null) return 'id';
        if ($this->username !== null) return 'username';
        if ($this->email !== null) return 'email';
        
        throw new \LogicException('No search criteria defined');
    }
    
    public function getSearchValue(): string|int
    {
        return match($this->getSearchType()) {
            'id' => $this->userId,
            'username' => $this->username,
            'email' => $this->email
        };
    }
}