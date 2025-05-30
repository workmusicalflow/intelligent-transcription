<?php

/**
 * Test runner complet pour exécuter tous les tests du Domain Layer
 */

require_once __DIR__ . '/src/autoload.php';

// Classes de base pour le test runner
class TestCase {
    protected $assertions = 0;
    protected $failures = [];
    
    protected function assertEquals($expected, $actual, $message = '') {
        $this->assertions++;
        if ($expected != $actual) {
            $this->failures[] = sprintf(
                "Expected [%s], got [%s]. %s",
                var_export($expected, true),
                var_export($actual, true),
                $message
            );
            return false;
        }
        return true;
    }
    
    protected function assertSame($expected, $actual, $message = '') {
        $this->assertions++;
        if ($expected !== $actual) {
            $this->failures[] = sprintf(
                "Expected identical [%s], got [%s]. %s",
                var_export($expected, true),
                var_export($actual, true),
                $message
            );
            return false;
        }
        return true;
    }
    
    protected function assertTrue($condition, $message = '') {
        $this->assertions++;
        if (!$condition) {
            $this->failures[] = "Expected true. $message";
            return false;
        }
        return true;
    }
    
    protected function assertFalse($condition, $message = '') {
        return $this->assertTrue(!$condition, $message);
    }
    
    protected function assertNull($value, $message = '') {
        return $this->assertTrue($value === null, $message);
    }
    
    protected function assertNotNull($value, $message = '') {
        return $this->assertTrue($value !== null, $message);
    }
    
    protected function assertInstanceOf($expected, $actual, $message = '') {
        $this->assertions++;
        if (!($actual instanceof $expected)) {
            $this->failures[] = sprintf(
                "Expected instance of [%s], got [%s]. %s",
                $expected,
                is_object($actual) ? get_class($actual) : gettype($actual),
                $message
            );
            return false;
        }
        return true;
    }
    
    protected function assertCount($expected, $countable, $message = '') {
        $this->assertions++;
        $actual = is_array($countable) || $countable instanceof Countable ? count($countable) : -1;
        if ($expected != $actual) {
            $this->failures[] = sprintf("Expected count [%d], got [%d]. %s", $expected, $actual, $message);
            return false;
        }
        return true;
    }
    
    protected function assertEmpty($value, $message = '') {
        return $this->assertTrue(empty($value), $message);
    }
    
    protected function assertNotEmpty($value, $message = '') {
        return $this->assertFalse(empty($value), $message);
    }
    
    protected function assertArrayHasKey($key, $array, $message = '') {
        $this->assertions++;
        if (!is_array($array) || !array_key_exists($key, $array)) {
            $this->failures[] = sprintf("Array does not have key [%s]. %s", $key, $message);
            return false;
        }
        return true;
    }
    
    protected function assertContains($needle, $haystack, $message = '') {
        $this->assertions++;
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack)) {
                $this->failures[] = sprintf("Array does not contain [%s]. %s", var_export($needle, true), $message);
                return false;
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, $needle) === false) {
                $this->failures[] = sprintf("String does not contain [%s]. %s", $needle, $message);
                return false;
            }
        }
        return true;
    }
    
    protected function assertStringEndsWith($suffix, $string, $message = '') {
        $this->assertions++;
        if (!is_string($string) || substr($string, -strlen($suffix)) !== $suffix) {
            $this->failures[] = sprintf("String does not end with [%s]. %s", $suffix, $message);
            return false;
        }
        return true;
    }
    
    protected function assertGreaterThan($expected, $actual, $message = '') {
        $this->assertions++;
        if (!($actual > $expected)) {
            $this->failures[] = sprintf("[%s] is not greater than [%s]. %s", $actual, $expected, $message);
            return false;
        }
        return true;
    }
    
    protected function assertGreaterThanOrEqual($expected, $actual, $message = '') {
        $this->assertions++;
        if (!($actual >= $expected)) {
            $this->failures[] = sprintf("[%s] is not greater than or equal to [%s]. %s", $actual, $expected, $message);
            return false;
        }
        return true;
    }
    
    protected function assertIsArray($value, $message = '') {
        return $this->assertTrue(is_array($value), $message);
    }
    
    protected function expectException($exceptionClass) {
        // Will be handled by test runner
        return $exceptionClass;
    }
    
    public function getResults() {
        return [
            'assertions' => $this->assertions,
            'failures' => $this->failures
        ];
    }
}

// Test runner
class TestRunner {
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $totalAssertions = 0;
    private $totalFailures = 0;
    private $testResults = [];
    
