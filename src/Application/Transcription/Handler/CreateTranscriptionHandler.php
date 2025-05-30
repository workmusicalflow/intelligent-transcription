<?php

namespace Application\Transcription\Handler;

use Application\Transcription\Command\CreateTranscriptionCommand;
use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\EventSourcing\EventDispatcherInterface;
use Domain\Transcription\Event\TranscriptionCreated;

/**
 * Handler pour créer une nouvelle transcription
 */
class CreateTranscriptionHandler
{
    private TranscriptionRepository $repository;
    private EventDispatcherInterface $eventDispatcher;
    
    public function __construct(
        TranscriptionRepository $repository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function handle(CreateTranscriptionCommand $command): TranscriptionId
    {
        // Générer un nouvel ID
        $transcriptionId = TranscriptionId::generate();
        
        // Créer l'entité Transcription
        $transcription = Transcription::create(
            $transcriptionId,
            $command->userId,
            $command->audioFile,
            $command->language,
            TranscriptionStatus::pending(),
            new TranscribedText(''), // Vide initialement
            null, // Pas de YouTube metadata pour l'instant
            null  // Pas de coût pour l'instant
        );
        
        // Sauvegarder dans le repository
        $this->repository->save($transcription);
        
        // Publier l'événement
        $event = new TranscriptionCreated(
            $transcriptionId,
            $command->userId,
            $command->audioFile->path(),
            $command->language->code()
        );
        
        $this->eventDispatcher->dispatch($event);
        
        return $transcriptionId;
    }
}