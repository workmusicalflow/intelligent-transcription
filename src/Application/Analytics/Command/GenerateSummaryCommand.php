<?php

namespace Application\Analytics\Command;

use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Analytics\ValueObject\SummaryStyle;
use Domain\Transcription\ValueObject\Language;

/**
 * Commande pour générer un résumé de transcription
 */
final class GenerateSummaryCommand
{
    public function __construct(
        public readonly TranscriptionId $transcriptionId,
        public readonly SummaryStyle $style,
        public readonly Language $language
    ) {}
}