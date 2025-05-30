<?php

namespace Application\DTO\Auth;

use Application\DTO\AbstractDTO;

/**
 * DTO pour représenter un utilisateur
 */
final class UserDTO extends AbstractDTO
{
    public function __construct(
        private readonly int $id,
        private readonly string $username,
        private readonly string $email,
        private readonly string $role,
        private readonly bool $isActive,
        private readonly ?\DateTimeImmutable $createdAt = null,
        private readonly ?\DateTimeImmutable $lastLoginAt = null,
        private readonly ?string $firstName = null,
        private readonly ?string $lastName = null
    ) {
        $this->validate();
    }
    
    public static function fromArray(array $data): static
    {
        return new self(
            id: (int) ($data['id'] ?? throw new \InvalidArgumentException('Missing id')),
            username: $data['username'] ?? throw new \InvalidArgumentException('Missing username'),
            email: $data['email'] ?? throw new \InvalidArgumentException('Missing email'),
            role: $data['role'] ?? 'user',
            isActive: (bool) ($data['is_active'] ?? true),
            createdAt: isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null,
            lastLoginAt: isset($data['last_login_at']) ? new \DateTimeImmutable($data['last_login_at']) : null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null
        );
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'last_login_at' => $this->lastLoginAt?->format('Y-m-d H:i:s'),
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'is_admin' => $this->isAdmin()
        ];
    }
    
    public function validate(): void
    {
        parent::validate();
        
        $this->validatePositiveInteger($this->id, 'id');
        $this->validateRequired('username', $this->username);
        $this->validateRequired('email', $this->email);
        $this->validateEmail($this->email);
        $this->validateRequired('role', $this->role);
        
        // Validation du rôle
        $validRoles = ['admin', 'user', 'moderator'];
        if (!in_array($this->role, $validRoles)) {
            throw new \InvalidArgumentException("Invalid role: {$this->role}");
        }
        
        // Validation du nom d'utilisateur
        if (strlen($this->username) < 3) {
            throw new \InvalidArgumentException('Username must be at least 3 characters long');
        }
    }
    
    // Getters
    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getLastLoginAt(): ?\DateTimeImmutable { return $this->lastLoginAt; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    
    // Méthodes utilitaires
    public function getFullName(): string
    {
        $parts = array_filter([$this->firstName, $this->lastName]);
        return !empty($parts) ? implode(' ', $parts) : $this->username;
    }
    
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }
}