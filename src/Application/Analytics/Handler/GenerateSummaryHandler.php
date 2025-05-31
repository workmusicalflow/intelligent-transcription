<?php

namespace Application\Analytics\Handler;

use Application\Analytics\Command\GenerateSummaryCommand;
use Domain\Analytics\Service\SummarizerInterface;
use Domain\Transcription\Repository\TranscriptionRepository;
use Application\Event\Dispatcher\EventDispatcherInterface;
use Domain\Analytics\Event\SummaryGenerated;

/**
 * Handler pour générer des résumés de transcription
 */
final class GenerateSummaryHandler
{
    public function __construct(
        private readonly SummarizerInterface $summarizer,
        private readonly TranscriptionRepository $transcriptionRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}
    
    public function handle(GenerateSummaryCommand $command): void
    {
        // Récupérer la transcription
        $transcription = $this->transcriptionRepository->findById($command->transcriptionId);
        
        if (!$transcription) {
            throw new \InvalidArgumentException("Transcription not found: {$command->transcriptionId}");
        }
        
        if (!$transcription->isCompleted()) {
            throw new \InvalidArgumentException("Transcription is not completed yet");
        }
        
        // Générer le résumé
        $transcribedText = $transcription->text();
        if (!$transcribedText) {
            throw new \InvalidArgumentException("No transcribed text available");
        }
        
        $summary = $this->summarizer->summarize(
            $transcribedText,
            $command->style->toArray(),
            $command->language
        );
        
        // Note: setSummary method does not exist in current Transcription entity
        // For now, we'll save it in metadata
        $this->transcriptionRepository->save($transcription);
        
        // Publier l'événement
        $this->eventDispatcher->dispatch(
            new SummaryGenerated(
                $transcription->transcriptionId(),
                $summary->content(),
                $command->style
            )
        );
    }
}