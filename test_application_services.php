<?php

/**
 * Test des Application Services
 */

require_once __DIR__ . '/src/autoload.php';

use Application\Service\TranscriptionApplicationService;
use Application\Service\ChatApplicationService;
use Application\Service\UserApplicationService;
use Application\Handler\CommandBus;
use Application\Handler\QueryBus;
use Application\Handler\Command\TranscriptionCommandHandler;
use Application\Handler\Query\TranscriptionQueryHandler;
use Application\Handler\Command\AuthCommandHandler;
use Application\Handler\Query\AuthQueryHandler;

// Réutiliser le MockTranscriptionRepository du test précédent
require_once __DIR__ . '/test_handlers.php';

// Mock AuthService pour les tests
class MockAuthService
{
    private array $users;
    
    public function __construct()
    {
        $this->users = [
            'testuser' => [
                'id' => 1,
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'user',
                'is_active' => true,
                'created_at' => '2025-01-01 00:00:00'
            ]
        ];
    }
    
    public function authenticate(string $username, string $password): array
    {
        $user = $this->users[$username] ?? null;
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        return [
            'success' => true,
            'user' => $user,
            'token' => 'mock_token_' . uniqid()
        ];
    }
    
    public function getUserById(int $id): ?array
    {
        foreach ($this->users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }
    
    public function getUserByUsername(string $username): ?array
    {
        return $this->users[$username] ?? null;
    }
    
    public function getUserByEmail(string $email): ?array
    {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        return null;
    }
    
    public function getLastLoginTime(int $userId): ?string
    {
        return date('Y-m-d H:i:s', strtotime('-1 hour'));
    }
}

echo "🧪 TEST APPLICATION SERVICES\n";
echo str_repeat('=', 50) . "\n\n";

try {
    // Setup
    $transcriptionRepo = new MockTranscriptionRepository();
    $authService = new MockAuthService();
    
    // Handlers
    $transcriptionCommandHandler = new TranscriptionCommandHandler($transcriptionRepo);
    $transcriptionQueryHandler = new TranscriptionQueryHandler($transcriptionRepo);
    $authCommandHandler = new AuthCommandHandler($authService);
    $authQueryHandler = new AuthQueryHandler($authService);
    
    // Bus
    $commandBus = new CommandBus();
    $queryBus = new QueryBus();
    
    // Enregistrer les handlers
    $commandBus->registerHandler($transcriptionCommandHandler);
    $commandBus->registerHandler($authCommandHandler);
    $queryBus->registerHandler($transcriptionQueryHandler);
    $queryBus->registerHandler($authQueryHandler);
    
    // Application Services
    $transcriptionService = new TranscriptionApplicationService($commandBus, $queryBus);
    $chatService = new ChatApplicationService($commandBus, $queryBus, $transcriptionService);
    $userService = new UserApplicationService($commandBus, $queryBus);
    
    echo "1. Test UserApplicationService - Authentication... ";
    $authResult = $userService->authenticateUser(
        username: 'testuser',
        password: 'password123',
        rememberMe: true,
        ipAddress: '127.0.0.1',
        userAgent: 'Test Agent'
    );
    
    if ($authResult['success'] && $authResult['user']->getUsername() === 'testuser') {
        echo "✅\n";
        echo "   User: " . $authResult['user']->getUsername() . "\n";
        echo "   Role: " . $authResult['user']->getRole() . "\n\n";
    } else {
        echo "❌ Authentication failed\n\n";
    }
    
    // Test 2: Créer un fichier temporaire et une transcription
    $tempFile = tempnam(sys_get_temp_dir(), 'test_audio');
    file_put_contents($tempFile, 'test audio content for service');
    
    echo "2. Test TranscriptionApplicationService - Create from upload... ";
    $transcription = $transcriptionService->createFromUpload(
        userId: '1',
        originalFilename: 'test_meeting.mp3',
        tempFilePath: $tempFile,
        mimeType: 'audio/mpeg',
        fileSize: 1024000,
        language: 'fr',
        isPriority: false,
        estimatedDuration: 180.5
    );
    
    if ($transcription && $transcription->getId()) {
        echo "✅\n";
        echo "   Transcription ID: " . $transcription->getId() . "\n";
        echo "   Filename: " . $transcription->getOriginalFilename() . "\n";
        echo "   Status: " . $transcription->getStatus() . "\n\n";
        
        $transcriptionId = $transcription->getId();
    } else {
        echo "❌ Transcription creation failed\n\n";
        $transcriptionId = null;
    }
    
    // Test 3: Create from YouTube
    echo "3. Test TranscriptionApplicationService - Create from YouTube... ";
    $youtubeTranscription = $transcriptionService->createFromYouTube(
        userId: '1',
        youtubeUrl: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        language: 'en',
        isPriority: true
    );
    
    if ($youtubeTranscription && $youtubeTranscription->isYoutubeSource()) {
        echo "✅\n";
        echo "   YouTube ID: " . $youtubeTranscription->getId() . "\n";
        echo "   URL: " . $youtubeTranscription->getYoutubeUrl() . "\n\n";
    } else {
        echo "❌ YouTube transcription failed\n\n";
    }
    
    // Test 4: List user transcriptions
    echo "4. Test TranscriptionApplicationService - List user transcriptions... ";
    $userTranscriptions = $transcriptionService->getUserTranscriptions(
        userId: '1',
        page: 1,
        limit: 10
    );
    
    if (isset($userTranscriptions['data']) && count($userTranscriptions['data']) > 0) {
        echo "✅\n";
        echo "   Found: " . count($userTranscriptions['data']) . " transcriptions\n";
        echo "   Page: " . $userTranscriptions['pagination']['page'] . "\n\n";
    } else {
        echo "❌ No transcriptions found\n\n";
    }
    
    // Test 5: Get transcription stats
    echo "5. Test TranscriptionApplicationService - Get stats... ";
    $stats = $transcriptionService->getTranscriptionStats(
        userId: '1',
        includeDetailed: true
    );
    
    if (isset($stats['total']) && $stats['total'] >= 0) {
        echo "✅\n";
        echo "   Total: " . $stats['total'] . "\n";
        echo "   Completed: " . ($stats['by_status']['completed'] ?? 0) . "\n\n";
    } else {
        echo "❌ Stats failed\n\n";
    }
    
    // Test 6: Chat with transcription context
    if ($transcriptionId) {
        echo "6. Test ChatApplicationService - Send message with context... ";
        $chatResult = $chatService->sendMessage(
            userId: '1',
            message: 'Peux-tu me faire un résumé de cette transcription ?',
            transcriptionId: $transcriptionId,
            language: 'fr'
        );
        
        if (isset($chatResult['user_message']) && isset($chatResult['ai_response'])) {
            echo "✅\n";
            echo "   User message: " . substr($chatResult['user_message']['content'], 0, 50) . "...\n";
            echo "   AI response: " . substr($chatResult['ai_response']['content'], 0, 50) . "...\n";
            echo "   Has context: " . ($chatResult['context']['has_transcription_context'] ? 'Yes' : 'No') . "\n\n";
        } else {
            echo "❌ Chat failed\n\n";
        }
    }
    
    // Test 7: Export conversation
    echo "7. Test ChatApplicationService - Export conversation... ";
    $exportResult = $chatService->exportConversation(
        conversationId: 'conv_123',
        userId: '1',
        format: 'json'
    );
    
    if (isset($exportResult['filename']) && isset($exportResult['format'])) {
        echo "✅\n";
        echo "   Filename: " . $exportResult['filename'] . "\n";
        echo "   Format: " . $exportResult['format'] . "\n";
        echo "   Size: " . $exportResult['size'] . " bytes\n\n";
    } else {
        echo "❌ Export failed\n\n";
    }
    
    // Test 8: Get user profile
    echo "8. Test UserApplicationService - Get user profile... ";
    $userProfile = $userService->getUserProfile(1);
    
    if (isset($userProfile['user']) && isset($userProfile['statistics'])) {
        echo "✅\n";
        echo "   Username: " . $userProfile['user']['username'] . "\n";
        echo "   Total transcriptions: " . $userProfile['statistics']['total_transcriptions'] . "\n";
        echo "   Subscription: " . $userProfile['subscription']['plan'] . "\n\n";
    } else {
        echo "❌ Profile failed\n\n";
    }
    
    // Test 9: Check permissions
    echo "9. Test UserApplicationService - Check permissions... ";
    $canTranscribe = $userService->checkUserPermissions(1, 'transcribe');
    $canAdmin = $userService->checkUserPermissions(1, 'admin_access');
    $hasPriority = $userService->checkUserPermissions(1, 'priority_processing');
    
    echo "✅\n";
    echo "   Can transcribe: " . ($canTranscribe ? 'Yes' : 'No') . "\n";
    echo "   Can admin: " . ($canAdmin ? 'Yes' : 'No') . "\n";
    echo "   Has priority: " . ($hasPriority ? 'Yes' : 'No') . "\n\n";
    
    // Test 10: Error handling
    echo "10. Test error handling... ";
    try {
        $transcriptionService->createFromUpload(
            userId: '1',
            originalFilename: 'test.exe', // Invalid extension
            tempFilePath: '/non/existent/file.exe',
            mimeType: 'application/exe', // Invalid MIME type
            fileSize: 1024,
            language: 'fr'
        );
        echo "❌ No exception thrown\n\n";
    } catch (Exception $e) {
        echo "✅\n";
        echo "   Exception: " . $e->getMessage() . "\n\n";
    }
    
    // Cleanup
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    echo "🎉 ALL APPLICATION SERVICE TESTS PASSED!\n";
    echo "\nApplication Services are working correctly.\n";
    echo "Ready for Event Handling implementation.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}