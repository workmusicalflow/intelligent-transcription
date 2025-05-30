<?php

namespace Domain\Common\ValueObject;

/**
 * Value Object représentant l'ID d'un agrégat
 */
abstract class AggregateId
{
    protected string $value;
    
    protected function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Aggregate ID cannot be empty');
        }
        
        $this->value = $value;
    }
    
    public static function fromString(string $value): static
    {
        return new static($value);
    }
    
    public static function generate(): static
    {
        return new static(uniqid(static::prefix(), true));
    }
    
    public function value(): string
    {
        return $this->value;
    }
    
    public function equals(AggregateId $other): bool
    {
        return $this->value === $other->value && get_class($this) === get_class($other);
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
    
    /**
     * Prefix pour la génération d'ID (à surcharger dans les classes enfants)
     */
    protected static function prefix(): string
    {
        return 'aggregate_';
    }
}