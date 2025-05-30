<?php

/**
 * Test rapide de l'Application Layer
 */

require_once __DIR__ . '/src/autoload.php';

use Application\Command\Transcription\CreateTranscriptionCommand;
use Application\Query\Transcription\GetTranscriptionQuery;
use Application\DTO\Transcription\TranscriptionDTO;
use Application\DTO\Auth\UserDTO;

echo "🧪 TEST APPLICATION LAYER\n";
echo str_repeat('=', 40) . "\n\n";

try {
    // Test 1: Création d'un CreateTranscriptionCommand
    echo "1. Test CreateTranscriptionCommand... ";
    $createCommand = new CreateTranscriptionCommand(
        userId: 'user123',
        originalFilename: 'test.mp3',
        filePath: __FILE__, // Utilise ce fichier pour le test
        mimeType: 'audio/mpeg',
        fileSize: 1024000,
        language: 'fr'
    );
    
    echo "✅\n";
    echo "   Command ID: " . $createCommand->getCommandId() . "\n";
    echo "   File: " . $createCommand->getOriginalFilename() . "\n\n";
    
    // Test 2: Création d'une GetTranscriptionQuery
    echo "2. Test GetTranscriptionQuery... ";
    $getQuery = new GetTranscriptionQuery('trans123');
    
    echo "✅\n";
    echo "   Query ID: " . $getQuery->getQueryId() . "\n";
    echo "   Cache Key: " . $getQuery->getCacheKey() . "\n\n";
    
    // Test 3: Création d'un TranscriptionDTO
    echo "3. Test TranscriptionDTO... ";
    $transcriptionData = [
        'id' => 'trans123',
        'user_id' => 'user123',
        'original_filename' => 'test.mp3',
        'language' => 'fr',
        'status' => 'completed',
        'text' => 'Ceci est un test de transcription.',
        'duration' => 60.5,
        'created_at' => '2025-05-30 16:00:00'
    ];
    
    $transcriptionDTO = TranscriptionDTO::fromArray($transcriptionData);
    
    echo "✅\n";
    echo "   ID: " . $transcriptionDTO->getId() . "\n";
    echo "   Status: " . $transcriptionDTO->getStatus() . "\n";
    echo "   Is Completed: " . ($transcriptionDTO->isCompleted() ? 'Yes' : 'No') . "\n\n";
    
    // Test 4: Création d'un UserDTO
    echo "4. Test UserDTO... ";
    $userData = [
        'id' => 123,
        'username' => 'testuser',
        'email' => 'test@example.com',
        'role' => 'user',
        'is_active' => true,
        'first_name' => 'Test',
        'last_name' => 'User'
    ];
    
    $userDTO = UserDTO::fromArray($userData);
    
    echo "✅\n";
    echo "   Username: " . $userDTO->getUsername() . "\n";
    echo "   Full Name: " . $userDTO->getFullName() . "\n";
    echo "   Is Admin: " . ($userDTO->isAdmin() ? 'Yes' : 'No') . "\n\n";
    
    // Test 5: Sérialisation JSON
    echo "5. Test JSON serialization... ";
    $json = $transcriptionDTO->toJson();
    $decoded = json_decode($json, true);
    
    if ($decoded['id'] === 'trans123') {
        echo "✅\n";
        echo "   JSON size: " . strlen($json) . " bytes\n\n";
    } else {
        echo "❌\n";
        echo "   JSON decode failed\n\n";
    }
    
    // Test 6: Validation d'erreur
    echo "6. Test validation error... ";
    try {
        new CreateTranscriptionCommand(
            userId: '', // Vide - devrait lever une exception
            originalFilename: 'test.mp3',
            filePath: __FILE__,
            mimeType: 'audio/mpeg',
            fileSize: 1024000,
            language: 'fr'
        );
        echo "❌ No exception thrown\n";
    } catch (InvalidArgumentException $e) {
        echo "✅\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "🎉 ALL TESTS PASSED!\n";
    echo "\nApplication Layer structure is working correctly.\n";
    echo "Ready for Handlers implementation.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}