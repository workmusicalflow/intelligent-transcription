<?php

namespace Infrastructure\GraphQL\Controller;

use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Annotations\Right;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Common\ValueObject\UserId;
use Application\Transcription\Query\GetTranscriptionQuery;
use Application\Transcription\Query\GetUserTranscriptionsQuery;
use Infrastructure\Container\ServiceLocator;

/**
 * Controller GraphQL pour les queries de transcription
 */
class TranscriptionQueryController
{
    /**
     * @Query
     * @Logged
     */
    public function transcription(string $id): ?Transcription
    {
        try {
            $transcriptionId = new TranscriptionId($id);
            $userId = $this->getCurrentUserId();
            
            $query = new GetTranscriptionQuery($transcriptionId, $userId);
            $handler = ServiceLocator::get(\Application\Transcription\Handler\GetTranscriptionHandler::class);
            
            return $handler->handle($query);
            
        } catch (\Exception $e) {
            throw new \RuntimeException("Cannot fetch transcription: " . $e->getMessage());
        }
    }
    
    /**
     * @Query
     * @Logged
     * @return Transcription[]
     */
    public function transcriptions(
        int $page = 1,
        int $limit = 20,
        ?string $status = null
    ): array {
        try {
            $userId = $this->getCurrentUserId();
            $limit = min($limit, 100); // Limiter pour éviter la surcharge
            
            $query = new GetUserTranscriptionsQuery($userId, $page, $limit, $status);
            $handler = ServiceLocator::get(\Application\Transcription\Handler\GetUserTranscriptionsHandler::class);
            
            $result = $handler->handle($query);
            return $result['items'] ?? [];
            
        } catch (\Exception $e) {
            throw new \RuntimeException("Cannot fetch transcriptions: " . $e->getMessage());
        }
    }
    
    /**
     * @Query
     * @Logged
     */
    public function transcriptionsByLanguage(string $language): array
    {
        try {
            $userId = $this->getCurrentUserId();
            $repository = ServiceLocator::get(\Domain\Transcription\Repository\TranscriptionRepository::class);
            
            return $repository->findByUserAndLanguage(
                $userId,
                new \Domain\Transcription\ValueObject\Language($language)
            );
            
        } catch (\Exception $e) {
            throw new \RuntimeException("Cannot fetch transcriptions by language: " . $e->getMessage());
        }
    }
    
    /**
     * @Query
     * @Logged
     */
    public function transcriptionCount(): int
    {
        try {
            $userId = $this->getCurrentUserId();
            $repository = ServiceLocator::get(\Domain\Transcription\Repository\TranscriptionRepository::class);
            
            return $repository->countByUser($userId);
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * @Query
     * @Logged
     */
    public function transcriptionStats(): TranscriptionStatsType
    {
        try {
            $userId = $this->getCurrentUserId();
            $repository = ServiceLocator::get(\Domain\Transcription\Repository\TranscriptionRepository::class);
            
            $total = $repository->countByUser($userId);
            $completed = $repository->countByUserAndStatus(
                $userId,
                \Domain\Transcription\ValueObject\TranscriptionStatus::completed()
            );
            $processing = $repository->countByUserAndStatus(
                $userId,
                \Domain\Transcription\ValueObject\TranscriptionStatus::processing()
            );
            $pending = $repository->countByUserAndStatus(
                $userId,
                \Domain\Transcription\ValueObject\TranscriptionStatus::pending()
            );
            
            return new TranscriptionStatsType($total, $completed, $processing, $pending);
            
        } catch (\Exception $e) {
            return new TranscriptionStatsType(0, 0, 0, 0);
        }
    }
    
    /**
     * Récupère l'ID de l'utilisateur actuel depuis le contexte
     */
    private function getCurrentUserId(): UserId
    {
        // Dans GraphQLite, on peut accéder au contexte via l'injection
        // Pour simplifier, on utilise la session ou JWT
        $userId = $_SESSION['user_id'] ?? 'anonymous';
        return new UserId($userId);
    }
}

/**
 * Type pour les statistiques de transcription
 * @Type
 */
class TranscriptionStatsType
{
    public function __construct(
        private int $total,
        private int $completed,
        private int $processing,
        private int $pending
    ) {}
    
    /**
     * @Field
     */
    public function getTotal(): int
    {
        return $this->total;
    }
    
    /**
     * @Field
     */
    public function getCompleted(): int
    {
        return $this->completed;
    }
    
    /**
     * @Field
     */
    public function getProcessing(): int
    {
        return $this->processing;
    }
    
    /**
     * @Field
     */
    public function getPending(): int
    {
        return $this->pending;
    }
    
    /**
     * @Field
     */
    public function getCompletionRate(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }
        
        return round(($this->completed / $this->total) * 100, 2);
    }
}