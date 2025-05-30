<?php

namespace Domain\Transcription\ValueObject;

use Domain\Common\ValueObject\Identifier;

final class TranscriptionId extends Identifier
{
    public static function fromString(string $value): static
    {
        return new self($value);
    }
    
    public static function generate(): static
    {
        return new self(uniqid('trans_', true));
    }
}