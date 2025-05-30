<?php

namespace Infrastructure\Repository\InMemory;

use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\Collection\TranscriptionCollection;
use Domain\Transcription\Repository\Criteria\TranscriptionSearchCriteria;
use Domain\Common\ValueObject\UserId;
use Domain\Common\Specification\Specification;

/**
 * Repository en mémoire pour les tests
 */
class InMemoryTranscriptionRepository implements TranscriptionRepository
{
    private array $transcriptions = [];
    
    public function save(Transcription $transcription): void
    {
        $this->transcriptions[$transcription->id()] = $transcription;
    }
    
    public function findById(TranscriptionId $id): ?Transcription
    {
        return $this->transcriptions[$id->value()] ?? null;
    }
    
    public function findByUser(UserId $userId): TranscriptionCollection
    {
        $userTranscriptions = array_filter(
            $this->transcriptions,
            fn(Transcription $t) => $t->userId()->equals($userId)
        );
        
        return new TranscriptionCollection(array_values($userTranscriptions));
    }
    
    public function findByUserPaginated(UserId $userId, int $page = 1, int $perPage = 10): TranscriptionCollection
    {
        $userTranscriptions = $this->findByUser($userId)->items();
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($userTranscriptions, $offset, $perPage);
        
        return new TranscriptionCollection($paginated);
    }
    
    public function findByStatus(TranscriptionStatus $status): TranscriptionCollection
    {
        $statusTranscriptions = array_filter(
            $this->transcriptions,
            fn(Transcription $t) => $t->status()->equals($status)
        );
        
        return new TranscriptionCollection(array_values($statusTranscriptions));
    }
    
    public function findByUserAndStatus(UserId $userId, TranscriptionStatus $status): TranscriptionCollection
    {
        $filtered = array_filter(
            $this->transcriptions,
            fn(Transcription $t) => $t->userId()->equals($userId) && $t->status()->equals($status)
        );
        
        return new TranscriptionCollection(array_values($filtered));
    }
    
    public function delete(TranscriptionId $id): void
    {
        unset($this->transcriptions[$id->value()]);
    }
    
    public function countByUser(UserId $userId): int
    {
        return count(array_filter(
            $this->transcriptions,
            fn(Transcription $t) => $t->userId()->equals($userId)
        ));
    }
    
    public function countByUserAndStatus(UserId $userId, TranscriptionStatus $status): int
    {
        return count(array_filter(
            $this->transcriptions,
            fn(Transcription $t) => $t->userId()->equals($userId) && $t->status()->equals($status)
        ));
    }
    
    public function findRecentByUser(UserId $userId, int $limit = 10): TranscriptionCollection
    {
        $userTranscriptions = $this->findByUser($userId)->items();
        // Trier par date de création (simulé avec l'ordre d'ajout)
        $recent = array_slice($userTranscriptions, -$limit);
        
        return new TranscriptionCollection(array_reverse($recent));
    }
    
    public function findYouTubeTranscriptionsByUser(UserId $userId): TranscriptionCollection
    {
        $youtubeTranscriptions = array_filter(
            $this->transcriptions,
            fn(Transcription $t) => $t->userId()->equals($userId) && $t->youtubeMetadata() !== null
        );
        
        return new TranscriptionCollection(array_values($youtubeTranscriptions));
    }
    
    public function nextIdentity(): TranscriptionId
    {
        return TranscriptionId::generate();
    }
    
    public function search(TranscriptionSearchCriteria $criteria): TranscriptionCollection
    {
        // Implémentation basique pour les tests
        return $this->findByUser($criteria->getUserId());
    }
    
    public function findBySpecification(Specification $specification): TranscriptionCollection
    {
        $matching = array_filter(
            $this->transcriptions,
            fn(Transcription $t) => $specification->isSatisfiedBy($t)
        );
        
        return new TranscriptionCollection(array_values($matching));
    }
    
    public function exists(TranscriptionId $id): bool
    {
        return isset($this->transcriptions[$id->value()]);
    }
    
    // Méthodes utilitaires pour les tests
    public function clear(): void
    {
        $this->transcriptions = [];
    }
    
    public function count(): int
    {
        return count($this->transcriptions);
    }
    
    public function findAll(): TranscriptionCollection
    {
        return new TranscriptionCollection(array_values($this->transcriptions));
    }
}