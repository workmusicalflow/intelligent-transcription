<?php

namespace Domain\Transcription\Specification;

use Domain\Common\Specification\CompositeSpecification;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\Language;

class TranscriptionByLanguageSpecification extends CompositeSpecification
{
    private Language $language;
    
    public function __construct(Language $language)
    {
        $this->language = $language;
    }
    
    public function isSatisfiedBy($candidate): bool
    {
        if (!$candidate instanceof Transcription) {
            return false;
        }
        
        return $candidate->language()->equals($this->language);
    }
}