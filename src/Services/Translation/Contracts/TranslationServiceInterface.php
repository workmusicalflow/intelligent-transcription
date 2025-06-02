<?php

namespace App\Services\Translation\Contracts;

use App\Services\Translation\DTO\TranslationConfig;

/**
 * Interface pour services de traduction avec support doublage
 */
interface TranslationServiceInterface
{
    /**
     * Traduire segments avec préservation timestamps
     *
     * @param array $segments Segments avec word-level timestamps
     * @param string $targetLanguage Code langue (fr, es, de, etc.)
     * @param TranslationConfig|null $config Configuration optionnelle
     * @return array Segments traduits avec timestamps préservés
     */
    public function translateSegments(
        array $segments,
        string $targetLanguage,
        ?TranslationConfig $config = null
    ): array;

    /**
     * Estimer coût de traduction
     *
     * @param array $segments Segments à traduire
     * @param string $targetLanguage Langue cible
     * @return float Coût estimé en USD
     */
    public function estimateCost(array $segments, string $targetLanguage): float;

    /**
     * Obtenir capacités du service
     *
     * @return array Capacités disponibles
     */
    public function getCapabilities(): array;
}