<?php

namespace Application\Transcription\Handler;

use Application\Transcription\Query\GetTranscriptionQuery;
use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Transcription\Entity\Transcription;

/**
 * Handler pour récupérer une transcription
 */
class GetTranscriptionHandler
{
    private TranscriptionRepository $repository;
    
    public function __construct(TranscriptionRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function handle(GetTranscriptionQuery $query): ?Transcription
    {
        $transcription = $this->repository->findById($query->transcriptionId);
        
        // Vérifier que l'utilisateur a accès à cette transcription
        if ($transcription && !$transcription->userId()->equals($query->userId)) {
            return null; // Pas d'accès
        }
        
        return $transcription;
    }
}