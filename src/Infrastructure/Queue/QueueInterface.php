<?php

namespace Infrastructure\Queue;

/**
 * Interface pour le système de queue
 */
interface QueueInterface
{
    public function push(string $job, array $data = [], string $queue = 'default'): string;
    
    public function pop(string $queue = 'default'): ?QueueJob;
    
    public function size(string $queue = 'default'): int;
    
    public function clear(string $queue = 'default'): int;
    
    public function failed(string $jobId, string $reason): void;
    
    public function retry(string $jobId): bool;
    
    public function getStats(): array;
    
    public function getJobStatus(string $jobId): string;
}