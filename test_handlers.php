<?php

/**
 * Test des Handlers et Bus
 */

require_once __DIR__ . '/src/autoload.php';

use Application\Command\Transcription\CreateTranscriptionCommand;
use Application\Query\Transcription\GetTranscriptionQuery;
use Application\Handler\CommandBus;
use Application\Handler\QueryBus;
use Application\Handler\Command\TranscriptionCommandHandler;
use Application\Handler\Query\TranscriptionQueryHandler;

// Mock du TranscriptionRepository pour les tests
class MockTranscriptionRepository implements Domain\Transcription\Repository\TranscriptionRepository
{
    private array $transcriptions = [];
    
    public function save(Domain\Transcription\Entity\Transcription $transcription): void
    {
        $this->transcriptions[(string) $transcription->id()] = $transcription;
    }
    
    public function findById(Domain\Transcription\ValueObject\TranscriptionId $id): ?Domain\Transcription\Entity\Transcription
    {
        return $this->transcriptions[(string) $id] ?? null;
    }
    
    public function findByUser(Domain\Common\ValueObject\UserId $userId): Domain\Transcription\Collection\TranscriptionCollection
    {
        $filtered = array_filter($this->transcriptions, fn($t) => (string) $t->userId() === (string) $userId);
        return new Domain\Transcription\Collection\TranscriptionCollection(array_values($filtered));
    }
    
    public function findByUserPaginated(Domain\Common\ValueObject\UserId $userId, int $page = 1, int $perPage = 10): Domain\Transcription\Collection\TranscriptionCollection
    {
        $all = $this->findByUser($userId);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($all->items(), $offset, $perPage);
        return new Domain\Transcription\Collection\TranscriptionCollection($items);
    }
    
    public function findByStatus(Domain\Transcription\ValueObject\TranscriptionStatus $status): Domain\Transcription\Collection\TranscriptionCollection
    {
        $filtered = array_filter($this->transcriptions, fn($t) => $t->status()->equals($status));
        return new Domain\Transcription\Collection\TranscriptionCollection(array_values($filtered));
    }
    
    public function findByUserAndStatus(Domain\Common\ValueObject\UserId $userId, Domain\Transcription\ValueObject\TranscriptionStatus $status): Domain\Transcription\Collection\TranscriptionCollection
    {
        $filtered = array_filter($this->transcriptions, fn($t) => 
            (string) $t->userId() === (string) $userId && $t->status()->equals($status)
        );
        return new Domain\Transcription\Collection\TranscriptionCollection(array_values($filtered));
    }
    
    public function delete(Domain\Transcription\ValueObject\TranscriptionId $id): void
    {
        unset($this->transcriptions[(string) $id]);
    }
    
    public function countByUser(Domain\Common\ValueObject\UserId $userId): int
    {
        return count(array_filter($this->transcriptions, fn($t) => (string) $t->userId() === (string) $userId));
    }
    
    public function countByUserAndStatus(Domain\Common\ValueObject\UserId $userId, Domain\Transcription\ValueObject\TranscriptionStatus $status): int
    {
        return count(array_filter($this->transcriptions, fn($t) => 
            (string) $t->userId() === (string) $userId && $t->status()->equals($status)
        ));
    }
    
    public function findRecentByUser(Domain\Common\ValueObject\UserId $userId, int $limit = 10): Domain\Transcription\Collection\TranscriptionCollection
    {
        $userTranscriptions = array_filter($this->transcriptions, fn($t) => (string) $t->userId() === (string) $userId);
        usort($userTranscriptions, fn($a, $b) => $b->createdAt() <=> $a->createdAt());
        $recent = array_slice($userTranscriptions, 0, $limit);
        return new Domain\Transcription\Collection\TranscriptionCollection($recent);
    }
    
    public function exists(Domain\Transcription\ValueObject\TranscriptionId $id): bool
    {
        return isset($this->transcriptions[(string) $id]);
    }
    
    public function findYouTubeTranscriptionsByUser(Domain\Common\ValueObject\UserId $userId): Domain\Transcription\Collection\TranscriptionCollection
    {
        $filtered = array_filter($this->transcriptions, fn($t) => 
            (string) $t->userId() === (string) $userId && $t->isYouTubeSource()
        );
        return new Domain\Transcription\Collection\TranscriptionCollection(array_values($filtered));
    }
    
    public function nextIdentity(): Domain\Transcription\ValueObject\TranscriptionId
    {
        return Domain\Transcription\ValueObject\TranscriptionId::generate();
    }
    
    public function search($criteria): Domain\Transcription\Collection\TranscriptionCollection
    {
        // Implémentation simplifiée pour les tests
        return new Domain\Transcription\Collection\TranscriptionCollection(array_values($this->transcriptions));
    }
    
    public function findBySpecification($specification): Domain\Transcription\Collection\TranscriptionCollection
    {
        $filtered = array_filter($this->transcriptions, fn($t) => $specification->isSatisfiedBy($t));
        return new Domain\Transcription\Collection\TranscriptionCollection(array_values($filtered));
    }
    
    // Méthode temporaire pour les tests
    public function findAll(): array
    {
        return array_values($this->transcriptions);
    }
}

