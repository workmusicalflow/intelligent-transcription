<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\External\OpenAI;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Infrastructure\External\OpenAI\WhisperAdapter;
use Infrastructure\External\OpenAI\OpenAIClient;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Transcription\Service\TranscriptionResult;
use Domain\Common\ValueObject\Money;
use Exception;

class WhisperAdapterTest extends TestCase
{
    private WhisperAdapter $adapter;
    private MockObject|OpenAIClient $mockClient;
    private AudioFile $mockAudioFile;

    protected function setUp(): void
    {
        // Mock du client OpenAI
        $this->mockClient = $this->createMock(OpenAIClient::class);
        
        // Créer l'adapter avec injection du mock
        $this->adapter = new WhisperAdapter('test-api-key');
        $this->injectMockClient();
        
        // Mock de fichier audio
        $this->mockAudioFile = $this->createMockAudioFile();
    }

    private function injectMockClient(): void
    {
        $reflection = new \ReflectionClass($this->adapter);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->adapter, $this->mockClient);
    }

    private function createMockAudioFile(array $options = []): AudioFile
    {
        $defaults = [
            'path' => '/tmp/test.mp3',
            'originalName' => 'test.mp3',
            'mimeType' => 'audio/mpeg',
            'size' => 1024000, // 1MB
            'duration' => 120  // 2 minutes
        ];
        
        $config = array_merge($defaults, $options);
        
        $audioFile = $this->createMock(AudioFile::class);
        $audioFile->method('path')->willReturn($config['path']);
        $audioFile->method('originalName')->willReturn($config['originalName']);
        $audioFile->method('mimeType')->willReturn($config['mimeType']);
        $audioFile->method('size')->willReturn($config['size']);
        $audioFile->method('duration')->willReturn($config['duration']);
        $audioFile->method('isValid')->willReturn(true);
        
        return $audioFile;
    }

    private function createMockApiResponse(array $data = [], bool $successful = true): object
    {
        $response = new class($data, $successful) {
            public function __construct(private array $data, private bool $successful) {}
            public function isSuccessful(): bool { return $this->successful; }
            public function getData(): array { return $this->data; }
            public function getError(): string { return 'API Error'; }
        };
        
        return $response;
    }

    public function testConstruct(): void
    {
        $adapter = new WhisperAdapter('test-key', 'whisper-1');
        
        $this->assertInstanceOf(WhisperAdapter::class, $adapter);
        $this->assertEquals('OpenAI Whisper', $adapter->getName());
    }

    public function testTranscribeSuccess(): void
    {
        // Préparer les données de réponse simulées
        $responseData = [
            'text' => 'Hello world test transcription',
            'language' => 'en',
            'segments' => [
                [
                    'start' => 0.0,
                    'end' => 2.5,
                    'text' => 'Hello world',
                    'avg_logprob' => -0.5
                ],
                [
                    'start' => 2.5,
                    'end' => 5.0,
                    'text' => 'test transcription',
                    'avg_logprob' => -0.3
                ]
            ],
            'words' => [
                ['word' => 'Hello', 'start' => 0.0, 'end' => 0.5],
                ['word' => 'world', 'start' => 0.5, 'end' => 1.0]
            ]
        ];

        $mockResponse = $this->createMockApiResponse($responseData);
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with('audio/transcriptions', $this->isType('array'))
            ->willReturn($mockResponse);

        // Mock file_exists
        $this->mockFileExists(true);

        $result = $this->adapter->transcribe($this->mockAudioFile);

        $this->assertInstanceOf(TranscriptionResult::class, $result);
        $this->assertEquals('Hello world test transcription', $result->transcribedText()->content());
        $this->assertEquals('en', $result->detectedLanguage()->code());
        $this->assertGreaterThan(0, $result->confidence());
    }

    public function testTranscribeWithLanguage(): void
    {
        $responseData = [
            'text' => 'Bonjour le monde',
            'language' => 'fr',
            'segments' => [],
            'words' => []
        ];

        $mockResponse = $this->createMockApiResponse($responseData);
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with('audio/transcriptions', $this->callback(function($params) {
                return isset($params['language']) && $params['language'] === 'fr' &&
                       isset($params['prompt']) && !empty($params['prompt']);
            }))
            ->willReturn($mockResponse);

        $this->mockFileExists(true);

        $language = Language::fromCode('fr');
        $result = $this->adapter->transcribe($this->mockAudioFile, $language);

        $this->assertEquals('fr', $result->detectedLanguage()->code());
    }

    public function testTranscribeApiError(): void
    {
        $mockResponse = $this->createMockApiResponse([], false);
        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        $this->mockFileExists(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transcription failed');

        $this->adapter->transcribe($this->mockAudioFile);
    }

    public function testValidateAudioFileInvalid(): void
    {
        $invalidAudioFile = $this->createMock(AudioFile::class);
        $invalidAudioFile->method('isValid')->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid audio file');

        $this->adapter->transcribe($invalidAudioFile);
    }

    public function testValidateAudioFileTooLarge(): void
    {
        $largeAudioFile = $this->createMockAudioFile([
            'size' => 30 * 1024 * 1024 // 30MB > 25MB limit
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File too large');

        $this->adapter->transcribe($largeAudioFile);
    }

    public function testValidateAudioFileUnsupportedFormat(): void
    {
        $unsupportedAudioFile = $this->createMockAudioFile([
            'mimeType' => 'audio/unsupported'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported format');

        $this->adapter->transcribe($unsupportedAudioFile);
    }

    public function testValidateAudioFileNotFound(): void
    {
        $this->mockFileExists(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Audio file not found');

        $this->adapter->transcribe($this->mockAudioFile);
    }

    public function testEstimateCost(): void
    {
        $cost = $this->adapter->estimateCost($this->mockAudioFile);

        $this->assertInstanceOf(Money::class, $cost);
        $this->assertEquals('USD', $cost->currency());
        // 2 minutes * $0.006 per minute = $0.012
        $this->assertEquals(0.012, $cost->amount());
    }

    public function testEstimateCostNoDuration(): void
    {
        $audioFile = $this->createMockAudioFile(['duration' => null]);
        $cost = $this->adapter->estimateCost($audioFile);

        $this->assertEquals(0.0, $cost->amount());
    }

    public function testGetSupportedLanguages(): void
    {
        $languages = $this->adapter->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('en', $languages);
        $this->assertContains('es', $languages);
        $this->assertGreaterThan(10, count($languages));
    }

    public function testIsLanguageSupported(): void
    {
        $frenchLanguage = Language::fromCode('fr');
        $unsupportedLanguage = Language::fromCode('xx');

        $this->assertTrue($this->adapter->isLanguageSupported($frenchLanguage));
        $this->assertFalse($this->adapter->isLanguageSupported($unsupportedLanguage));
    }

    public function testGetMaxFileSize(): void
    {
        $maxSize = $this->adapter->getMaxFileSize();

        $this->assertEquals(25 * 1024 * 1024, $maxSize); // 25MB
    }

    public function testGetSupportedFormats(): void
    {
        $formats = $this->adapter->getSupportedFormats();

        $this->assertIsArray($formats);
        $this->assertContains('audio/mpeg', $formats);
        $this->assertContains('audio/wav', $formats);
        $this->assertContains('audio/mp4', $formats);
        $this->assertGreaterThan(5, count($formats));
    }

    public function testDetectLanguage(): void
    {
        $responseData = [
            'text' => 'Detected text',
            'language' => 'fr',
            'segments' => [],
            'words' => []
        ];

        $mockResponse = $this->createMockApiResponse($responseData);
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with('audio/transcriptions', $this->callback(function($params) {
                return !isset($params['language']); // Language should not be set for detection
            }))
            ->willReturn($mockResponse);

        $this->mockFileExists(true);

        $detectedLanguage = $this->adapter->detectLanguage($this->mockAudioFile);

        $this->assertInstanceOf(Language::class, $detectedLanguage);
        $this->assertEquals('fr', $detectedLanguage->code());
    }

    public function testSupportsFormat(): void
    {
        $this->assertTrue($this->adapter->supportsFormat('audio/mpeg'));
        $this->assertTrue($this->adapter->supportsFormat('audio/wav'));
        $this->assertFalse($this->adapter->supportsFormat('video/mp4'));
        $this->assertFalse($this->adapter->supportsFormat('text/plain'));
    }

    public function testGetMaxDurationSupported(): void
    {
        $maxDuration = $this->adapter->getMaxDurationSupported();

        $this->assertEquals(170 * 60, $maxDuration); // 170 minutes in seconds
    }

    public function testGetMaxFileSizeSupported(): void
    {
        $maxSize = $this->adapter->getMaxFileSizeSupported();

        $this->assertEquals($this->adapter->getMaxFileSize(), $maxSize);
    }

    public function testSetAndGetOptions(): void
    {
        $originalOptions = $this->adapter->getOptions();
        
        $newOptions = [
            'temperature' => 0.2,
            'custom_option' => 'test_value'
        ];

        $this->adapter->setOptions($newOptions);
        $updatedOptions = $this->adapter->getOptions();

        // Should merge with existing options
        $this->assertEquals(0.2, $updatedOptions['temperature']);
        $this->assertEquals('test_value', $updatedOptions['custom_option']);
        $this->assertArrayHasKey('response_format', $updatedOptions);
    }

    public function testGetStats(): void
    {
        $stats = $this->adapter->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('model', $stats);
        $this->assertArrayHasKey('supported_languages', $stats);
        $this->assertArrayHasKey('supported_formats', $stats);
        $this->assertArrayHasKey('max_file_size_mb', $stats);
        $this->assertArrayHasKey('options', $stats);
        
        $this->assertEquals('whisper-1', $stats['model']);
        $this->assertEquals(25, $stats['max_file_size_mb']);
    }

    public function testGetLanguagePrompt(): void
    {
        // Test via transcription avec différentes langues
        $responseData = [
            'text' => 'Test text',
            'language' => 'fr',
            'segments' => [],
            'words' => []
        ];

        $mockResponse = $this->createMockApiResponse($responseData);
        
        // Test français
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with('audio/transcriptions', $this->callback(function($params) {
                return isset($params['prompt']) && 
                       strpos($params['prompt'], 'français') !== false;
            }))
            ->willReturn($mockResponse);

        $this->mockFileExists(true);

        $frenchLanguage = Language::fromCode('fr');
        $this->adapter->transcribe($this->mockAudioFile, $frenchLanguage);
    }

    public function testCalculateAverageConfidenceEmpty(): void
    {
        // Test avec segments vides
        $responseData = [
            'text' => 'Test text',
            'language' => 'en',
            'segments' => [], // Pas de segments
            'words' => []
        ];

        $mockResponse = $this->createMockApiResponse($responseData);
        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        $this->mockFileExists(true);

        $result = $this->adapter->transcribe($this->mockAudioFile);

        $this->assertEquals(0.0, $result->confidence());
    }

    public function testCalculateAverageConfidenceWithSegments(): void
    {
        $responseData = [
            'text' => 'Test text',
            'language' => 'en',
            'segments' => [
                ['avg_logprob' => -0.5], // exp(-0.5) ≈ 0.607
                ['avg_logprob' => -0.3], // exp(-0.3) ≈ 0.741
                ['no_logprob' => true]   // Ce segment sera ignoré
            ],
            'words' => []
        ];

        $mockResponse = $this->createMockApiResponse($responseData);
        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        $this->mockFileExists(true);

        $result = $this->adapter->transcribe($this->mockAudioFile);

        // Moyenne de exp(-0.5) et exp(-0.3)
        $expectedConfidence = (exp(-0.5) + exp(-0.3)) / 2;
        $this->assertEqualsWithDelta($expectedConfidence, $result->confidence(), 0.01);
    }

    public function testPrepareFileForUpload(): void
    {
        // Test via transcription - la méthode est privée
        $responseData = [
            'text' => 'Test',
            'language' => 'en',
            'segments' => [],
            'words' => []
        ];

        $mockResponse = $this->createMockApiResponse($responseData);
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with('audio/transcriptions', $this->callback(function($params) {
                return isset($params['file']) && $params['file'] === '/tmp/test.mp3';
            }))
            ->willReturn($mockResponse);

        $this->mockFileExists(true);

        $this->adapter->transcribe($this->mockAudioFile);
    }

    public function testGetName(): void
    {
        $this->assertEquals('OpenAI Whisper', $this->adapter->getName());
    }

    private function mockFileExists(bool $exists): void
    {
        // Note: Dans un test réel, on utiliserait vfsStream ou un autre système de fichiers virtuel
        // Ici on assume que le fichier existe pour simplifier
        if (!$exists) {
            $mockAudioFile = $this->createMockAudioFile([
                'path' => '/nonexistent/path.mp3'
            ]);
            $this->mockAudioFile = $mockAudioFile;
        }
    }

    public function testTranscribeThrowsExceptionOnClientError(): void
    {
        $this->mockClient->expects($this->once())
            ->method('post')
            ->willThrowException(new Exception('Client error'));

        $this->mockFileExists(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Transcription failed');

        $this->adapter->transcribe($this->mockAudioFile);
    }

    public function testDefaultModelIsWhisper1(): void
    {
        $responseData = [
            'text' => 'Test',
            'language' => 'en',
            'segments' => [],
            'words' => []
        ];

        $mockResponse = $this->createMockApiResponse($responseData);
        $this->mockClient->expects($this->once())
            ->method('post')
            ->with('audio/transcriptions', $this->callback(function($params) {
                return $params['model'] === 'whisper-1';
            }))
            ->willReturn($mockResponse);

        $this->mockFileExists(true);

        $this->adapter->transcribe($this->mockAudioFile);
    }

    public function testCustomModelInConstructor(): void
    {
        $customAdapter = new WhisperAdapter('test-key', 'whisper-2');
        
        $stats = $customAdapter->getStats();
        $this->assertEquals('whisper-2', $stats['model']);
    }
}