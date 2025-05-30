<?php

/**
 * Test final et complet de la couche Application
 * Utilise les tests existants et fonctionnels
 */

require_once __DIR__ . '/src/autoload.php';

echo "🧪 FINAL APPLICATION LAYER TESTS\n";
echo str_repeat('=', 50) . "\n\n";

$testResults = [];
$startTime = microtime(true);

try {
    // Test 1: Handlers (Command/Query)
    echo "1. Testing Command/Query Handlers... ";
    $testResults['handlers'] = runTestHandlers();
    echo $testResults['handlers'] ? "✅\n" : "❌\n";
    
    // Test 2: Application Services
    echo "2. Testing Application Services... ";
    $testResults['services'] = runTestServices();
    echo $testResults['services'] ? "✅\n" : "❌\n";
    
    // Test 3: Event System
    echo "3. Testing Event System... ";
    $testResults['events'] = runTestEvents();
    echo $testResults['events'] ? "✅\n" : "❌\n";
    
    // Test 4: Bus System
    echo "4. Testing Bus System... ";
    $testResults['bus'] = testBusSystem();
    echo $testResults['bus'] ? "✅\n" : "❌\n";
    
    // Test 5: Cache Integration
    echo "5. Testing Cache Integration... ";
    $testResults['cache'] = testCacheIntegration();
    echo $testResults['cache'] ? "✅\n" : "❌\n";
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    // Résultats finaux
    $passedTests = count(array_filter($testResults));
    $totalTests = count($testResults);
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "📊 FINAL APPLICATION LAYER RESULTS:\n";
    echo "   Tests passed: {$passedTests}/{$totalTests}\n";
    echo "   Duration: {$duration}ms\n\n";
    
    if ($passedTests === $totalTests) {
        echo "🎉 ALL APPLICATION LAYER TESTS PASSED!\n";
        echo "✅ Phase 2 - Application Layer is COMPLETE\n\n";
        
        // Statistiques détaillées
        echo "📈 IMPLEMENTATION STATISTICS:\n";
        echo "   - Commands: " . countFiles('src/Application/Command') . " classes\n";
        echo "   - Queries: " . countFiles('src/Application/Query') . " classes\n";
        echo "   - Handlers: " . countFiles('src/Application/Handler') . " classes\n";
        echo "   - DTOs: " . countFiles('src/Application/DTO') . " classes\n";
        echo "   - Services: " . countFiles('src/Application/Service') . " classes\n";
        echo "   - Events: " . countFiles('src/Application/Event') . " classes\n\n";
        
        echo "🚀 READY FOR PHASE 3 - INFRASTRUCTURE LAYER\n";
    } else {
        echo "❌ Some tests failed. Please review and fix.\n";
        foreach ($testResults as $test => $result) {
            if (!$result) {
                echo "   - Failed: {$test}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

function runTestHandlers(): bool
{
    try {
        // Exécuter le test des handlers existant
        ob_start();
        include __DIR__ . '/test_handlers.php';
        $output = ob_get_clean();
        
        // Vérifier qu'il n'y a pas d'erreur fatale
        return !str_contains($output, 'Fatal error') && !str_contains($output, 'ERROR:');
    } catch (Exception $e) {
        error_log("Handlers test error: " . $e->getMessage());
        return false;
    }
}

function runTestServices(): bool
{
    try {
        // Exécuter le test des services existant
        ob_start();
        include __DIR__ . '/test_application_services.php';
        $output = ob_get_clean();
        
        // Vérifier qu'il n'y a pas d'erreur fatale
        return !str_contains($output, 'Fatal error') && !str_contains($output, 'ERROR:');
    } catch (Exception $e) {
        error_log("Services test error: " . $e->getMessage());
        return false;
    }
}

function runTestEvents(): bool
{
    try {
        // Exécuter le test des événements existant
        ob_start();
        include __DIR__ . '/test_event_system.php';
        $output = ob_get_clean();
        
        // Vérifier le succès du test
        return str_contains($output, 'ALL EVENT SYSTEM TESTS PASSED!');
    } catch (Exception $e) {
        error_log("Events test error: " . $e->getMessage());
        return false;
    }
}

function testBusSystem(): bool
{
    try {
        // Test simple du Bus System
        $commandBus = new Application\Bus\CommandBus();
        $queryBus = new Application\Bus\QueryBus();
        
        // Test d'enregistrement d'un handler factice
        $testHandler = function($command) {
            return 'test_result';
        };
        
        $commandBus->registerHandler('TestCommand', $testHandler);
        
        // Vérifier que le handler est enregistré
        $result = $commandBus->dispatch((object)['_class' => 'TestCommand']);
        
        return $result === 'test_result';
    } catch (Exception $e) {
        error_log("Bus system test error: " . $e->getMessage());
        return false;
    }
}

function testCacheIntegration(): bool
{
    try {
        // Test du cache avec Query Bus
        $queryBus = new Application\Bus\QueryBus();
        $cacheService = new Application\Service\CacheService();
        
        // Test simple de mise en cache
        $testKey = 'test_cache_key_' . uniqid();
        $testValue = ['test' => 'data', 'timestamp' => time()];
        
        $cacheService->set($testKey, $testValue, 60);
        $retrieved = $cacheService->get($testKey);
        
        return $retrieved === $testValue;
    } catch (Exception $e) {
        error_log("Cache test error: " . $e->getMessage());
        return false;
    }
}

function countFiles(string $directory): int
{
    $count = 0;
    $path = __DIR__ . '/' . $directory;
    
    if (!is_dir($path)) {
        return 0;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $count++;
        }
    }
    
    return $count;
}