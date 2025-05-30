<?php

/**
 * Test complet de toute la couche Application
 * Couvre tous les composants de la Phase 2 : Application Layer
 */

require_once __DIR__ . '/src/autoload.php';

use Application\Command\Transcription\CreateTranscriptionCommand;
use Application\Query\Transcription\GetTranscriptionQuery;
use Application\DTO\TranscriptionDTO;
use Application\Bus\CommandBus;
use Application\Bus\QueryBus;
use Application\Handler\Command\TranscriptionCommandHandler;
use Application\Handler\Query\TranscriptionQueryHandler;
use Application\Service\TranscriptionApplicationService;
use Application\Event\Dispatcher\EventDispatcher;
use Application\Service\EventService;
use Domain\Transcription\Event\TranscriptionCreated;
use Domain\Common\ValueObject\UserId;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;

echo "🧪 TEST COMPLETE APPLICATION LAYER\n";
echo str_repeat('=', 50) . "\n\n";

$testResults = [];
$startTime = microtime(true);

try {
    // Test 1: DTOs et Interfaces
    echo "1. Testing DTOs and Interfaces... ";
    $testResults['dtos'] = testDTOsAndInterfaces();
    echo $testResults['dtos'] ? "✅\n" : "❌\n";
    
    // Test 2: Commands et Command Handlers
    echo "2. Testing Commands and Command Handlers... ";
    $testResults['commands'] = testCommandsAndHandlers();
    echo $testResults['commands'] ? "✅\n" : "❌\n";
    
    // Test 3: Queries et Query Handlers
    echo "3. Testing Queries and Query Handlers... ";
    $testResults['queries'] = testQueriesAndHandlers();
    echo $testResults['queries'] ? "✅\n" : "❌\n";
    
    // Test 4: Bus System (Command/Query Bus)
    echo "4. Testing Bus System... ";
    $testResults['bus'] = testBusSystem();
    echo $testResults['bus'] ? "✅\n" : "❌\n";
    
    // Test 5: Application Services
    echo "5. Testing Application Services... ";
    $testResults['services'] = testApplicationServices();
    echo $testResults['services'] ? "✅\n" : "❌\n";
    
    // Test 6: Event System
    echo "6. Testing Event System... ";
    $testResults['events'] = testEventSystem();
    echo $testResults['events'] ? "✅\n" : "❌\n";
    
    // Test 7: Integration Tests
    echo "7. Testing Full Integration... ";
    $testResults['integration'] = testFullIntegration();
    echo $testResults['integration'] ? "✅\n" : "❌\n";
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    // Résultats finaux
    $passedTests = count(array_filter($testResults));
    $totalTests = count($testResults);
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "📊 APPLICATION LAYER TEST RESULTS:\n";
    echo "   Tests passed: {$passedTests}/{$totalTests}\n";
    echo "   Duration: {$duration}ms\n\n";
    
    if ($passedTests === $totalTests) {
        echo "🎉 ALL APPLICATION LAYER TESTS PASSED!\n";
        echo "✅ Phase 2 - Application Layer is COMPLETE\n\n";
        echo "🚀 Ready for Phase 3 - Infrastructure Layer\n";
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

function testDTOsAndInterfaces(): bool
{
    try {
        // Test Command Interface
        $createCmd = new CreateTranscriptionCommand(
            'user_test',
            '/tmp/test.mp3',
            'fr'
        );
        
        if (!($createCmd instanceof Application\Command\CommandInterface)) {
            return false;
        }
        
        // Test Query Interface  
        $getQuery = new GetTranscriptionQuery('trans_123');
        
        if (!($getQuery instanceof Application\Query\QueryInterface)) {
            return false;
        }
        
        // Test DTOs
        $dto = new TranscriptionDTO();
        $dto->id = 'test_123';
        $dto->userId = 'user_456';
        $dto->status = 'pending';
        
        if ($dto->id !== 'test_123' || $dto->userId !== 'user_456') {
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("DTOs test error: " . $e->getMessage());
        return false;
    }
}

function testCommandsAndHandlers(): bool
{
    try {
        // Test Command Bus avec Mock Repository
        $mockRepo = new class implements Domain\Transcription\Repository\TranscriptionRepository {
            public function save(Domain\Transcription\Entity\Transcription $transcription): void {}
            public function findById(Domain\Transcription\ValueObject\TranscriptionId $id): ?Domain\Transcription\Entity\Transcription { return null; }
            public function findByUserId(Domain\Common\ValueObject\UserId $userId): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection([]);
            }
            public function matching(Domain\Common\Specification\SpecificationInterface $specification): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection([]);
            }
            public function findAll(): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection([]);
            }
            public function remove(Domain\Transcription\ValueObject\TranscriptionId $id): void {}
            public function exists(Domain\Transcription\ValueObject\TranscriptionId $id): bool { return false; }
            public function count(): int { return 0; }
        };
        
        $handler = new Application\Handler\Command\TranscriptionCommandHandler($mockRepo);
        
        $command = new Application\Command\CreateTranscriptionCommand(
            'user_test',
            '/tmp/test.mp3',
            'fr'
        );
        
        // Test sans exception
        $result = $handler->handle($command);
        
        return is_string($result) && !empty($result);
    } catch (Exception $e) {
        error_log("Commands test error: " . $e->getMessage());
        return false;
    }
}

