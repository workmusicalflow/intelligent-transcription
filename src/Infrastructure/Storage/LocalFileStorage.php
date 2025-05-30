<?php

namespace Infrastructure\Storage;

use Infrastructure\Storage\StorageInterface;
use Exception;

/**
 * Stockage de fichiers local
 */
class LocalFileStorage implements StorageInterface
{
    private string $basePath;
    private array $allowedExtensions;
    private int $maxFileSize;
    
    public function __construct(
        string $basePath,
        array $allowedExtensions = ['mp3', 'wav', 'm4a', 'txt', 'json'],
        int $maxFileSize = 100 * 1024 * 1024 // 100MB
    ) {
        $this->basePath = rtrim($basePath, '/');
        $this->allowedExtensions = $allowedExtensions;
        $this->maxFileSize = $maxFileSize;
        
        $this->ensureBasePathExists();
    }
    
    public function store(string $path, string $content): bool
    {
        try {
            $fullPath = $this->getFullPath($path);
            $this->validatePath($path);
            $this->validateContent($content);
            
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            return file_put_contents($fullPath, $content) !== false;
            
        } catch (Exception $e) {
            error_log("Storage error: " . $e->getMessage());
            return false;
        }
    }
    
    public function get(string $path): ?string
    {
        $fullPath = $this->getFullPath($path);
        
        if (!$this->exists($path)) {
            return null;
        }
        
        $content = file_get_contents($fullPath);
        return $content !== false ? $content : null;
    }
    
    public function exists(string $path): bool
    {
        return file_exists($this->getFullPath($path));
    }
    
    public function delete(string $path): bool
    {
        $fullPath = $this->getFullPath($path);
        
        if (!$this->exists($path)) {
            return true; // Déjà supprimé
        }
        
        return unlink($fullPath);
    }
    
    public function move(string $from, string $to): bool
    {
        $fromPath = $this->getFullPath($from);
        $toPath = $this->getFullPath($to);
        
        if (!$this->exists($from)) {
            return false;
        }
        
        $toDirectory = dirname($toPath);
        if (!is_dir($toDirectory)) {
            mkdir($toDirectory, 0755, true);
        }
        
        return rename($fromPath, $toPath);
    }
    
    public function size(string $path): int
    {
        $fullPath = $this->getFullPath($path);
        
        if (!$this->exists($path)) {
            return 0;
        }
        
        return filesize($fullPath) ?: 0;
    }
    
    public function lastModified(string $path): int
    {
        $fullPath = $this->getFullPath($path);
        
        if (!$this->exists($path)) {
            return 0;
        }
        
        return filemtime($fullPath) ?: 0;
    }
    
    public function url(string $path): string
    {
        // Pour un stockage local, retourner un chemin relatif
        return '/' . ltrim($path, '/');
    }
    
    public function list(string $directory = ''): array
    {
        $fullPath = $this->getFullPath($directory);
        
        if (!is_dir($fullPath)) {
            return [];
        }
        
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getPathname(), strlen($this->basePath) + 1);
                $files[] = [
                    'path' => $relativePath,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'type' => $file->getExtension()
                ];
            }
        }
        
        return $files;
    }
    
    public function makeDirectory(string $directory): bool
    {
        $fullPath = $this->getFullPath($directory);
        
        if (is_dir($fullPath)) {
            return true;
        }
        
        return mkdir($fullPath, 0755, true);
    }
    
    private function getFullPath(string $path): string
    {
        // Nettoyer le chemin pour éviter les attaques de traversée
        $cleanPath = $this->sanitizePath($path);
        return $this->basePath . '/' . $cleanPath;
    }
    
    private function sanitizePath(string $path): string
    {
        // Supprimer les tentatives de traversée de répertoire
        $path = str_replace(['../', '..\\', '../'], '', $path);
        $path = ltrim($path, '/\\');
        
        return $path;
    }
    
    private function validatePath(string $path): void
    {
        if (empty($path)) {
            throw new Exception("Path cannot be empty");
        }
        
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (!empty($this->allowedExtensions) && !in_array(strtolower($extension), $this->allowedExtensions)) {
            throw new Exception("File extension not allowed: {$extension}");
        }
    }
    
    private function validateContent(string $content): void
    {
        if (strlen($content) > $this->maxFileSize) {
            throw new Exception("Content too large: " . strlen($content) . " bytes. Max: {$this->maxFileSize}");
        }
    }
    
    private function ensureBasePathExists(): void
    {
        if (!is_dir($this->basePath)) {
            if (!mkdir($this->basePath, 0755, true)) {
                throw new Exception("Cannot create base storage directory: {$this->basePath}");
            }
        }
        
        if (!is_writable($this->basePath)) {
            throw new Exception("Storage directory is not writable: {$this->basePath}");
        }
    }
    
    public function getDiskUsage(): array
    {
        $totalSize = 0;
        $fileCount = 0;
        
        $files = $this->list();
        foreach ($files as $file) {
            $totalSize += $file['size'];
            $fileCount++;
        }
        
        return [
            'total_files' => $fileCount,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'base_path' => $this->basePath,
            'available_space' => disk_free_space($this->basePath)
        ];
    }
    
    public function cleanup(int $olderThanDays = 7): array
    {
        $cutoffTime = time() - ($olderThanDays * 24 * 60 * 60);
        $deletedFiles = [];
        $deletedSize = 0;
        
        $files = $this->list();
        foreach ($files as $file) {
            if ($file['modified'] < $cutoffTime) {
                $size = $file['size'];
                if ($this->delete($file['path'])) {
                    $deletedFiles[] = $file['path'];
                    $deletedSize += $size;
                }
            }
        }
        
        return [
            'deleted_files' => count($deletedFiles),
            'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
            'files' => $deletedFiles
        ];
    }
    
    public function getStats(): array
    {
        return [
            'base_path' => $this->basePath,
            'allowed_extensions' => $this->allowedExtensions,
            'max_file_size_mb' => round($this->maxFileSize / 1024 / 1024, 2),
            'disk_usage' => $this->getDiskUsage()
        ];
    }
}