<?php

namespace Tests;

/**
 * Adaptateur pour faire fonctionner les tests PHPUnit avec notre test runner
 */
class TestCase {
    protected function setUp(): void {}
    protected function tearDown(): void {}
    
    public function assertEquals($expected, $actual, $message = ''): void {
        if ($expected != $actual) {
            throw new \AssertionError(sprintf(
                "Failed asserting that %s matches expected %s. %s",
                var_export($actual, true),
                var_export($expected, true),
                $message
            ));
        }
    }
    
    public function assertSame($expected, $actual, $message = ''): void {
        if ($expected !== $actual) {
            throw new \AssertionError(sprintf(
                "Failed asserting that %s is identical to %s. %s",
                var_export($actual, true),
                var_export($expected, true),
                $message
            ));
        }
    }
    
    public function assertTrue($condition, $message = ''): void {
        if (!$condition) {
            throw new \AssertionError("Failed asserting that false is true. $message");
        }
    }
    
    public function assertFalse($condition, $message = ''): void {
        if ($condition) {
            throw new \AssertionError("Failed asserting that true is false. $message");
        }
    }
    
    public function assertNull($value, $message = ''): void {
        if ($value !== null) {
            throw new \AssertionError(sprintf(
                "Failed asserting that %s is null. %s",
                var_export($value, true),
                $message
            ));
        }
    }
    
    public function assertNotNull($value, $message = ''): void {
        if ($value === null) {
            throw new \AssertionError("Failed asserting that null is not null. $message");
        }
    }
    
    public function assertInstanceOf($expected, $actual, $message = ''): void {
        if (!($actual instanceof $expected)) {
            throw new \AssertionError(sprintf(
                "Failed asserting that %s is an instance of %s. %s",
                is_object($actual) ? get_class($actual) : gettype($actual),
                $expected,
                $message
            ));
        }
    }
    
    public function assertCount($expectedCount, $haystack, $message = ''): void {
        $actualCount = is_array($haystack) || $haystack instanceof \Countable ? count($haystack) : -1;
        if ($expectedCount != $actualCount) {
            throw new \AssertionError(sprintf(
                "Failed asserting that count is %d. Actual count: %d. %s",
                $expectedCount,
                $actualCount,
                $message
            ));
        }
    }
    
    public function assertEmpty($actual, $message = ''): void {
        if (!empty($actual)) {
            throw new \AssertionError(sprintf(
                "Failed asserting that %s is empty. %s",
                var_export($actual, true),
                $message
            ));
        }
    }
    
    public function assertNotEmpty($actual, $message = ''): void {
        if (empty($actual)) {
            throw new \AssertionError("Failed asserting that value is not empty. $message");
        }
    }
    
    public function assertArrayHasKey($key, $array, $message = ''): void {
        if (!is_array($array) || !array_key_exists($key, $array)) {
            throw new \AssertionError(sprintf(
                "Failed asserting that array has key '%s'. %s",
                $key,
                $message
            ));
        }
    }
    
    public function assertContains($needle, $haystack, $message = ''): void {
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack, true)) {
                throw new \AssertionError(sprintf(
                    "Failed asserting that array contains %s. %s",
                    var_export($needle, true),
                    $message
                ));
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, $needle) === false) {
                throw new \AssertionError(sprintf(
                    "Failed asserting that '%s' contains '%s'. %s",
                    $haystack,
                    $needle,
                    $message
                ));
            }
        }
    }
    
    public function assertStringEndsWith($suffix, $string, $message = ''): void {
        if (!is_string($string) || substr($string, -strlen($suffix)) !== $suffix) {
            throw new \AssertionError(sprintf(
                "Failed asserting that '%s' ends with '%s'. %s",
                $string,
                $suffix,
                $message
            ));
        }
    }
    
    public function assertGreaterThan($expected, $actual, $message = ''): void {
        if (!($actual > $expected)) {
            throw new \AssertionError(sprintf(
                "Failed asserting that %s is greater than %s. %s",
                var_export($actual, true),
                var_export($expected, true),
                $message
            ));
        }
    }
    
    public function assertGreaterThanOrEqual($expected, $actual, $message = ''): void {
        if (!($actual >= $expected)) {
            throw new \AssertionError(sprintf(
                "Failed asserting that %s is greater than or equal to %s. %s",
                var_export($actual, true),
                var_export($expected, true),
                $message
            ));
        }
    }
    
    public function assertIsArray($actual, $message = ''): void {
        if (!is_array($actual)) {
            throw new \AssertionError(sprintf(
                "Failed asserting that %s is of type array. %s",
                gettype($actual),
                $message
            ));
        }
    }
    
    public function expectException($exception): void {
        // This will be handled by the test runner
        $this->_expectedException = $exception;
    }
}

// Mock PHPUnit namespace
namespace PHPUnit\Framework;

if (!class_exists('PHPUnit\Framework\TestCase')) {
    class TestCase extends \Tests\TestCase {}
}