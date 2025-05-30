<?php

namespace Domain\Common\ValueObject;

abstract class ValueObject
{
    abstract public function equals(ValueObject $other): bool;
    
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }
    
    abstract public function toArray(): array;
    
    protected function sameValueAs(ValueObject $other): bool
    {
        return get_class($this) === get_class($other) && 
               $this->toArray() === $other->toArray();
    }
}