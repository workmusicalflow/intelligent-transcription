<?php

namespace Tests\Unit\Domain\Common\ValueObject;

use PHPUnit\Framework\TestCase;
use Domain\Common\ValueObject\Money;
use Domain\Common\Exception\InvalidArgumentException;

class MoneyTest extends TestCase
{
    public function testCanCreateMoney(): void
    {
        $money = new Money(10.50, 'USD');
        
        $this->assertEquals(10.50, $money->amount());
        $this->assertEquals('USD', $money->currency());
    }
    
    public function testCanCreateUsingStaticFactories(): void
    {
        $usd = Money::USD(10.50);
        $eur = Money::EUR(20.75);
        $zero = Money::zero('USD');
        
        $this->assertEquals('USD', $usd->currency());
        $this->assertEquals('EUR', $eur->currency());
        $this->assertEquals(0.0, $zero->amount());
        $this->assertTrue($zero->isZero());
    }
    
    public function testRoundsToTwoDecimalPlaces(): void
    {
        $money = Money::USD(10.999);
        
        $this->assertEquals(11.00, $money->amount());
    }
    
    public function testThrowsExceptionForNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(-10, 'USD');
    }
    
    public function testThrowsExceptionForInvalidCurrency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(10, 'XXX');
    }
    
    public function testCurrencyIsCaseInsensitive(): void
    {
        $lowercase = new Money(10, 'usd');
        $uppercase = new Money(10, 'USD');
        
        $this->assertEquals($lowercase->currency(), $uppercase->currency());
    }
    
    public function testCanAddMoney(): void
    {
        $money1 = Money::USD(10.50);
        $money2 = Money::USD(5.25);
        
        $sum = $money1->add($money2);
        
        $this->assertEquals(15.75, $sum->amount());
        // Original values should be unchanged (immutability)
        $this->assertEquals(10.50, $money1->amount());
        $this->assertEquals(5.25, $money2->amount());
    }
    
    public function testCannotAddDifferentCurrencies(): void
    {
        $usd = Money::USD(10);
        $eur = Money::EUR(10);
        
        $this->expectException(InvalidArgumentException::class);
        $usd->add($eur);
    }
    
    public function testCanSubtractMoney(): void
    {
        $money1 = Money::USD(10.50);
        $money2 = Money::USD(3.25);
        
        $difference = $money1->subtract($money2);
        
        $this->assertEquals(7.25, $difference->amount());
    }
    
    public function testCanMultiplyMoney(): void
    {
        $money = Money::USD(10.00);
        
        $doubled = $money->multiply(2);
        $halved = $money->multiply(0.5);
        
        $this->assertEquals(20.00, $doubled->amount());
        $this->assertEquals(5.00, $halved->amount());
    }
    
    public function testCanDivideMoney(): void
    {
        $money = Money::USD(10.00);
        
        $divided = $money->divide(2);
        
        $this->assertEquals(5.00, $divided->amount());
    }
    
    public function testThrowsExceptionForDivisionByZero(): void
    {
        $money = Money::USD(10.00);
        
        $this->expectException(InvalidArgumentException::class);
        $money->divide(0);
    }
    
    public function testCanCompareMoney(): void
    {
        $money1 = Money::USD(10.00);
        $money2 = Money::USD(20.00);
        $money3 = Money::USD(10.00);
        
        $this->assertTrue($money2->isGreaterThan($money1));
        $this->assertFalse($money1->isGreaterThan($money2));
        
        $this->assertTrue($money1->isLessThan($money2));
        $this->assertFalse($money2->isLessThan($money1));
        
        $this->assertTrue($money1->equals($money3));
        $this->assertFalse($money1->equals($money2));
    }
    
    public function testIsPositiveAndIsZero(): void
    {
        $positive = Money::USD(10.00);
        $zero = Money::zero();
        
        $this->assertTrue($positive->isPositive());
        $this->assertFalse($positive->isZero());
        
        $this->assertFalse($zero->isPositive());
        $this->assertTrue($zero->isZero());
    }
    
    public function testFormatting(): void
    {
        $usd = Money::USD(1234.56);
        $eur = Money::EUR(1234.56);
        $gbp = new Money(1234.56, 'GBP');
        $jpy = new Money(1234.56, 'JPY');
        
        $this->assertEquals('$1,234.56', $usd->format());
        $this->assertEquals('€1,234.56', $eur->format());
        $this->assertEquals('£1,234.56', $gbp->format());
        $this->assertEquals('¥1,234.56', $jpy->format());
        
        // Test __toString
        $this->assertEquals('$1,234.56', (string) $usd);
    }
    
    public function testToArray(): void
    {
        $money = Money::USD(10.50);
        $array = $money->toArray();
        
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('currency', $array);
        $this->assertArrayHasKey('formatted', $array);
        
        $this->assertEquals(10.50, $array['amount']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals('$10.50', $array['formatted']);
    }
}