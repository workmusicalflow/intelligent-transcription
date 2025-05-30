<?php

namespace Domain\Transcription\Specification;

use Domain\Common\Specification\CompositeSpecification;
use Domain\Transcription\Entity\Transcription;

class YouTubeTranscriptionSpecification extends CompositeSpecification
{
    public function isSatisfiedBy($candidate): bool
    {
        if (!$candidate instanceof Transcription) {
            return false;
        }
        
        return $candidate->isYouTubeSource();
    }
}