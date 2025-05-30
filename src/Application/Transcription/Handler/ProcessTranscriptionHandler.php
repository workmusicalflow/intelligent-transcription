<?php

namespace Application\Transcription\Handler;

use Application\Transcription\Command\ProcessTranscriptionCommand;
use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Transcription\Service\TranscriberInterface;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\EventSourcing\EventDispatcherInterface;
use Domain\Transcription\Event\TranscriptionProcessingStarted;
use Domain\Transcription\Event\TranscriptionCompleted;
use Domain\Common\ValueObject\Money;

/**
 * Handler pour traiter une transcription
 */
class ProcessTranscriptionHandler
{
    private TranscriptionRepository $repository;
    private TranscriberInterface $transcriber;
    private EventDispatcherInterface $eventDispatcher;
    
    public function __construct(
        TranscriptionRepository $repository,
        TranscriberInterface $transcriber,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->repository = $repository;
        $this->transcriber = $transcriber;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function handle(ProcessTranscriptionCommand $command): void
    {
        // Récupérer la transcription
        $transcription = $this->repository->findById($command->transcriptionId);
        
        if (!$transcription) {
            throw new \Exception('Transcription non trouvée');
        }
        
        // Marquer comme en cours de traitement
        $transcription->startProcessing();
        $this->repository->save($transcription);
        
        // Publier l'événement de début
        $this->eventDispatcher->dispatch(
            new TranscriptionProcessingStarted($transcription->id())
        );
        
        try {
            // Effectuer la transcription
            $result = $this->transcriber->transcribe(
                $transcription->audioFile(),
                $transcription->language()
            );
            
            // Mettre à jour avec le résultat
            $transcription->complete(
                $result->text(),
                new Money($result->cost(), 'USD')
            );
            
            // Sauvegarder
            $this->repository->save($transcription);
            
            // Publier l'événement de fin
            $this->eventDispatcher->dispatch(
                new TranscriptionCompleted(
                    $transcription->id(),
                    $result->text()->value(),
                    $result->cost()
                )
            );
            
        } catch (\Exception $e) {
            // En cas d'erreur, marquer comme échoué
            $transcription->fail($e->getMessage());
            $this->repository->save($transcription);
            
            throw $e;
        }
    }
}