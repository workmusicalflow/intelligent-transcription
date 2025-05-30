<?php

namespace Domain\Common\Specification;

abstract class CompositeSpecification implements Specification
{
    public function and(Specification $other): Specification
    {
        return new AndSpecification($this, $other);
    }
    
    public function or(Specification $other): Specification
    {
        return new OrSpecification($this, $other);
    }
    
    public function not(): Specification
    {
        return new NotSpecification($this);
    }
}

/**
 * Spécification AND
 */
class AndSpecification extends CompositeSpecification
{
    private Specification $left;
    private Specification $right;
    
    public function __construct(Specification $left, Specification $right)
    {
        $this->left = $left;
        $this->right = $right;
    }
    
    public function isSatisfiedBy($candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate) && 
               $this->right->isSatisfiedBy($candidate);
    }
}

/**
 * Spécification OR
 */
class OrSpecification extends CompositeSpecification
{
    private Specification $left;
    private Specification $right;
    
    public function __construct(Specification $left, Specification $right)
    {
        $this->left = $left;
        $this->right = $right;
    }
    
    public function isSatisfiedBy($candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate) || 
               $this->right->isSatisfiedBy($candidate);
    }
}

/**
 * Spécification NOT
 */
class NotSpecification extends CompositeSpecification
{
    private Specification $wrapped;
    
    public function __construct(Specification $wrapped)
    {
        $this->wrapped = $wrapped;
    }
    
    public function isSatisfiedBy($candidate): bool
    {
        return !$this->wrapped->isSatisfiedBy($candidate);
    }
}