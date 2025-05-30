<?php

namespace Application\Transcription\Command;

use Domain\Common\ValueObject\UserId;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;

/**
 * Commande pour créer une nouvelle transcription
 */
class CreateTranscriptionCommand
{
    public readonly UserId $userId;
    public readonly AudioFile $audioFile;
    public readonly Language $language;
    
    public function __construct(
        UserId $userId,
        AudioFile $audioFile,
        Language $language
    ) {
        $this->userId = $userId;
        $this->audioFile = $audioFile;
        $this->language = $language;
    }
}