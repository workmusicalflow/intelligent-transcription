<?php

namespace App\Services\Translation;

use Psr\Log\LoggerInterface;

/**
 * Service de cache intelligent pour traductions
 * Optimise performance et coûts avec cache multi-niveaux
 */
class TranslationCacheService
{
    private array $memoryCache = [];
    private string $cacheDir;

    public function __construct(
        private LoggerInterface $logger,
        ?string $cacheDir = null
    ) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../../cache/translations';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Récupérer traduction du cache
     */
    public function get(string $key): ?array
    {
        // 1. Vérifier cache mémoire (le plus rapide)
        if (isset($this->memoryCache[$key])) {
            $this->logger->debug('Translation cache hit (memory)', ['key' => $key]);
            return $this->memoryCache[$key];
        }

        // 2. Vérifier cache fichier
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            
            if ($data && isset($data['expires_at']) && $data['expires_at'] > time()) {
                // Mettre en cache mémoire pour prochains accès
                $this->memoryCache[$key] = $data['content'];
                
                $this->logger->debug('Translation cache hit (file)', ['key' => $key]);
                return $data['content'];
            } else {
                // Cache expiré, supprimer
                unlink($filePath);
            }
        }

        return null;
    }

    /**
     * Stocker traduction en cache
     */
    public function set(string $key, array $data, int $ttl = 3600): bool
    {
        try {
            // Cache mémoire
            $this->memoryCache[$key] = $data;

            // Cache fichier avec TTL
            $cacheData = [
                'content' => $data,
                'created_at' => time(),
                'expires_at' => time() + $ttl,
                'key' => $key
            ];

            $filePath = $this->getCacheFilePath($key);
            $success = file_put_contents($filePath, json_encode($cacheData, JSON_PRETTY_PRINT)) !== false;

            if ($success) {
                $this->logger->debug('Translation cached successfully', [
                    'key' => $key,
                    'ttl' => $ttl,
                    'size' => strlen(json_encode($data))
                ]);
            }

            return $success;

        } catch (\Exception $e) {
            $this->logger->error('Failed to cache translation', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalider cache pour clé spécifique
     */
    public function invalidate(string $key): bool
    {
        // Supprimer du cache mémoire
        unset($this->memoryCache[$key]);

        // Supprimer du cache fichier
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * Nettoyer cache expiré
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $files = glob($this->cacheDir . '/*.json');

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if (!$data || !isset($data['expires_at']) || $data['expires_at'] <= time()) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        // Nettoyer cache mémoire (garder seulement les 100 plus récents)
        if (count($this->memoryCache) > 100) {
            $this->memoryCache = array_slice($this->memoryCache, -100, null, true);
        }

        $this->logger->info('Translation cache cleaned', ['files_removed' => $cleaned]);
        return $cleaned;
    }

    /**
     * Statistiques du cache
     */
    public function getStats(): array
    {
        $files = glob($this->cacheDir . '/*.json');
        $totalSize = 0;
        $validFiles = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['expires_at']) && $data['expires_at'] > time()) {
                $validFiles++;
            }
        }

        return [
            'memory_cache_entries' => count($this->memoryCache),
            'file_cache_entries' => count($files),
            'valid_cache_entries' => $validFiles,
            'total_cache_size_bytes' => $totalSize,
            'cache_directory' => $this->cacheDir
        ];
    }

    /**
     * Générer chemin fichier cache
     */
    private function getCacheFilePath(string $key): string
    {
        // Utiliser hash pour éviter problèmes noms fichiers
        $hash = hash('sha256', $key);
        return $this->cacheDir . '/' . $hash . '.json';
    }
}