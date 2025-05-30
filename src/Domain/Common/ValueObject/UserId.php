<?php

namespace Domain\Common\ValueObject;

final class UserId extends Identifier
{
    public static function fromString(string $value): static
    {
        return new self($value);
    }
    
    public static function fromInt(int $value): static
    {
        return new self((string) $value);
    }
    
    public static function generate(): static
    {
        return new self(uniqid('user_', true));
    }
    
    public function toInt(): int
    {
        return (int) $this->value;
    }
}