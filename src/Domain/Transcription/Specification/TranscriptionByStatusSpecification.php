<?php

namespace Domain\Transcription\Specification;

use Domain\Common\Specification\CompositeSpecification;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionStatus;

class TranscriptionByStatusSpecification extends CompositeSpecification
{
    private TranscriptionStatus $status;
    
    public function __construct(TranscriptionStatus $status)
    {
        $this->status = $status;
    }
    
    public function isSatisfiedBy($candidate): bool
    {
        if (!$candidate instanceof Transcription) {
            return false;
        }
        
        return $candidate->status()->equals($this->status);
    }
}