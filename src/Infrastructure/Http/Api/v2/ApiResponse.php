<?php

namespace Infrastructure\Http\Api\v2;

/**
 * Représente une réponse API
 */
class ApiResponse
{
    private array $data;
    private int $statusCode;
    private array $headers;
    
    public function __construct(array $data = [], int $statusCode = 200, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = array_merge([
            'Content-Type' => 'application/json',
            'X-API-Version' => '2.0'
        ], $headers);
    }
    
    public function getData(): array
    {
        return $this->data;
    }
    
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    // Méthodes factory pour les réponses communes
    
    public static function success($data = null, string $message = 'Success'): self
    {
        return new self([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }
    
    public static function created($data = null, string $location = null): self
    {
        $response = new self([
            'success' => true,
            'message' => 'Resource created',
            'data' => $data
        ], 201);
        
        if ($location) {
            $response->setHeader('Location', $location);
        }
        
        return $response;
    }
    
    public static function noContent(): self
    {
        return new self([], 204);
    }
    
    public static function badRequest(string $message, array $errors = []): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 400);
    }
    
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self([
            'success' => false,
            'message' => $message
        ], 401);
    }
    
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self([
            'success' => false,
            'message' => $message
        ], 403);
    }
    
    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self([
            'success' => false,
            'message' => $message
        ], 404);
    }
    
    public static function conflict(string $message): self
    {
        return new self([
            'success' => false,
            'message' => $message
        ], 409);
    }
    
    public static function tooManyRequests(int $retryAfter = 60): self
    {
        $response = new self([
            'success' => false,
            'message' => 'Too many requests'
        ], 429);
        
        $response->setHeader('Retry-After', (string)$retryAfter);
        
        return $response;
    }
    
    public static function serverError(string $message = 'Internal server error'): self
    {
        return new self([
            'success' => false,
            'message' => $message
        ], 500);
    }
    
    public static function paginated(array $items, int $total, int $page, int $perPage): self
    {
        $totalPages = ceil($total / $perPage);
        
        return new self([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]);
    }
    
    public static function error(string $message, int $statusCode = 400, array $data = []): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
}