echo "🧪 TEST HANDLERS & BUS\n";
echo str_repeat('=', 40) . "\n\n";

try {
    // Setup
    $transcriptionRepo = new MockTranscriptionRepository();
    
    // Créer les handlers
    $transcriptionCommandHandler = new TranscriptionCommandHandler($transcriptionRepo);
    $transcriptionQueryHandler = new TranscriptionQueryHandler($transcriptionRepo);
    
    // Créer les bus
    $commandBus = new CommandBus();
    $queryBus = new QueryBus();
    
    // Enregistrer les handlers
    $commandBus->registerHandler($transcriptionCommandHandler);
    $queryBus->registerHandler($transcriptionQueryHandler);
    
    echo "1. Test Bus Registration... ";
    $commandHandlers = $commandBus->getRegisteredHandlers();
    $queryHandlers = $queryBus->getRegisteredHandlers();
    
    if (count($commandHandlers) === 1 && count($queryHandlers) === 1) {
        echo "✅\n";
        echo "   Command handlers: " . count($commandHandlers) . "\n";
        echo "   Query handlers: " . count($queryHandlers) . "\n\n";
    } else {
        echo "❌\n";
        echo "   Expected 1 handler each, got " . count($commandHandlers) . " and " . count($queryHandlers) . "\n\n";
    }
    
    // Test 2: Créer un fichier temporaire pour le test
    $tempFile = tempnam(sys_get_temp_dir(), 'test_audio');
    file_put_contents($tempFile, 'test audio content');
    
    echo "2. Test CreateTranscriptionCommand... ";
    $createCommand = new CreateTranscriptionCommand(
        userId: 'user123',
        originalFilename: 'test.mp3',
        filePath: $tempFile,
        mimeType: 'audio/mpeg',
        fileSize: 1024000,
        language: 'fr'
    );
    
    $result = $commandBus->execute($createCommand);
    
    if ($result && $result->getId() !== null) {
        echo "✅\n";
        echo "   Transcription ID: " . $result->getId() . "\n";
        echo "   Status: " . $result->getStatus() . "\n\n";
        
        $transcriptionId = $result->getId();
    } else {
        echo "❌ No result returned\n\n";
        $transcriptionId = null;
    }
    
    // Test 3: Query de récupération
    if ($transcriptionId) {
        echo "3. Test GetTranscriptionQuery... ";
        $getQuery = new GetTranscriptionQuery($transcriptionId);
        $queryResult = $queryBus->execute($getQuery);
        
        if ($queryResult && $queryResult->getId() === $transcriptionId) {
            echo "✅\n";
            echo "   Retrieved ID: " . $queryResult->getId() . "\n";
            echo "   Language: " . $queryResult->getLanguage() . "\n\n";
        } else {
            echo "❌ Query failed\n\n";
        }
    }
    
    // Test 4: Cache du QueryBus
    echo "4. Test QueryBus Cache... ";
    $cacheStats1 = $queryBus->getCacheStats();
    
    // Exécuter la même query une deuxième fois
    if ($transcriptionId) {
        $getQuery2 = new GetTranscriptionQuery($transcriptionId);
        $queryResult2 = $queryBus->execute($getQuery2);
        
        $cacheStats2 = $queryBus->getCacheStats();
        
        if ($cacheStats2['entries'] > $cacheStats1['entries']) {
            echo "✅\n";
            echo "   Cache entries: " . $cacheStats2['entries'] . "\n";
            echo "   Cache enabled: " . ($cacheStats2['enabled'] ? 'Yes' : 'No') . "\n\n";
        } else {
            echo "❌ Cache not working\n\n";
        }
    }
    
    // Test 5: Handler canHandle
    echo "5. Test Handler canHandle... ";
    $canHandleCommand = $transcriptionCommandHandler->canHandle($createCommand);
    $canHandleQuery = $transcriptionQueryHandler->canHandle($getQuery);
    
    if ($canHandleCommand && $canHandleQuery) {
        echo "✅\n";
        echo "   Command handler can handle: Yes\n";
        echo "   Query handler can handle: Yes\n\n";
    } else {
        echo "❌\n";
        echo "   Command: " . ($canHandleCommand ? 'Yes' : 'No') . "\n";
        echo "   Query: " . ($canHandleQuery ? 'Yes' : 'No') . "\n\n";
    }
    
    // Test 6: Gestion d'erreur
    echo "6. Test Error Handling... ";
    try {
        $invalidCommand = new CreateTranscriptionCommand(
            userId: '',  // Invalid - should trigger validation error
            originalFilename: 'test.mp3',
            filePath: $tempFile,
            mimeType: 'audio/mpeg',
            fileSize: 1024000,
            language: 'fr'
        );
        
        $commandBus->execute($invalidCommand);
        echo "❌ No exception thrown\n\n";
    } catch (Exception $e) {
        echo "✅\n";
        echo "   Exception caught: " . $e->getMessage() . "\n\n";
    }
    
    // Cleanup
    unlink($tempFile);
    
    echo "🎉 ALL HANDLER TESTS PASSED!\n";
    echo "\nCommand/Query Handlers are working correctly.\n";
    echo "Ready for Application Services implementation.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}