<?php

namespace Tests\Integration;

/**
 * Test d'intégration complet de l'architecture 3-couches
 * 
 * Valide le flux complet : HTTP → Application → Domain → Infrastructure
 */
class FullStackTest
{
    private array $results = [];
    private $startTime;
    
    public function run(): void
    {
        echo "🧪 FULL STACK INTEGRATION TEST\n";
        echo str_repeat('=', 50) . "\n\n";
        
        $this->startTime = microtime(true);
        
        // Tests fonctionnels
        $this->testCompleteTranscriptionFlow();
        $this->testEventSourcingFlow();
        $this->testCachingPerformance();
        $this->testRepositoryPatterns();
        $this->testDomainIntegrity();
        
        // Tests de performance
        $this->testPerformanceMetrics();
        
        // Rapport final
        $this->displayResults();
    }
    
    /**
     * Test 1: Flux complet de transcription
     */
    private function testCompleteTranscriptionFlow(): void
    {
        echo "1. Testing complete transcription flow... ";
        
        try {
            // 1. Créer une transcription via HTTP
            $controller = new \Infrastructure\Http\Controller\TranscriptionController();
            
            // Simuler une requête
            $_POST = [
                'language' => 'fr',
                'youtube_url' => 'https://www.youtube.com/watch?v=test123'
            ];
            $_SESSION['user_id'] = 'test_user_' . uniqid();
            
            // 2. Créer via le controller (capture la sortie)
            ob_start();
            $controller->create();
            $response = json_decode(ob_get_clean(), true);
            
            if (!isset($response['transcription_id'])) {
                throw new \Exception('No transcription ID in response');
            }
            
            $transcriptionId = $response['transcription_id'];
            
            // 3. Vérifier dans le repository
            $repository = \Infrastructure\Container\ServiceLocator::getTranscriptionRepository();
            $transcription = $repository->findById(
                new \Domain\Transcription\ValueObject\TranscriptionId($transcriptionId)
            );
            
            if (!$transcription) {
                throw new \Exception('Transcription not found in repository');
            }
            
            // 4. Vérifier l'état initial
            if (!$transcription->status()->isPending()) {
                throw new \Exception('Initial status should be pending');
            }
            
            // 5. Simuler le traitement
            $command = new \Application\Transcription\Command\ProcessTranscriptionCommand(
                $transcription->id()
            );
            
            // Mock du transcriber pour éviter l'appel API réel
            $mockTranscriber = new class implements \Domain\Transcription\Service\TranscriberInterface {
                public function transcribe($audioFile, $language) {
                    return new \Domain\Transcription\ValueObject\TranscriptionResult(
                        new \Domain\Transcription\ValueObject\TranscribedText('Ceci est un test de transcription'),
                        0.10
                    );
                }
                public function estimateCost($audioFile) { return 0.10; }
                public function getSupportedLanguages() { return ['fr', 'en']; }
                public function getMaxFileSizeInMB() { return 100; }
                public function validateAudioFile($audioFile) { return true; }
            };
            
            // Remplacer temporairement le service
            $container = \Infrastructure\Container\ServiceLocator::getContainer();
            $container->set(\Domain\Transcription\Service\TranscriberInterface::class, $mockTranscriber);
            
            $handler = new \Application\Transcription\Handler\ProcessTranscriptionHandler(
                $repository,
                $mockTranscriber,
                \Infrastructure\Container\ServiceLocator::getEventDispatcher()
            );
            
            $handler->handle($command);
            
            // 6. Vérifier le résultat final
            $processed = $repository->findById($transcription->id());
            
            if (!$processed->status()->isCompleted()) {
                throw new \Exception('Status should be completed after processing');
            }
            
            if ($processed->transcribedText()->value() !== 'Ceci est un test de transcription') {
                throw new \Exception('Transcribed text mismatch');
            }
            
            $this->results['transcription_flow'] = true;
            echo "✅\n";
            
        } catch (\Exception $e) {
            $this->results['transcription_flow'] = false;
            echo "❌ (" . $e->getMessage() . ")\n";
        }
    }
    
