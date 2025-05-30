<?php

namespace Tests\Unit\Domain\Transcription\ValueObject;

use PHPUnit\Framework\TestCase;
use Domain\Transcription\ValueObject\Language;
use Domain\Common\Exception\InvalidArgumentException;

class LanguageTest extends TestCase
{
    public function testCanCreateLanguageFromValidCode(): void
    {
        $language = Language::fromCode('fr');
        
        $this->assertInstanceOf(Language::class, $language);
        $this->assertEquals('fr', $language->code());
        $this->assertEquals('Français', $language->name());
    }
    
    public function testCanCreateLanguageUsingStaticFactories(): void
    {
        $french = Language::FRENCH();
        $english = Language::ENGLISH();
        $spanish = Language::SPANISH();
        
        $this->assertEquals('fr', $french->code());
        $this->assertEquals('en', $english->code());
        $this->assertEquals('es', $spanish->code());
    }
    
    public function testThrowsExceptionForInvalidLanguageCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Language::fromCode('invalid');
    }
    
    public function testLanguageCodesAreCaseInsensitive(): void
    {
        $lowercase = Language::fromCode('fr');
        $uppercase = Language::fromCode('FR');
        
        $this->assertEquals($lowercase->code(), $uppercase->code());
    }
    
    public function testCanIdentifyComplexLanguages(): void
    {
        $chinese = Language::fromCode('zh');
        $japanese = Language::fromCode('ja');
        $french = Language::fromCode('fr');
        
        $this->assertTrue($chinese->isComplexLanguage());
        $this->assertTrue($japanese->isComplexLanguage());
        $this->assertFalse($french->isComplexLanguage());
    }
    
    public function testLanguageEquality(): void
    {
        $lang1 = Language::fromCode('fr');
        $lang2 = Language::fromCode('fr');
        $lang3 = Language::fromCode('en');
        
        $this->assertTrue($lang1->equals($lang2));
        $this->assertFalse($lang1->equals($lang3));
    }
    
    public function testSupportedLanguagesReturnsExpectedList(): void
    {
        $supported = Language::getSupportedLanguages();
        
        $this->assertIsArray($supported);
        $this->assertArrayHasKey('fr', $supported);
        $this->assertArrayHasKey('en', $supported);
        $this->assertEquals('Français', $supported['fr']);
    }
    
    public function testToArrayReturnsExpectedStructure(): void
    {
        $language = Language::FRENCH();
        $array = $language->toArray();
        
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertEquals('fr', $array['code']);
        $this->assertEquals('Français', $array['name']);
    }
}