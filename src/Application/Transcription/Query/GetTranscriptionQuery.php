<?php

namespace Application\Transcription\Query;

use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Common\ValueObject\UserId;

/**
 * Query pour récupérer une transcription
 */
class GetTranscriptionQuery
{
    public readonly TranscriptionId $transcriptionId;
    public readonly UserId $userId;
    
    public function __construct(
        TranscriptionId $transcriptionId,
        UserId $userId
    ) {
        $this->transcriptionId = $transcriptionId;
        $this->userId = $userId;
    }
}