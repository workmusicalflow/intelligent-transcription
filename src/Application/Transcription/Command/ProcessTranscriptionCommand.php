<?php

namespace Application\Transcription\Command;

use Domain\Transcription\ValueObject\TranscriptionId;

/**
 * Commande pour lancer le traitement d'une transcription
 */
class ProcessTranscriptionCommand
{
    public readonly TranscriptionId $transcriptionId;
    
    public function __construct(TranscriptionId $transcriptionId)
    {
        $this->transcriptionId = $transcriptionId;
    }
}