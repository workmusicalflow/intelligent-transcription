<?php

namespace Infrastructure\External;

/**
 * Interface pour les clients API externes
 */
interface ApiClientInterface
{
    public function request(string $method, string $endpoint, array $data = []): ApiResponse;
    
    public function get(string $endpoint, array $params = []): ApiResponse;
    
    public function post(string $endpoint, array $data = []): ApiResponse;
    
    public function put(string $endpoint, array $data = []): ApiResponse;
    
    public function delete(string $endpoint): ApiResponse;
    
    public function setHeader(string $name, string $value): void;
    
    public function setTimeout(int $seconds): void;
    
    public function setRetryPolicy(int $maxRetries, float $delay = 1.0): void;
}