function testQueriesAndHandlers(): bool
{
    try {
        // Mock Repository qui retourne des données de test
        $mockRepo = new class implements Domain\Transcription\Repository\TranscriptionRepository {
            public function save(Domain\Transcription\Entity\Transcription $transcription): void {}
            public function findById(Domain\Transcription\ValueObject\TranscriptionId $id): ?Domain\Transcription\Entity\Transcription {
                $userId = Domain\Common\ValueObject\UserId::fromString('user_test');
                $audioFile = Domain\Transcription\ValueObject\AudioFile::create(
                    '/tmp/test.mp3',
                    'test.mp3',
                    'audio/mpeg',
                    1000000,
                    120.0
                );
                $language = Domain\Transcription\ValueObject\Language::fromCode('fr');
                
                return Domain\Transcription\Entity\Transcription::create(
                    $id,
                    $userId,
                    $audioFile,
                    $language
                );
            }
            public function findByUserId(Domain\Common\ValueObject\UserId $userId): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection([]);
            }
            public function matching(Domain\Common\Specification\SpecificationInterface $specification): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection([]);
            }
            public function findAll(): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection([]);
            }
            public function remove(Domain\Transcription\ValueObject\TranscriptionId $id): void {}
            public function exists(Domain\Transcription\ValueObject\TranscriptionId $id): bool { return true; }
            public function count(): int { return 1; }
        };
        
        $handler = new Application\Handler\Query\TranscriptionQueryHandler($mockRepo);
        
        $query = new Application\Query\GetTranscriptionQuery('trans_test_123');
        $result = $handler->handle($query);
        
        return $result instanceof Application\DTO\TranscriptionDTO;
    } catch (Exception $e) {
        error_log("Queries test error: " . $e->getMessage());
        return false;
    }
}

function testBusSystem(): bool
{
    try {
        // Test Command Bus
        $commandBus = new Application\Bus\CommandBus();
        
        // Mock handler
        $mockHandler = function($command) {
            return 'handled_' . get_class($command);
        };
        
        $commandBus->registerHandler(Application\Command\CreateTranscriptionCommand::class, $mockHandler);
        
        $command = new Application\Command\CreateTranscriptionCommand(
            'user_test',
            '/tmp/test.mp3',
            'fr'
        );
        
        $result = $commandBus->dispatch($command);
        
        if (!str_contains($result, 'CreateTranscriptionCommand')) {
            return false;
        }
        
        // Test Query Bus
        $queryBus = new Application\Bus\QueryBus();
        
        $mockQueryHandler = function($query) {
            $dto = new Application\DTO\TranscriptionDTO();
            $dto->id = 'mocked_id';
            return $dto;
        };
        
        $queryBus->registerHandler(Application\Query\GetTranscriptionQuery::class, $mockQueryHandler);
        
        $query = new Application\Query\GetTranscriptionQuery('trans_123');
        $queryResult = $queryBus->dispatch($query);
        
        return $queryResult instanceof Application\DTO\TranscriptionDTO && $queryResult->id === 'mocked_id';
    } catch (Exception $e) {
        error_log("Bus system test error: " . $e->getMessage());
        return false;
    }
}

function testApplicationServices(): bool
{
    try {
        // Mock dependencies
        $mockCommandBus = new class {
            public function dispatch($command) { return 'service_result_' . uniqid(); }
        };
        
        $mockQueryBus = new class {
            public function dispatch($query) { 
                $dto = new Application\DTO\TranscriptionDTO();
                $dto->id = 'service_test';
                return $dto;
            }
        };
        
        $mockEventDispatcher = new class {
            public function dispatch($event) { return true; }
        };
        
        // Test TranscriptionApplicationService
        $service = new Application\Service\TranscriptionApplicationService(
            $mockCommandBus,
            $mockQueryBus,
            $mockEventDispatcher
        );
        
        $result = $service->createTranscription('user_test', '/tmp/test.mp3', 'fr');
        
        if (!is_string($result) || !str_contains($result, 'service_result_')) {
            return false;
        }
        
        // Test getTranscription
        $transcription = $service->getTranscription('trans_123');
        
        return $transcription instanceof Application\DTO\TranscriptionDTO && $transcription->id === 'service_test';
    } catch (Exception $e) {
        error_log("Application services test error: " . $e->getMessage());
        return false;
    }
}