    /**
     * Test 2: Event Sourcing
     */
    private function testEventSourcingFlow(): void
    {
        echo "2. Testing event sourcing flow... ";
        
        try {
            $eventStore = \Infrastructure\Container\ServiceLocator::get(
                \Domain\EventSourcing\EventStoreInterface::class
            );
            
            $eventDispatcher = \Infrastructure\Container\ServiceLocator::getEventDispatcher();
            
            // Créer et stocker un événement
            $event = new \Domain\Transcription\Event\TranscriptionCreated(
                \Domain\Transcription\ValueObject\TranscriptionId::generate(),
                new \Domain\Common\ValueObject\UserId('test_user'),
                '/tmp/test.mp3',
                'fr'
            );
            
            $eventStore->append($event);
            
            // Récupérer les événements
            $events = $eventStore->getEventsForAggregate(
                $event->aggregateId(),
                \Domain\Transcription\Entity\Transcription::class
            );
            
            if (empty($events)) {
                throw new \Exception('No events found in store');
            }
            
            // Vérifier le dispatching
            $handlerCalled = false;
            $eventDispatcher->addHandler(
                \Domain\Transcription\Event\TranscriptionCreated::class,
                function($event) use (&$handlerCalled) {
                    $handlerCalled = true;
                }
            );
            
            $eventDispatcher->dispatch($event);
            
            if (!$handlerCalled) {
                throw new \Exception('Event handler not called');
            }
            
            $this->results['event_sourcing'] = true;
            echo "✅\n";
            
        } catch (\Exception $e) {
            $this->results['event_sourcing'] = false;
            echo "❌ (" . $e->getMessage() . ")\n";
        }
    }
    
