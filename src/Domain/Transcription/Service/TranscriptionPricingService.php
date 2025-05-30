<?php

namespace Domain\Transcription\Service;

use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Common\ValueObject\Money;

/**
 * Service de calcul des prix pour les transcriptions
 */
interface TranscriptionPricingService
{
    /**
     * Calcule le prix d'une transcription
     * 
     * @param AudioFile $audioFile Le fichier audio à transcrire
     * @param Language $language La langue de transcription
     * @param bool $isPriority Si la transcription est prioritaire
     * @param array $options Options supplémentaires
     * @return Money Le prix calculé
     */
    public function calculatePrice(
        AudioFile $audioFile,
        Language $language,
        bool $isPriority = false,
        array $options = []
    ): Money;
    
    /**
     * Calcule le prix estimé basé sur la durée
     * 
     * @param float $durationInMinutes La durée en minutes
     * @param Language $language La langue de transcription
     * @param bool $isPriority Si la transcription est prioritaire
     * @return Money Le prix estimé
     */
    public function estimatePrice(
        float $durationInMinutes,
        Language $language,
        bool $isPriority = false
    ): Money;
    
    /**
     * Obtient le tarif de base par minute
     * 
     * @return Money Le tarif par minute
     */
    public function getBaseRatePerMinute(): Money;
    
    /**
     * Obtient le multiplicateur pour les transcriptions prioritaires
     * 
     * @return float Le multiplicateur
     */
    public function getPriorityMultiplier(): float;
    
    /**
     * Obtient le multiplicateur pour une langue spécifique
     * 
     * @param Language $language La langue
     * @return float Le multiplicateur de complexité
     */
    public function getLanguageComplexityMultiplier(Language $language): float;
    
    /**
     * Applique une remise au prix
     * 
     * @param Money $price Le prix original
     * @param float $discountPercentage Le pourcentage de remise (0-100)
     * @return Money Le prix avec remise
     */
    public function applyDiscount(Money $price, float $discountPercentage): Money;
}