function testEventSystem(): bool
{
    try {
        // Test EventDispatcher
        $dispatcher = new Application\Event\Dispatcher\EventDispatcher();
        
        $handlerCalled = false;
        $handler = function($event) use (&$handlerCalled) {
            $handlerCalled = true;
        };
        
        $dispatcher->subscribe(Domain\Transcription\Event\TranscriptionCreated::class, $handler);
        
        // Créer un événement de test
        $userId = Domain\Common\ValueObject\UserId::fromString('user_test');
        $audioFile = Domain\Transcription\ValueObject\AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            1000000,
            120.0
        );
        $language = Domain\Transcription\ValueObject\Language::fromCode('fr');
        
        $event = new Domain\Transcription\Event\TranscriptionCreated(
            'trans_event_test',
            $userId,
            $audioFile,
            $language
        );
        
        $dispatcher->dispatch($event);
        
        if (!$handlerCalled) {
            return false;
        }
        
        // Test EventService
        $eventService = new Application\Service\EventService($dispatcher);
        $stats = $eventService->getEventStats();
        
        return isset($stats['registered_event_types']) && $stats['registered_event_types'] > 0;
    } catch (Exception $e) {
        error_log("Event system test error: " . $e->getMessage());
        return false;
    }
}

function testFullIntegration(): bool
{
    try {
        // Test complet d'intégration : Command -> Handler -> Event -> Service
        
        // 1. Créer toutes les dépendances mockées mais fonctionnelles
        $mockRepo = new class implements Domain\Transcription\Repository\TranscriptionRepository {
            private array $storage = [];
            
            public function save(Domain\Transcription\Entity\Transcription $transcription): void {
                $this->storage[$transcription->id()->value()] = $transcription;
            }
            public function findById(Domain\Transcription\ValueObject\TranscriptionId $id): ?Domain\Transcription\Entity\Transcription {
                return $this->storage[$id->value()] ?? null;
            }
            public function findByUserId(Domain\Common\ValueObject\UserId $userId): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection([]);
            }
            public function matching(Domain\Common\Specification\SpecificationInterface $specification): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection([]);
            }
            public function findAll(): Domain\Transcription\Collection\TranscriptionCollection {
                return new Domain\Transcription\Collection\TranscriptionCollection(array_values($this->storage));
            }
            public function remove(Domain\Transcription\ValueObject\TranscriptionId $id): void {
                unset($this->storage[$id->value()]);
            }
            public function exists(Domain\Transcription\ValueObject\TranscriptionId $id): bool {
                return isset($this->storage[$id->value()]);
            }
            public function count(): int { return count($this->storage); }
        };
        
        // 2. Setup Command Bus avec vrai handler
        $commandBus = new Application\Bus\CommandBus();
        $commandHandler = new Application\Handler\Command\TranscriptionCommandHandler($mockRepo);
        $commandBus->registerHandler(Application\Command\CreateTranscriptionCommand::class, [$commandHandler, 'handle']);
        
        // 3. Setup Query Bus avec vrai handler
        $queryBus = new Application\Bus\QueryBus();
        $queryHandler = new Application\Handler\Query\TranscriptionQueryHandler($mockRepo);
        $queryBus->registerHandler(Application\Query\GetTranscriptionQuery::class, [$queryHandler, 'handle']);
        
        // 4. Setup Event System
        $eventDispatcher = new Application\Event\Dispatcher\EventDispatcher();
        $eventService = new Application\Service\EventService($eventDispatcher);
        
        // 5. Setup Application Service complet
        $appService = new Application\Service\TranscriptionApplicationService(
            $commandBus,
            $queryBus,
            $eventDispatcher
        );
        
        // 6. Test du workflow complet
        
        // Créer un fichier temporaire pour le test
        $tempFile = tempnam(sys_get_temp_dir(), 'integration_test_audio');
        file_put_contents($tempFile, 'test audio content for integration test');
        
        // Créer une transcription
        $transcriptionId = $appService->createTranscription('user_integration_test', $tempFile, 'fr');
        
        if (empty($transcriptionId)) {
            return false;
        }
        
        // Récupérer la transcription créée
        $transcription = $appService->getTranscription($transcriptionId);
        
        if (!($transcription instanceof Application\DTO\TranscriptionDTO)) {
            return false;
        }
        
        if ($transcription->id !== $transcriptionId) {
            return false;
        }
        
        if ($transcription->userId !== 'user_integration_test') {
            return false;
        }
        
        // Vérifier que les événements ont été dispatchés
        $eventStats = $eventService->getEventStats();
        if (!isset($eventStats['total_events_dispatched']) || $eventStats['total_events_dispatched'] < 1) {
            return false;
        }
        
        // Nettoyage
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Integration test error: " . $e->getMessage());
        return false;
    }
}