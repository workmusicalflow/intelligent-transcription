<?php

namespace Tests\Unit\Domain\Transcription\Entity;

use PHPUnit\Framework\TestCase;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Transcription\ValueObject\YouTubeMetadata;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\Exception\InvalidTranscriptionStateException;
use Domain\Transcription\Event\TranscriptionCreated;
use Domain\Transcription\Event\TranscriptionStartedProcessing;
use Domain\Transcription\Event\TranscriptionCompleted;
use Domain\Transcription\Event\TranscriptionFailed;
use Domain\Common\ValueObject\UserId;

class TranscriptionTest extends TestCase
{
    private AudioFile $audioFile;
    private Language $language;
    private UserId $userId;
    
    protected function setUp(): void
    {
        $this->audioFile = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            1024 * 1024,
            120
        );
        $this->language = Language::FRENCH();
        $this->userId = UserId::fromInt(123);
    }
    
    public function testCanCreateTranscriptionFromFile(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $this->assertInstanceOf(Transcription::class, $transcription);
        $this->assertNotEmpty($transcription->id());
        $this->assertEquals($this->userId, $transcription->userId());
        $this->assertEquals($this->audioFile, $transcription->audioFile());
        $this->assertEquals($this->language, $transcription->language());
        $this->assertTrue($transcription->isPending());
        $this->assertFalse($transcription->isYouTubeSource());
        $this->assertNull($transcription->text());
    }
    
    public function testCanCreateTranscriptionFromYouTube(): void
    {
        $youtubeMetadata = YouTubeMetadata::create(
            'dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'Test Video',
            180
        );
        
        $transcription = Transcription::createFromYouTube(
            $this->audioFile,
            $youtubeMetadata,
            $this->language,
            $this->userId
        );
        
        $this->assertTrue($transcription->isYouTubeSource());
        $this->assertEquals($youtubeMetadata, $transcription->youtubeMetadata());
    }
    
    public function testCreationEmitsDomainEvent(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $events = $transcription->pullDomainEvents();
        
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TranscriptionCreated::class, $events[0]);
        $this->assertEquals($transcription->id(), $events[0]->aggregateId());
        
        // After pulling, events should be cleared
        $this->assertCount(0, $transcription->pullDomainEvents());
    }
    
    public function testCanStartProcessing(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->pullDomainEvents(); // Clear creation event
        
        $transcription->startProcessing('/tmp/preprocessed.wav');
        
        $this->assertTrue($transcription->isProcessing());
        $this->assertNotNull($transcription->startedAt());
        $this->assertEquals('/tmp/preprocessed.wav', $transcription->audioFile()->preprocessedPath());
        
        $events = $transcription->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TranscriptionStartedProcessing::class, $events[0]);
    }
    
    public function testCannotStartProcessingNonPendingTranscription(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->startProcessing();
        
        $this->expectException(InvalidTranscriptionStateException::class);
        $transcription->startProcessing(); // Should throw
    }
    
    public function testCanCompleteTranscription(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->startProcessing();
        $transcription->pullDomainEvents(); // Clear previous events
        
        $transcribedText = new TranscribedText('Bonjour le monde', [
            ['text' => 'Bonjour le monde', 'start' => 0.0, 'end' => 2.0]
        ]);
        
        $transcription->complete($transcribedText, ['model' => 'whisper-1']);
        
        $this->assertTrue($transcription->isCompleted());
        $this->assertEquals($transcribedText, $transcription->text());
        $this->assertNotNull($transcription->completedAt());
        $this->assertArrayHasKey('model', $transcription->metadata());
        
        $events = $transcription->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TranscriptionCompleted::class, $events[0]);
    }
    
    public function testCannotCompleteNonProcessingTranscription(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcribedText = new TranscribedText('Test');
        
        $this->expectException(InvalidTranscriptionStateException::class);
        $transcription->complete($transcribedText);
    }
    
    public function testCanFailTranscription(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->startProcessing();
        $transcription->pullDomainEvents();
        
        $transcription->fail('API error', 'API_ERROR', ['code' => 500]);
        
        $this->assertTrue($transcription->isFailed());
        $this->assertEquals('API error', $transcription->failureReason());
        $this->assertNotNull($transcription->completedAt());
        
        $events = $transcription->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TranscriptionFailed::class, $events[0]);
    }
    
    public function testCannotFailCompletedTranscription(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->startProcessing();
        $transcription->complete(new TranscribedText('Test'));
        
        $this->expectException(InvalidTranscriptionStateException::class);
        $transcription->fail('Should not work');
    }
    
    public function testCanCancelTranscription(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->cancel();
        
        $this->assertEquals(TranscriptionStatus::CANCELLED()->value(), $transcription->status()->value());
        $this->assertNotNull($transcription->completedAt());
    }
    
    public function testCanRetryFailedTranscription(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->startProcessing();
        $transcription->fail('First attempt failed');
        
        $transcription->retry();
        
        $this->assertTrue($transcription->isPending());
        $this->assertNull($transcription->text());
        $this->assertNull($transcription->failureReason());
        $this->assertNull($transcription->startedAt());
        $this->assertNull($transcription->completedAt());
    }
    
    public function testProcessingDurationCalculation(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->startProcessing();
        sleep(1); // Simulate processing time
        $transcription->complete(new TranscribedText('Test'));
        
        $duration = $transcription->processingDuration();
        
        $this->assertNotNull($duration);
        $this->assertGreaterThanOrEqual(1, $duration);
    }
    
    public function testGetPreviewText(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $transcription->startProcessing();
        $longText = str_repeat('word ', 50);
        $transcription->complete(new TranscribedText($longText));
        
        $preview = $transcription->getPreviewText(20);
        
        $this->assertEquals(23, strlen($preview)); // 20 + '...'
        $this->assertStringEndsWith('...', $preview);
    }
    
    public function testToArraySerialization(): void
    {
        $transcription = Transcription::createFromFile(
            $this->audioFile,
            $this->language,
            $this->userId
        );
        
        $array = $transcription->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('audio_file', $array);
        $this->assertArrayHasKey('language', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('is_youtube_source', $array);
        
        $this->assertEquals($transcription->id(), $array['id']);
        $this->assertEquals('123', $array['user_id']);
        $this->assertFalse($array['is_youtube_source']);
    }
}