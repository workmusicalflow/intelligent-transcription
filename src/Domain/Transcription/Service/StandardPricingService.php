<?php

namespace Domain\Transcription\Service;

use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Common\ValueObject\Money;

/**
 * Implémentation standard du service de pricing
 */
final class StandardPricingService implements TranscriptionPricingService
{
    private const BASE_RATE_PER_MINUTE = 0.006; // $0.006 per minute (Whisper API rate)
    private const PRIORITY_MULTIPLIER = 2.5;
    private const MINIMUM_CHARGE = 0.10; // Minimum $0.10 per transcription
    
    private const LANGUAGE_COMPLEXITY_MULTIPLIERS = [
        // Simple languages (Latin alphabet)
        'en' => 1.0,
        'es' => 1.0,
        'fr' => 1.0,
        'de' => 1.0,
        'it' => 1.0,
        'pt' => 1.0,
        'nl' => 1.0,
        'sv' => 1.0,
        'da' => 1.0,
        'no' => 1.0,
        'fi' => 1.0,
        // Complex scripts
        'zh' => 1.5,
        'ja' => 1.5,
        'ko' => 1.5,
        'ar' => 1.5,
        'hi' => 1.3,
        // Other
        'ru' => 1.2,
        'pl' => 1.1,
        'tr' => 1.1
    ];
    
    public function calculatePrice(
        AudioFile $audioFile,
        Language $language,
        bool $isPriority = false,
        array $options = []
    ): Money {
        // Use actual duration if available, otherwise estimate from file size
        $durationInMinutes = $audioFile->durationInMinutes() ?? $this->estimateDuration($audioFile);
        
        $basePrice = $this->calculateBasePrice($durationInMinutes);
        $languageMultiplier = $this->getLanguageComplexityMultiplier($language);
        $priorityMultiplier = $isPriority ? self::PRIORITY_MULTIPLIER : 1.0;
        
        $finalPrice = $basePrice * $languageMultiplier * $priorityMultiplier;
        
        // Apply any additional options
        if (isset($options['discount_percentage'])) {
            $finalPrice = $this->applyDiscountToAmount($finalPrice, $options['discount_percentage']);
        }
        
        // Ensure minimum charge
        $finalPrice = max($finalPrice, self::MINIMUM_CHARGE);
        
        return Money::USD($finalPrice);
    }
    
    public function estimatePrice(
        float $durationInMinutes,
        Language $language,
        bool $isPriority = false
    ): Money {
        $basePrice = $this->calculateBasePrice($durationInMinutes);
        $languageMultiplier = $this->getLanguageComplexityMultiplier($language);
        $priorityMultiplier = $isPriority ? self::PRIORITY_MULTIPLIER : 1.0;
        
        $finalPrice = $basePrice * $languageMultiplier * $priorityMultiplier;
        $finalPrice = max($finalPrice, self::MINIMUM_CHARGE);
        
        return Money::USD($finalPrice);
    }
    
    public function getBaseRatePerMinute(): Money
    {
        return Money::USD(self::BASE_RATE_PER_MINUTE);
    }
    
    public function getPriorityMultiplier(): float
    {
        return self::PRIORITY_MULTIPLIER;
    }
    
    public function getLanguageComplexityMultiplier(Language $language): float
    {
        return self::LANGUAGE_COMPLEXITY_MULTIPLIERS[$language->code()] ?? 1.2;
    }
    
    public function applyDiscount(Money $price, float $discountPercentage): Money
    {
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and 100');
        }
        
        $discountFactor = 1 - ($discountPercentage / 100);
        return $price->multiply($discountFactor);
    }
    
    private function calculateBasePrice(float $durationInMinutes): float
    {
        // Round up to nearest minute for billing
        $billableMinutes = ceil($durationInMinutes);
        return $billableMinutes * self::BASE_RATE_PER_MINUTE;
    }
    
    private function estimateDuration(AudioFile $audioFile): float
    {
        // Rough estimation based on file size and format
        // Assuming average bitrate of 128kbps for audio
        $estimatedBitrate = 128 * 1024 / 8; // bytes per second
        $estimatedSeconds = $audioFile->size() / $estimatedBitrate;
        return $estimatedSeconds / 60;
    }
    
    private function applyDiscountToAmount(float $amount, float $discountPercentage): float
    {
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            return $amount;
        }
        
        return $amount * (1 - ($discountPercentage / 100));
    }
}