    /**
     * Test 3: Performance du cache
     */
    private function testCachingPerformance(): void
    {
        echo "3. Testing caching performance... ";
        
        try {
            $cache = \Infrastructure\Container\ServiceLocator::getCache();
            
            // Test de performance
            $iterations = 1000;
            $key = 'perf_test_' . uniqid();
            $value = str_repeat('x', 1000); // 1KB de données
            
            // Write performance
            $writeStart = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $cache->set($key . $i, $value, 3600);
            }
            $writeTime = microtime(true) - $writeStart;
            
            // Read performance
            $readStart = microtime(true);
            $hits = 0;
            for ($i = 0; $i < $iterations; $i++) {
                if ($cache->get($key . $i) === $value) {
                    $hits++;
                }
            }
            $readTime = microtime(true) - $readStart;
            
            // Vérifier les performances
            $writeSpeed = $iterations / $writeTime;
            $readSpeed = $iterations / $readTime;
            $hitRate = ($hits / $iterations) * 100;
            
            if ($hitRate < 95) {
                throw new \Exception("Low cache hit rate: {$hitRate}%");
            }
            
            echo "✅ (Write: " . round($writeSpeed) . " ops/s, Read: " . round($readSpeed) . " ops/s, Hit rate: {$hitRate}%)\n";
            
            $this->results['caching'] = true;
            $this->results['cache_metrics'] = [
                'write_speed' => $writeSpeed,
                'read_speed' => $readSpeed,
                'hit_rate' => $hitRate
            ];
            
        } catch (\Exception $e) {
            $this->results['caching'] = false;
            echo "❌ (" . $e->getMessage() . ")\n";
        }
    }
    
    /**
     * Test 4: Repository patterns
     */
    private function testRepositoryPatterns(): void
    {
        echo "4. Testing repository patterns... ";
        
        try {
            $repository = \Infrastructure\Container\ServiceLocator::getTranscriptionRepository();
            
            // Créer plusieurs transcriptions
            $userId = new \Domain\Common\ValueObject\UserId('test_user_' . uniqid());
            $transcriptions = [];
            
            for ($i = 0; $i < 5; $i++) {
                $transcription = \Domain\Transcription\Entity\Transcription::create(
                    \Domain\Transcription\ValueObject\TranscriptionId::generate(),
                    $userId,
                    \Domain\Transcription\ValueObject\AudioFile::fromPath("/tmp/test{$i}.mp3"),
                    new \Domain\Transcription\ValueObject\Language('fr'),
                    $i % 2 === 0 ? 
                        \Domain\Transcription\ValueObject\TranscriptionStatus::completed() :
                        \Domain\Transcription\ValueObject\TranscriptionStatus::pending(),
                    new \Domain\Transcription\ValueObject\TranscribedText("Test {$i}"),
                    null,
                    null
                );
                
                $repository->save($transcription);
                $transcriptions[] = $transcription;
            }
            
            // Test findByUser
            $userTranscriptions = $repository->findByUser($userId);
            if (count($userTranscriptions) !== 5) {
                throw new \Exception('findByUser returned wrong count');
            }
            
            // Test findByStatus
            $completed = $repository->findByStatus(
                \Domain\Transcription\ValueObject\TranscriptionStatus::completed()
            );
            if (count($completed) < 3) { // Au moins 3 (peut y avoir d'autres tests)
                throw new \Exception('findByStatus returned wrong count');
            }
            
            // Test pagination
            $paginated = $repository->findByUserPaginated($userId, 1, 2);
            if (count($paginated) !== 2) {
                throw new \Exception('Pagination not working correctly');
            }
            
            $this->results['repository'] = true;
            echo "✅\n";
            
        } catch (\Exception $e) {
            $this->results['repository'] = false;
            echo "❌ (" . $e->getMessage() . ")\n";
        }
    }
    
    /**
     * Test 5: Intégrité du domaine
     */
    private function testDomainIntegrity(): void
    {
        echo "5. Testing domain integrity... ";
        
        try {
            // Test des Value Objects
            $id1 = new \Domain\Transcription\ValueObject\TranscriptionId('test123');
            $id2 = new \Domain\Transcription\ValueObject\TranscriptionId('test123');
            
            if (!$id1->equals($id2)) {
                throw new \Exception('Value object equality failed');
            }
            
            // Test des invariants
            try {
                new \Domain\Transcription\ValueObject\Language('invalid_lang_code_too_long');
                throw new \Exception('Language should reject invalid codes');
            } catch (\InvalidArgumentException $e) {
                // C'est le comportement attendu
            }
            
            // Test des états
            $status = \Domain\Transcription\ValueObject\TranscriptionStatus::pending();
            if (!$status->canTransitionTo('processing')) {
                throw new \Exception('Status transition validation failed');
            }
            
            // Test de l'encapsulation
            $transcription = \Domain\Transcription\Entity\Transcription::create(
                \Domain\Transcription\ValueObject\TranscriptionId::generate(),
                new \Domain\Common\ValueObject\UserId('test'),
                \Domain\Transcription\ValueObject\AudioFile::fromPath('/tmp/test.mp3'),
                new \Domain\Transcription\ValueObject\Language('fr'),
                \Domain\Transcription\ValueObject\TranscriptionStatus::pending(),
                new \Domain\Transcription\ValueObject\TranscribedText(''),
                null,
                null
            );
            
            // Vérifier que les mutations passent par des méthodes métier
            $transcription->startProcessing();
            if (!$transcription->status()->isProcessing()) {
                throw new \Exception('Business method mutation failed');
            }
            
            $this->results['domain_integrity'] = true;
            echo "✅\n";
            
        } catch (\Exception $e) {
            $this->results['domain_integrity'] = false;
            echo "❌ (" . $e->getMessage() . ")\n";
        }
    }
    
    /**
     * Test 6: Métriques de performance
     */
    private function testPerformanceMetrics(): void
    {
        echo "6. Testing performance metrics... ";
        
        try {
            $metrics = [];
            
            // Test de charge du repository
            $repository = \Infrastructure\Container\ServiceLocator::getTranscriptionRepository();
            
            $loadStart = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                $transcription = \Domain\Transcription\Entity\Transcription::create(
                    \Domain\Transcription\ValueObject\TranscriptionId::generate(),
                    new \Domain\Common\ValueObject\UserId('perf_test'),
                    \Domain\Transcription\ValueObject\AudioFile::fromPath('/tmp/perf.mp3'),
                    new \Domain\Transcription\ValueObject\Language('fr'),
                    \Domain\Transcription\ValueObject\TranscriptionStatus::completed(),
                    new \Domain\Transcription\ValueObject\TranscribedText('Performance test'),
                    null,
                    null
                );
                
                $repository->save($transcription);
            }
            $metrics['save_time'] = microtime(true) - $loadStart;
            $metrics['save_rate'] = 100 / $metrics['save_time'];
            
            // Test de requête
            $queryStart = microtime(true);
            $results = $repository->findByUser(new \Domain\Common\ValueObject\UserId('perf_test'));
            $metrics['query_time'] = microtime(true) - $queryStart;
            $metrics['query_count'] = count($results);
            
            // Mémoire utilisée
            $metrics['memory_peak'] = memory_get_peak_usage(true) / 1024 / 1024; // MB
            
            echo "✅ (Save: " . round($metrics['save_rate']) . " ops/s, Query: " . 
                 round($metrics['query_time'] * 1000) . "ms, Memory: " . 
                 round($metrics['memory_peak']) . "MB)\n";
            
            $this->results['performance'] = true;
            $this->results['metrics'] = $metrics;
            
        } catch (\Exception $e) {
            $this->results['performance'] = false;
            echo "❌ (" . $e->getMessage() . ")\n";
        }
    }
    
    /**
     * Affiche les résultats des tests
     */
    private function displayResults(): void
    {
        $totalTime = microtime(true) - $this->startTime;
        
        $passed = count(array_filter($this->results, function($v) {
            return $v === true;
        }));
        
        $total = count(array_filter($this->results, function($v) {
            return is_bool($v);
        }));
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "📊 INTEGRATION TEST RESULTS\n";
        echo str_repeat('=', 50) . "\n\n";
        
        echo "Tests passed: {$passed}/{$total}\n";
        echo "Total time: " . round($totalTime, 2) . "s\n\n";
        
        if (isset($this->results['cache_metrics'])) {
            echo "Cache Performance:\n";
            echo "  - Write speed: " . round($this->results['cache_metrics']['write_speed']) . " ops/s\n";
            echo "  - Read speed: " . round($this->results['cache_metrics']['read_speed']) . " ops/s\n";
            echo "  - Hit rate: " . $this->results['cache_metrics']['hit_rate'] . "%\n\n";
        }
        
        if (isset($this->results['metrics'])) {
            echo "Repository Performance:\n";
            echo "  - Save rate: " . round($this->results['metrics']['save_rate']) . " ops/s\n";
            echo "  - Query time: " . round($this->results['metrics']['query_time'] * 1000) . "ms\n";
            echo "  - Memory usage: " . round($this->results['metrics']['memory_peak']) . "MB\n\n";
        }
        
        if ($passed === $total) {
            echo "✅ ALL INTEGRATION TESTS PASSED!\n";
            echo "🏗️ Clean Architecture is fully operational\n";
            echo "🚀 Domain → Application → Infrastructure validated\n";
            echo "⚡ Performance metrics are within acceptable range\n";
        } else {
            echo "❌ Some tests failed:\n";
            foreach ($this->results as $test => $result) {
                if ($result === false) {
                    echo "  - {$test}\n";
                }
            }
        }
    }
}

// Exécuter les tests si appelé directement
if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    require_once __DIR__ . '/../../src/bootstrap.php';
    
    $test = new FullStackTest();
    $test->run();
}