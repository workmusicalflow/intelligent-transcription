<?php

namespace Domain\Analytics\Service;

use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Analytics\ValueObject\Summary;

/**
 * Interface pour le service de génération de résumés
 */
interface SummarizerInterface
{
    /**
     * Génère un résumé à partir d'un texte transcrit
     * 
     * @param TranscribedText $text Le texte à résumer
     * @param array $options Options pour la génération (langue, longueur max, etc.)
     * @return Summary Le résumé généré
     */
    public function summarize(TranscribedText $text, array $options = []): Summary;
}