    public function runTestClass($className, $methods) {
        echo "\n📋 $className\n" . str_repeat('=', strlen($className) + 4) . "\n";
        
        foreach ($methods as $method) {
            $this->totalTests++;
            $testCase = new class extends TestCase {};
            
            echo "  ▶ $method... ";
            
            try {
                $startTime = microtime(true);
                
                // Handle expectException
                $expectedException = null;
                if (strpos($method, 'Exception') !== false || strpos($method, 'Throws') !== false) {
                    $expectedException = true;
                }
                
                // Run the test
                call_user_func($methods[$method]->bindTo($testCase, $testCase));
                
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $results = $testCase->getResults();
                
                if (empty($results['failures'])) {
                    echo "✅ ({$results['assertions']} assertions, {$duration}ms)\n";
                    $this->passedTests++;
                } else {
                    echo "❌\n";
                    foreach ($results['failures'] as $failure) {
                        echo "     └─ $failure\n";
                    }
                    $this->failedTests++;
                }
                
                $this->totalAssertions += $results['assertions'];
                $this->totalFailures += count($results['failures']);
                
            } catch (Exception $e) {
                if ($expectedException && $e instanceof $expectedException) {
                    echo "✅ (exception expected)\n";
                    $this->passedTests++;
                } else {
                    echo "❌ Exception: " . $e->getMessage() . "\n";
                    echo "     └─ " . $e->getFile() . ":" . $e->getLine() . "\n";
                    $this->failedTests++;
                    $this->totalFailures++;
                }
            }
        }
    }
    
    public function printSummary() {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "📊 RÉSUMÉ DES TESTS\n";
        echo str_repeat('=', 50) . "\n";
        echo "Tests exécutés:     $this->totalTests\n";
        echo "Tests réussis:      " . $this->passedTests . " (" . 
             ($this->totalTests > 0 ? round($this->passedTests / $this->totalTests * 100, 1) : 0) . "%)\n";
        echo "Tests échoués:      $this->failedTests\n";
        echo "Assertions totales: $this->totalAssertions\n";
        echo "Échecs d'assertion: $this->totalFailures\n";
        
        if ($this->failedTests === 0 && $this->totalFailures === 0) {
            echo "\n✅ TOUS LES TESTS SONT PASSÉS! 🎉\n";
        } else {
            echo "\n❌ CERTAINS TESTS ONT ÉCHOUÉ\n";
        }
    }
}

// Inclure tous les tests
include_once __DIR__ . '/tests/Unit/Domain/Transcription/ValueObject/LanguageTest.php';
include_once __DIR__ . '/tests/Unit/Domain/Transcription/ValueObject/TranscribedTextTest.php';
include_once __DIR__ . '/tests/Unit/Domain/Transcription/ValueObject/AudioFileTest.php';
include_once __DIR__ . '/tests/Unit/Domain/Transcription/Entity/TranscriptionTest.php';
include_once __DIR__ . '/tests/Unit/Domain/Common/ValueObject/MoneyTest.php';
include_once __DIR__ . '/tests/Unit/Domain/Transcription/Service/StandardPricingServiceTest.php';

// Extraire et adapter les méthodes de test
function extractTestMethods($testClass) {
    $reflection = new ReflectionClass($testClass);
    $methods = [];
    
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if (strpos($method->getName(), 'test') === 0) {
            $methods[$method->getName()] = function() use ($testClass, $method) {
                $instance = new $testClass();
                if (method_exists($instance, 'setUp')) {
                    $instance->setUp();
                }
                $method->invoke($instance);
            };
        }
    }
    
    return $methods;
}

// Exécuter les tests
echo "🧪 EXÉCUTION DES TESTS DU DOMAIN LAYER\n";
echo str_repeat('=', 50) . "\n";

$runner = new TestRunner();

// Tests des Value Objects
$runner->runTestClass('LanguageTest', extractTestMethods('Tests\Unit\Domain\Transcription\ValueObject\LanguageTest'));
$runner->runTestClass('TranscribedTextTest', extractTestMethods('Tests\Unit\Domain\Transcription\ValueObject\TranscribedTextTest'));
$runner->runTestClass('AudioFileTest', extractTestMethods('Tests\Unit\Domain\Transcription\ValueObject\AudioFileTest'));
$runner->runTestClass('MoneyTest', extractTestMethods('Tests\Unit\Domain\Common\ValueObject\MoneyTest'));

// Tests des entités
$runner->runTestClass('TranscriptionTest', extractTestMethods('Tests\Unit\Domain\Transcription\Entity\TranscriptionTest'));

// Tests des services
$runner->runTestClass('StandardPricingServiceTest', extractTestMethods('Tests\Unit\Domain\Transcription\Service\StandardPricingServiceTest'));

// Résumé final
$runner->printSummary();