<?php

namespace Domain\Transcription\ValueObject;

use Domain\Common\ValueObject\ValueObject;
use Domain\Common\Exception\InvalidArgumentException;

final class TranscriptionStatus extends ValueObject
{
    private const PENDING = 'pending';
    private const PROCESSING = 'processing';
    private const COMPLETED = 'completed';
    private const FAILED = 'failed';
    private const CANCELLED = 'cancelled';
    
    private const VALID_STATUSES = [
        self::PENDING,
        self::PROCESSING,
        self::COMPLETED,
        self::FAILED,
        self::CANCELLED
    ];
    
    private string $status;
    
    public function __construct(string $status)
    {
        $normalizedStatus = strtolower(trim($status));
        
        if (!in_array($normalizedStatus, self::VALID_STATUSES)) {
            throw InvalidArgumentException::forInvalidValue(
                'transcription status',
                $status,
                implode(', ', self::VALID_STATUSES)
            );
        }
        
        $this->status = $normalizedStatus;
    }
    
    public static function PENDING(): self
    {
        return new self(self::PENDING);
    }
    
    public static function PROCESSING(): self
    {
        return new self(self::PROCESSING);
    }
    
    public static function COMPLETED(): self
    {
        return new self(self::COMPLETED);
    }
    
    public static function FAILED(): self
    {
        return new self(self::FAILED);
    }
    
    public static function CANCELLED(): self
    {
        return new self(self::CANCELLED);
    }
    
    public static function fromString(string $status): self
    {
        return new self($status);
    }
    
    public function value(): string
    {
        return $this->status;
    }
    
    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
    
    public function isProcessing(): bool
    {
        return $this->status === self::PROCESSING;
    }
    
    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }
    
    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }
    
    public function isCancelled(): bool
    {
        return $this->status === self::CANCELLED;
    }
    
    public function isFinished(): bool
    {
        return in_array($this->status, [self::COMPLETED, self::FAILED, self::CANCELLED]);
    }
    
    public function canTransitionTo(TranscriptionStatus $newStatus): bool
    {
        $transitions = [
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::COMPLETED, self::FAILED, self::CANCELLED],
            self::COMPLETED => [],
            self::FAILED => [self::PENDING], // Allow retry
            self::CANCELLED => [self::PENDING] // Allow restart
        ];
        
        return in_array($newStatus->value(), $transitions[$this->status] ?? []);
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }
    
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'is_pending' => $this->isPending(),
            'is_processing' => $this->isProcessing(),
            'is_completed' => $this->isCompleted(),
            'is_failed' => $this->isFailed(),
            'is_cancelled' => $this->isCancelled(),
            'is_finished' => $this->isFinished()
        ];
    }
    
    public function __toString(): string
    {
        return $this->status;
    }
    
    public static function getValidStatuses(): array
    {
        return self::VALID_STATUSES;
    }
}