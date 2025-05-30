<?php

namespace Domain\Common\ValueObject;

use InvalidArgumentException;

abstract class Identifier extends ValueObject
{
    protected string $value;
    
    public function __construct(string $value)
    {
        $this->guardAgainstEmptyValue($value);
        $this->value = $value;
    }
    
    public static function generate(): static
    {
        return new static(uniqid('', true));
    }
    
    public static function fromString(string $value): static
    {
        return new static($value);
    }
    
    public function value(): string
    {
        return $this->value;
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }
    
    public function toArray(): array
    {
        return ['value' => $this->value];
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
    
    private function guardAgainstEmptyValue(string $value): void
    {
        if (empty(trim($value))) {
            throw new InvalidArgumentException('Identifier cannot be empty');
        }
    }
}