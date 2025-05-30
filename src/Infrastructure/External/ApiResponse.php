<?php

namespace Infrastructure\External;

/**
 * Réponse d'API externe standardisée
 */
class ApiResponse
{
    public function __construct(
        private readonly int $statusCode,
        private readonly array $data,
        private readonly array $headers = [],
        private readonly ?string $error = null
    ) {}
    
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
    
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    public function getData(): array
    {
        return $this->data;
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    public function getError(): ?string
    {
        return $this->error;
    }
    
    public function hasError(): bool
    {
        return $this->error !== null;
    }
    
    public function toArray(): array
    {
        return [
            'status_code' => $this->statusCode,
            'data' => $this->data,
            'headers' => $this->headers,
            'error' => $this->error,
            'successful' => $this->isSuccessful()
        ];
    }
}