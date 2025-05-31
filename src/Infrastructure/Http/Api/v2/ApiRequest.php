<?php

namespace Infrastructure\Http\Api\v2;

/**
 * Représente une requête API
 */
class ApiRequest
{
    private string $method;
    private string $uri;
    private array $params;
    private array $query;
    private array $body;
    private array $headers;
    private ?array $user = null;
    
    public function __construct(
        string $method,
        string $uri,
        array $params = [],
        array $query = [],
        array $body = [],
        array $headers = []
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->params = $params;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
    }
    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    public function getUri(): string
    {
        return $this->uri;
    }
    
    public function getParam(string $name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }
    
    public function getQuery(string $name = null, $default = null)
    {
        if ($name === null) {
            return $this->query;
        }
        return $this->query[$name] ?? $default;
    }
    
    public function getBody(string $name = null, $default = null)
    {
        if ($name === null) {
            return $this->body;
        }
        return $this->body[$name] ?? $default;
    }
    
    public function getHeader(string $name, $default = null)
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }
        return $default;
    }
    
    public function getBearerToken(): ?string
    {
        $auth = $this->getHeader('Authorization');
        if ($auth && preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    public function setUser(array $user): void
    {
        $this->user = $user;
    }
    
    public function getUser(): ?array
    {
        return $this->user;
    }
    
    public function getUserId(): ?string
    {
        return $this->user['id'] ?? null;
    }
    
    public function getJsonBody(): array
    {
        return $this->body;
    }
    
    public function getQueryParam(string $name, $default = null)
    {
        return $this->query[$name] ?? $default;
    }
    
    public function getPathParam(string $name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }
    
    public function validate(array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $this->body[$field] ?? null;
            
            if (str_contains($rule, 'required') && empty($value)) {
                $errors[$field] = "Le champ $field est requis";
                continue;
            }
            
            if (!empty($value)) {
                if (str_contains($rule, 'email') && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Le champ $field doit être une adresse email valide";
                }
                
                if (preg_match('/min:(\d+)/', $rule, $matches) && strlen($value) < $matches[1]) {
                    $errors[$field] = "Le champ $field doit contenir au moins {$matches[1]} caractères";
                }
                
                if (preg_match('/max:(\d+)/', $rule, $matches) && strlen($value) > $matches[1]) {
                    $errors[$field] = "Le champ $field ne doit pas dépasser {$matches[1]} caractères";
                }
            }
        }
        
        return $errors;
    }
}