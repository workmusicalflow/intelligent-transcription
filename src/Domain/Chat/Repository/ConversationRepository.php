<?php

namespace Domain\Chat\Repository;

use Domain\Common\ValueObject\UserId;
use Domain\Chat\ValueObject\ConversationId;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Chat\Entity\Conversation;
use Domain\Chat\Collection\ConversationCollection;

interface ConversationRepository
{
    /**
     * Persiste une conversation
     */
    public function save(Conversation $conversation): void;
    
    /**
     * Trouve une conversation par son ID
     */
    public function findById(ConversationId $id): ?Conversation;
    
    /**
     * Trouve toutes les conversations d'un utilisateur
     */
    public function findByUser(UserId $userId): ConversationCollection;
    
    /**
     * Trouve les conversations liées à une transcription
     */
    public function findByTranscription(TranscriptionId $transcriptionId): ConversationCollection;
    
    /**
     * Trouve les conversations récentes d'un utilisateur
     */
    public function findRecentByUser(UserId $userId, int $limit = 10): ConversationCollection;
    
    /**
     * Supprime une conversation
     */
    public function delete(ConversationId $id): void;
    
    /**
     * Compte le nombre de conversations d'un utilisateur
     */
    public function countByUser(UserId $userId): int;
    
    /**
     * Vérifie si une conversation existe
     */
    public function exists(ConversationId $id): bool;
    
    /**
     * Obtient le prochain ID disponible
     */
    public function nextIdentity(): ConversationId;
    
    /**
     * Trouve les conversations nécessitant un résumé
     */
    public function findConversationsNeedingSummarization(int $limit = 10): ConversationCollection;
    
    /**
     * Obtient les statistiques de cache pour un utilisateur
     */
    public function getCacheStatsByUser(UserId $userId): array;
}