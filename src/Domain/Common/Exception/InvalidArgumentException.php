<?php

namespace Domain\Common\Exception;

class InvalidArgumentException extends DomainException
{
    public static function forInvalidValue(string $field, $value, string $expectedFormat = null): self
    {
        $message = sprintf('Invalid value for %s: %s', $field, $value);
        
        if ($expectedFormat) {
            $message .= sprintf('. Expected format: %s', $expectedFormat);
        }
        
        return new self($message);
    }
    
    public static function forEmptyValue(string $field): self
    {
        return new self(sprintf('%s cannot be empty', $field));
    }
    
    public static function forTooLongValue(string $field, int $maxLength): self
    {
        return new self(sprintf('%s cannot exceed %d characters', $field, $maxLength));
    }
}