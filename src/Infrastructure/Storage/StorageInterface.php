<?php

namespace Infrastructure\Storage;

/**
 * Interface pour l'abstraction du stockage de fichiers
 */
interface StorageInterface
{
    public function store(string $path, string $content): bool;
    
    public function get(string $path): ?string;
    
    public function exists(string $path): bool;
    
    public function delete(string $path): bool;
    
    public function move(string $from, string $to): bool;
    
    public function size(string $path): int;
    
    public function lastModified(string $path): int;
    
    public function url(string $path): string;
    
    public function list(string $directory = ''): array;
    
    public function makeDirectory(string $directory): bool;
}