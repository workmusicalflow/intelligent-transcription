<?php

namespace Infrastructure\Configuration;

/**
 * Interface pour la gestion de configuration
 */
interface ConfigurationInterface
{
    public function get(string $key, mixed $default = null): mixed;
    
    public function has(string $key): bool;
    
    public function set(string $key, mixed $value): void;
    
    public function all(): array;
    
    public function load(string $environment = 'production'): void;
    
    public function validate(): bool;
}