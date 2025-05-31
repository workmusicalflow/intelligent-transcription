<?php

namespace Infrastructure\GraphQL\Type;

use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * Type GraphQL pour User
 * 
 * @Type
 */
class UserType
{
    public function __construct(
        private string $id,
        private string $email,
        private ?string $name = null,
        private string $role = 'user'
    ) {}
    
    /**
     * @Field
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * @Field
     */
    public function getEmail(): string
    {
        return $this->email;
    }
    
    /**
     * @Field
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    
    /**
     * @Field
     */
    public function getRole(): string
    {
        return $this->role;
    }
    
    /**
     * @Field
     */
    public function getDisplayName(): string
    {
        return $this->name ?? $this->email;
    }
}