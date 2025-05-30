<?php

namespace Domain\Common\Specification;

interface Specification
{
    /**
     * Vérifie si le candidat satisfait la spécification
     */
    public function isSatisfiedBy($candidate): bool;
    
    /**
     * Crée une nouvelle spécification qui est la conjonction de celle-ci et d'une autre
     */
    public function and(Specification $other): Specification;
    
    /**
     * Crée une nouvelle spécification qui est la disjonction de celle-ci et d'une autre
     */
    public function or(Specification $other): Specification;
    
    /**
     * Crée une nouvelle spécification qui est la négation de celle-ci
     */
    public function not(): Specification;
}