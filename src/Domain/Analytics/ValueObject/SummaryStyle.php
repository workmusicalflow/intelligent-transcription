<?php

namespace Domain\Analytics\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;

final class SummaryStyle extends ValueObject
{
    private const BULLET_POINTS = 'bullet_points';
    private const PARAGRAPH = 'paragraph';
    private const EXECUTIVE = 'executive';
    private const DETAILED = 'detailed';
    
    private const VALID_STYLES = [
        self::BULLET_POINTS,
        self::PARAGRAPH, 
        self::EXECUTIVE,
        self::DETAILED
    ];
    
    private string $style;
    
    public function __construct(string $style)
    {
        $normalizedStyle = strtolower(trim($style));
        
        if (!in_array($normalizedStyle, self::VALID_STYLES)) {
            throw InvalidArgumentException::forInvalidValue(
                'summary style',
                $style,
                implode(', ', self::VALID_STYLES)
            );
        }
        
        $this->style = $normalizedStyle;
    }
    
    public static function BULLET_POINTS(): self
    {
        return new self(self::BULLET_POINTS);
    }
    
    public static function PARAGRAPH(): self
    {
        return new self(self::PARAGRAPH);
    }
    
    public static function EXECUTIVE(): self
    {
        return new self(self::EXECUTIVE);
    }
    
    public static function DETAILED(): self
    {
        return new self(self::DETAILED);
    }
    
    public function value(): string
    {
        return $this->style;
    }
    
    public function isBulletPoints(): bool
    {
        return $this->style === self::BULLET_POINTS;
    }
    
    public function isParagraph(): bool
    {
        return $this->style === self::PARAGRAPH;
    }
    
    public function isExecutive(): bool
    {
        return $this->style === self::EXECUTIVE;
    }
    
    public function isDetailed(): bool
    {
        return $this->style === self::DETAILED;
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }
    
    public function toArray(): array
    {
        return [
            'style' => $this->style,
            'is_bullet_points' => $this->isBulletPoints(),
            'is_paragraph' => $this->isParagraph(),
            'is_executive' => $this->isExecutive(),
            'is_detailed' => $this->isDetailed()
        ];
    }
    
    public function __toString(): string
    {
        return $this->style;
    }
    
    public static function getValidStyles(): array
    {
        return self::VALID_STYLES;
    }
}