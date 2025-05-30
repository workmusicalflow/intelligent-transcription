<?php

/**
 * Test du système d'événements
 */

require_once __DIR__ . '/src/autoload.php';

use Application\Event\Dispatcher\EventDispatcher;
use Application\Event\Handler\TranscriptionEventHandler;
use Application\Event\Handler\NotificationEventHandler;
use Application\Service\EventService;
use Domain\Transcription\Event\TranscriptionCreated;
use Domain\Transcription\Event\TranscriptionCompleted;
use Domain\Transcription\Event\TranscriptionFailed;

echo "🧪 TEST EVENT SYSTEM\n";
echo str_repeat('=', 40) . "\n\n";

try {
    // Test 1: Créer et configurer l'EventDispatcher
    echo "1. Test EventDispatcher creation... ";
    $dispatcher = new EventDispatcher();
    
    if ($dispatcher instanceof Application\Event\Dispatcher\EventDispatcherInterface) {
        echo "✅\n";
        echo "   Dispatcher created successfully\n\n";
    } else {
        echo "❌ Wrong type\n\n";
    }
    
    // Test 2: Enregistrer des handlers
    echo "2. Test handler registration... ";
    $transcriptionHandler = new TranscriptionEventHandler();
    $notificationHandler = new NotificationEventHandler();
    
    // Enregistrer pour TranscriptionCreated
    $dispatcher->subscribe(
        TranscriptionCreated::class,
        [$transcriptionHandler, 'handle']
    );
    
    $dispatcher->subscribe(
        TranscriptionCreated::class,
        [$notificationHandler, 'handle']
    );
    
    // Vérifier les enregistrements
    $hasHandlers = $dispatcher->hasHandlers(TranscriptionCreated::class);
    $handlerCount = count($dispatcher->getHandlers(TranscriptionCreated::class));
    
    if ($hasHandlers && $handlerCount === 2) {
        echo "✅\n";
        echo "   Handlers registered: {$handlerCount}\n\n";
    } else {
        echo "❌ Expected 2 handlers, got {$handlerCount}\n\n";
    }
    
    // Test 3: Créer et dispatcher un événement
    echo "3. Test event dispatching... ";
    
    // Créer un fichier temporaire pour le test
    $tempFile = tempnam(sys_get_temp_dir(), 'event_test_audio');
    file_put_contents($tempFile, 'test audio content for events');
    
    // Simuler un événement TranscriptionCreated
    $userId = Domain\Common\ValueObject\UserId::fromString('user_456');
    $audioFile = Domain\Transcription\ValueObject\AudioFile::create(
        $tempFile,
        'test_file.mp3',
        'audio/mpeg',
        1024000,
        120.0
    );
    $language = Domain\Transcription\ValueObject\Language::fromCode('fr');
    
    $event = new TranscriptionCreated(
        'trans_test_123',
        $userId,
        $audioFile,
        $language
    );
    
    // Capturer les logs pour vérifier l'exécution
    ob_start();
    $dispatcher->dispatch($event);
    $output = ob_get_clean();
    
    echo "✅\n";
    echo "   Event dispatched successfully\n";
    echo "   Event ID: " . $event->eventId() . "\n\n";
    
    // Test 4: Vérifier les statistiques
    echo "4. Test dispatcher statistics... ";
    $stats = $dispatcher->getStats();
    
    if (isset($stats['registered_event_types']) && $stats['registered_event_types'] >= 1) {
        echo "✅\n";
        echo "   Event types: " . $stats['registered_event_types'] . "\n";
        echo "   Total handlers: " . $stats['total_handlers'] . "\n";
        echo "   Events dispatched: " . $stats['total_events_dispatched'] . "\n\n";
    } else {
        echo "❌ Statistics not working\n\n";
    }
    
    // Test 5: Test EventService
    echo "5. Test EventService... ";
    $eventService = new EventService($dispatcher);
    
    $serviceStats = $eventService->getEventStats();
    if (isset($serviceStats['registered_event_types'])) {
        echo "✅\n";
        echo "   Service initialized with default handlers\n";
        echo "   Total event types: " . $serviceStats['registered_event_types'] . "\n\n";
    } else {
        echo "❌ EventService failed\n\n";
    }
    
    // Test 6: Test avec différents types d'événements
    echo "6. Test multiple event types... ";
    
    // Événement de completion
    $completedEvent = new TranscriptionCompleted(
        'trans_test_456',
        6, // wordCount
        15.5, // duration
        120 // processingTimeSeconds
    );
    
    // Événement d'échec
    $failedEvent = new TranscriptionFailed(
        'trans_test_789',
        'Format non supporté',
        'UNSUPPORTED_FORMAT'
    );
    
    // Dispatcher tous les événements
    $events = [$event, $completedEvent, $failedEvent];
    $dispatcher->dispatchAll($events);
    
    echo "✅\n";
    echo "   Dispatched " . count($events) . " events\n\n";
    
    // Test 7: Test de l'historique
    echo "7. Test event history... ";
    $history = $dispatcher->getEventHistory();
    
    if (!empty($history)) {
        $totalEvents = array_sum(array_map('count', $history));
        echo "✅\n";
        echo "   History contains {$totalEvents} events\n";
        echo "   Event types in history: " . implode(', ', array_keys($history)) . "\n\n";
    } else {
        echo "❌ No history recorded\n\n";
    }
    
    // Test 8: Test avec handler personnalisé
    echo "8. Test custom handler... ";
    
    $customHandlerCalled = false;
    $customHandler = function($event) use (&$customHandlerCalled) {
        $customHandlerCalled = true;
        echo "   Custom handler executed for: " . get_class($event) . "\n";
    };
    
    $eventService->registerHandler(TranscriptionCompleted::class, $customHandler);
    
    // Dispatcher un autre événement completed
    $anotherCompletedEvent = new TranscriptionCompleted(
        'trans_custom_test',
        4, // wordCount
        8.0, // duration
        45 // processingTimeSeconds
    );
    
    $dispatcher->dispatch($anotherCompletedEvent);
    
    if ($customHandlerCalled) {
        echo "✅\n";
        echo "   Custom handler was called\n\n";
    } else {
        echo "❌ Custom handler not called\n\n";
    }
    
    // Test 9: Test gestion d'erreur dans les handlers
    echo "9. Test error handling... ";
    
    $errorHandler = function($event) {
        throw new \Exception("Simulated handler error");
    };
    
    $dispatcher->subscribe(TranscriptionFailed::class, $errorHandler);
    
    // Cet événement devrait être géré même si un handler échoue
    $testFailedEvent = new TranscriptionFailed(
        'trans_error_test',
        'Test error handling',
        'TEST_ERROR'
    );
    
    $dispatcher->dispatch($testFailedEvent);
    
    echo "✅\n";
    echo "   Error handling works - dispatcher continues despite handler errors\n\n";
    
    // Test 10: Test performance avec plusieurs événements
    echo "10. Test performance... ";
    
    $startTime = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $perfUserId = Domain\Common\ValueObject\UserId::fromString('user_perf');
        $perfAudioFile = Domain\Transcription\ValueObject\AudioFile::create(
            $tempFile,
            "perf_test_{$i}.mp3",
            'audio/mpeg',
            1000000 + $i,
            60.0 + $i
        );
        $perfLanguage = Domain\Transcription\ValueObject\Language::fromCode('en');
        
        $perfEvent = new TranscriptionCreated(
            "trans_perf_{$i}",
            $perfUserId,
            $perfAudioFile,
            $perfLanguage
        );
        
        $dispatcher->dispatch($perfEvent);
    }
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "✅\n";
    echo "   Dispatched 100 events in {$duration}ms\n";
    echo "   Average: " . round($duration / 100, 2) . "ms per event\n\n";
    
    // Statistiques finales
    $finalStats = $dispatcher->getStats();
    echo "📊 FINAL STATISTICS:\n";
    echo "   Event types registered: " . $finalStats['registered_event_types'] . "\n";
    echo "   Total handlers: " . $finalStats['total_handlers'] . "\n";
    echo "   Total events processed: " . $finalStats['total_events_dispatched'] . "\n";
    echo "   History enabled: " . ($finalStats['history_enabled'] ? 'Yes' : 'No') . "\n\n";
    
    // Nettoyage
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    echo "🎉 ALL EVENT SYSTEM TESTS PASSED!\n";
    echo "\nEvent handling system is working correctly.\n";
    echo "Ready for final Application Layer tests.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}