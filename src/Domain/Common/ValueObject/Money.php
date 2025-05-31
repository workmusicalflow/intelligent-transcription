<?php

namespace Domain\Common\ValueObject;

use Domain\Common\Exception\InvalidArgumentException;

final class Money extends ValueObject
{
    private float $amount;
    private string $currency;
    
    private const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
    
    public function __construct(float $amount, string $currency)
    {
        $this->validateAmount($amount);
        $this->validateCurrency($currency);
        
        $this->amount = round($amount, 2);
        $this->currency = strtoupper($currency);
    }
    
    public static function USD(float $amount): self
    {
        return new self($amount, 'USD');
    }
    
    public static function EUR(float $amount): self
    {
        return new self($amount, 'EUR');
    }
    
    public static function zero(string $currency = 'USD'): self
    {
        return new self(0.0, $currency);
    }
    
    public static function fromAmount(float $amount, string $currency = 'USD'): self
    {
        return new self($amount, $currency);
    }
    
    private function validateAmount(float $amount): void
    {
        if ($amount < 0) {
            throw InvalidArgumentException::forInvalidValue('amount', $amount, 'positive number');
        }
    }
    
    private function validateCurrency(string $currency): void
    {
        $upperCurrency = strtoupper($currency);
        if (!in_array($upperCurrency, self::SUPPORTED_CURRENCIES)) {
            throw InvalidArgumentException::forInvalidValue(
                'currency',
                $currency,
                implode(', ', self::SUPPORTED_CURRENCIES)
            );
        }
    }
    
    public function amount(): float
    {
        return $this->amount;
    }
    
    public function currency(): string
    {
        return $this->currency;
    }
    
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }
    
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }
    
    public function multiply(float $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }
    
    public function divide(float $divisor): self
    {
        if ($divisor == 0) {
            throw InvalidArgumentException::forInvalidValue('divisor', $divisor, 'non-zero number');
        }
        return new self($this->amount / $divisor, $this->currency);
    }
    
    public function isZero(): bool
    {
        return $this->amount == 0;
    }
    
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }
    
    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }
    
    public function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this->sameValueAs($other);
    }
    
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->format()
        ];
    }
    
    public function format(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥'
        ];
        
        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';
        return $symbol . number_format($this->amount, 2);
    }
    
    public function __toString(): string
    {
        return $this->format();
    }
    
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                sprintf('Cannot operate on different currencies: %s and %s', $this->currency, $other->currency)
            );
        }
    }
}