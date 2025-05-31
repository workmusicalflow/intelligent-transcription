<?php

namespace Domain\Chat\ValueObject;

use Domain\Common\ValueObject\Identity;

final class ConversationId extends Identity
{
    public static function generate(): self
    {
        return new self(self::generateUuid());
    }
    
    public static function fromString(string $value): self
    {
        return new self($value);
    }
}