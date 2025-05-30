<?php

namespace Application\DTO;

/**
 * Classe de base abstraite pour tous les DTOs
 * 
 * Fournit une implémentation de base des méthodes communes aux DTOs.
 */
abstract class AbstractDTO implements DTOInterface
{
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
    
    public function validate(): void
    {
        // Validation de base - peut être surchargée dans les classes filles
        $data = $this->toArray();
        if (empty($data)) {
            throw new \InvalidArgumentException('DTO cannot be empty');
        }
    }
    
    /**
     * Validation d'un champ requis
     * 
     * @param string $field
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    protected function validateRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '') {
            throw new \InvalidArgumentException("Field '{$field}' is required");
        }
    }
    
    /**
     * Validation d'un email
     * 
     * @param string $email
     * @throws \InvalidArgumentException
     */
    protected function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$email}");
        }
    }
    
    /**
     * Validation d'un entier positif
     * 
     * @param int $value
     * @param string $field
     * @throws \InvalidArgumentException
     */
    protected function validatePositiveInteger(int $value, string $field): void
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException("Field '{$field}' must be a positive integer");
        }
    }
    
    /**
     * Validation d'une date
     * 
     * @param string $date
     * @param string $field
     * @throws \InvalidArgumentException
     */
    protected function validateDate(string $date, string $field): void
    {
        if (!strtotime($date)) {
            throw new \InvalidArgumentException("Field '{$field}' must be a valid date");
        }
    }
}