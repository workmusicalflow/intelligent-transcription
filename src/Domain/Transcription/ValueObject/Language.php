<?php

namespace Domain\Transcription\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;

final class Language extends ValueObject
{
    private const SUPPORTED_LANGUAGES = [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'ru' => 'Русский',
        'ja' => '日本語',
        'ko' => '한국어',
        'zh' => '中文',
        'ar' => 'العربية',
        'hi' => 'हिन्दी',
        'tr' => 'Türkçe',
        'sv' => 'Svenska',
        'da' => 'Dansk',
        'no' => 'Norsk',
        'fi' => 'Suomi'
    ];
    
    private string $code;
    
    public function __construct(string $code)
    {
        $normalizedCode = strtolower(trim($code));
        
        if (!isset(self::SUPPORTED_LANGUAGES[$normalizedCode])) {
            throw InvalidArgumentException::forInvalidValue(
                'language code',
                $code,
                implode(', ', array_keys(self::SUPPORTED_LANGUAGES))
            );
        }
        
        $this->code = $normalizedCode;
    }
    
    public static function fromCode(string $code): self
    {
        return new self($code);
    }
    
    public static function FRENCH(): self
    {
        return new self('fr');
    }
    
    public static function ENGLISH(): self
    {
        return new self('en');
    }
    
    public static function SPANISH(): self
    {
        return new self('es');
    }
    
    public function code(): string
    {
        return $this->code;
    }
    
    public function name(): string
    {
        return self::SUPPORTED_LANGUAGES[$this->code];
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }
    
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name()
        ];
    }
    
    public function __toString(): string
    {
        return $this->code;
    }
    
    public static function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }
    
    public function isComplexLanguage(): bool
    {
        return in_array($this->code, ['zh', 'ja', 'ar', 'ko', 'hi']);
    }
}