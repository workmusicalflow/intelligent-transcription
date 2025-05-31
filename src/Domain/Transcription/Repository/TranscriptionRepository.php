<?php

namespace Domain\Transcription\Repository;

use Domain\Common\ValueObject\UserId;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\Collection\TranscriptionCollection;

interface TranscriptionRepository
{
    /**
     * Persiste une transcription
     */
    public function save(Transcription $transcription): void;
    
    /**
     * Trouve une transcription par son ID
     */
    public function findById(TranscriptionId $id): ?Transcription;
    
    /**
     * Trouve toutes les transcriptions d'un utilisateur
     */
    public function findByUser(UserId $userId): TranscriptionCollection;
    
    /**
     * Trouve les transcriptions d'un utilisateur avec pagination
     */
    public function findByUserPaginated(
        UserId $userId,
        int $page = 1,
        int $perPage = 10
    ): TranscriptionCollection;
    
    /**
     * Trouve les transcriptions par statut
     */
    public function findByStatus(TranscriptionStatus $status): TranscriptionCollection;
    
    /**
     * Trouve les transcriptions d'un utilisateur par statut
     */
    public function findByUserAndStatus(
        UserId $userId,
        TranscriptionStatus $status
    ): TranscriptionCollection;
    
    /**
     * Supprime une transcription
     */
    public function delete(TranscriptionId $id): void;
    
    /**
     * Compte le nombre de transcriptions d'un utilisateur
     */
    public function countByUser(UserId $userId): int;
    
    /**
     * Compte les transcriptions par statut pour un utilisateur
     */
    public function countByUserAndStatus(UserId $userId, TranscriptionStatus $status): int;
    
    /**
     * Trouve les transcriptions récentes d'un utilisateur
     */
    public function findRecentByUser(UserId $userId, int $limit = 10): TranscriptionCollection;
    
    /**
     * Vérifie si une transcription existe
     */
    public function exists(TranscriptionId $id): bool;
    
    /**
     * Trouve les transcriptions YouTube d'un utilisateur
     */
    public function findYouTubeTranscriptionsByUser(UserId $userId): TranscriptionCollection;
    
    /**
     * Obtient le prochain ID disponible (pour certaines implémentations)
     */
    public function nextIdentity(): TranscriptionId;
    
    /**
     * Recherche avancée avec critères
     */
    public function search(Criteria\TranscriptionSearchCriteria $criteria): TranscriptionCollection;
    
    /**
     * Trouve les transcriptions qui satisfont une spécification
     */
    public function findBySpecification(\Domain\Common\Specification\Specification $specification): TranscriptionCollection;
    
    /**
     * Trouve toutes les transcriptions
     */
    public function findAll(): TranscriptionCollection;
}