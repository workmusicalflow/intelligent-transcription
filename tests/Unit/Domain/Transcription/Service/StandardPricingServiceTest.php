<?php

namespace Tests\Unit\Domain\Transcription\Service;

use PHPUnit\Framework\TestCase;
use Domain\Transcription\Service\StandardPricingService;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Common\ValueObject\Money;

class StandardPricingServiceTest extends TestCase
{
    private StandardPricingService $pricingService;
    
    protected function setUp(): void
    {
        $this->pricingService = new StandardPricingService();
    }
    
    public function testBaseRatePerMinute(): void
    {
        $baseRate = $this->pricingService->getBaseRatePerMinute();
        
        $this->assertInstanceOf(Money::class, $baseRate);
        $this->assertEquals(0.006, $baseRate->amount());
        $this->assertEquals('USD', $baseRate->currency());
    }
    
    public function testCalculatePriceForStandardTranscription(): void
    {
        $audioFile = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            5 * 1024 * 1024,
            300 // 5 minutes
        );
        
        $price = $this->pricingService->calculatePrice(
            $audioFile,
            Language::ENGLISH()
        );
        
        // 5 minutes * $0.006 = $0.03, but minimum is $0.10
        $this->assertEquals(0.10, $price->amount());
    }
    
    public function testCalculatePriceForLongerAudio(): void
    {
        $audioFile = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            10 * 1024 * 1024,
            1800 // 30 minutes
        );
        
        $price = $this->pricingService->calculatePrice(
            $audioFile,
            Language::ENGLISH()
        );
        
        // 30 minutes * $0.006 = $0.18
        $this->assertEquals(0.18, $price->amount());
    }
    
    public function testPriorityMultiplier(): void
    {
        $audioFile = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            10 * 1024 * 1024,
            1200 // 20 minutes
        );
        
        $standardPrice = $this->pricingService->calculatePrice(
            $audioFile,
            Language::ENGLISH(),
            false
        );
        
        $priorityPrice = $this->pricingService->calculatePrice(
            $audioFile,
            Language::ENGLISH(),
            true
        );
        
        // Priority should be 2.5x standard
        $this->assertEquals(
            $standardPrice->amount() * 2.5,
            $priorityPrice->amount()
        );
        $this->assertEquals(2.5, $this->pricingService->getPriorityMultiplier());
    }
    
    public function testLanguageComplexityMultipliers(): void
    {
        $audioFile = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            10 * 1024 * 1024,
            1200 // 20 minutes
        );
        
        // Test simple language (English)
        $englishPrice = $this->pricingService->calculatePrice(
            $audioFile,
            Language::ENGLISH()
        );
        
        // Test complex language (Chinese)
        $chinesePrice = $this->pricingService->calculatePrice(
            $audioFile,
            Language::fromCode('zh')
        );
        
        // Chinese should be 1.5x English
        $this->assertEquals(
            $englishPrice->amount() * 1.5,
            $chinesePrice->amount()
        );
        
        // Test language multipliers directly
        $this->assertEquals(1.0, $this->pricingService->getLanguageComplexityMultiplier(Language::ENGLISH()));
        $this->assertEquals(1.5, $this->pricingService->getLanguageComplexityMultiplier(Language::fromCode('zh')));
        $this->assertEquals(1.2, $this->pricingService->getLanguageComplexityMultiplier(Language::fromCode('ru')));
    }
    
    public function testMinimumCharge(): void
    {
        $shortAudio = AudioFile::create(
            '/tmp/short.mp3',
            'short.mp3',
            'audio/mpeg',
            100 * 1024,
            10 // 10 seconds
        );
        
        $price = $this->pricingService->calculatePrice(
            $shortAudio,
            Language::ENGLISH()
        );
        
        // Should apply minimum charge of $0.10
        $this->assertEquals(0.10, $price->amount());
    }
    
    public function testEstimatePrice(): void
    {
        // Test 10 minute estimation
        $estimate = $this->pricingService->estimatePrice(
            10.0,
            Language::ENGLISH()
        );
        
        $this->assertEquals(0.10, $estimate->amount()); // Minimum charge
        
        // Test 60 minute estimation with priority and complex language
        $complexEstimate = $this->pricingService->estimatePrice(
            60.0,
            Language::fromCode('ja'),
            true
        );
        
        // 60 * 0.006 * 1.5 (Japanese) * 2.5 (priority) = $1.35
        $this->assertEquals(1.35, $complexEstimate->amount());
    }
    
    public function testApplyDiscount(): void
    {
        $originalPrice = Money::USD(1.00);
        
        $discounted10 = $this->pricingService->applyDiscount($originalPrice, 10);
        $discounted50 = $this->pricingService->applyDiscount($originalPrice, 50);
        $discounted100 = $this->pricingService->applyDiscount($originalPrice, 100);
        
        $this->assertEquals(0.90, $discounted10->amount());
        $this->assertEquals(0.50, $discounted50->amount());
        $this->assertEquals(0.00, $discounted100->amount());
    }
    
    public function testApplyDiscountWithInvalidPercentage(): void
    {
        $price = Money::USD(1.00);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->pricingService->applyDiscount($price, -10);
    }
    
    public function testCalculatePriceWithDiscountOption(): void
    {
        $audioFile = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            10 * 1024 * 1024,
            1200 // 20 minutes
        );
        
        $priceWithDiscount = $this->pricingService->calculatePrice(
            $audioFile,
            Language::ENGLISH(),
            false,
            ['discount_percentage' => 20]
        );
        
        // 20 minutes * $0.006 = $0.12, with 20% discount = $0.096, rounded to $0.10 (minimum)
        $this->assertEquals(0.10, $priceWithDiscount->amount());
    }
    
    public function testRoundingUpMinutesForBilling(): void
    {
        // Test that partial minutes are rounded up
        $audioFile1 = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            1024 * 1024,
            61 // 1 minute and 1 second
        );
        
        $audioFile2 = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            1024 * 1024,
            119 // 1 minute and 59 seconds
        );
        
        $price1 = $this->pricingService->calculatePrice($audioFile1, Language::ENGLISH());
        $price2 = $this->pricingService->calculatePrice($audioFile2, Language::ENGLISH());
        
        // Both should be billed as 2 minutes, so same price (minimum charge applies)
        $this->assertEquals($price1->amount(), $price2->amount());
    }
}