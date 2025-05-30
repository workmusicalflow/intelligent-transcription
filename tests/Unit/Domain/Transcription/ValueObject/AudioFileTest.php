<?php

namespace Tests\Unit\Domain\Transcription\ValueObject;

use PHPUnit\Framework\TestCase;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Common\Exception\InvalidArgumentException;

class AudioFileTest extends TestCase
{
    public function testCanCreateAudioFile(): void
    {
        $audioFile = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            1024 * 1024, // 1MB
            120.5 // 2 minutes
        );
        
        $this->assertEquals('/tmp/test.mp3', $audioFile->path());
        $this->assertEquals('test.mp3', $audioFile->originalName());
        $this->assertEquals('audio/mpeg', $audioFile->mimeType());
        $this->assertEquals(1048576, $audioFile->size());
        $this->assertEquals(1.0, $audioFile->sizeInMB());
        $this->assertEquals(120.5, $audioFile->duration());
        $this->assertEquals(2.01, $audioFile->durationInMinutes());
        $this->assertEquals('mp3', $audioFile->extension());
    }
    
    public function testThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        AudioFile::create(
            '/tmp/test.exe',
            'test.exe',
            'application/exe',
            1024
        );
    }
    
    public function testThrowsExceptionForEmptyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        AudioFile::create(
            '',
            'test.mp3',
            'audio/mpeg',
            1024
        );
    }
    
    public function testThrowsExceptionForInvalidSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            0
        );
    }
    
    public function testThrowsExceptionForFileTooLarge(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            26 * 1024 * 1024 // 26MB (over 25MB limit)
        );
    }
    
    public function testNeedsPreprocessingForSpecificFormats(): void
    {
        $mp4 = AudioFile::create('/tmp/test.mp4', 'test.mp4', 'video/mp4', 1024);
        $webm = AudioFile::create('/tmp/test.webm', 'test.webm', 'video/webm', 1024);
        $mp3 = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024);
        
        $this->assertTrue($mp4->needsPreprocessing());
        $this->assertTrue($webm->needsPreprocessing());
        $this->assertFalse($mp3->needsPreprocessing());
    }
    
    public function testCanAddPreprocessedPath(): void
    {
        $original = AudioFile::create(
            '/tmp/test.mp4',
            'test.mp4',
            'video/mp4',
            1024
        );
        
        $this->assertNull($original->preprocessedPath());
        $this->assertFalse($original->hasPreprocessedVersion());
        
        $withPreprocessed = $original->withPreprocessedPath('/tmp/preprocessed.wav');
        
        $this->assertEquals('/tmp/preprocessed.wav', $withPreprocessed->preprocessedPath());
        // Original should be unchanged (immutability)
        $this->assertNull($original->preprocessedPath());
    }
    
    public function testCanAddDuration(): void
    {
        $original = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            1024
        );
        
        $this->assertNull($original->duration());
        
        $withDuration = $original->withDuration(180.5);
        
        $this->assertEquals(180.5, $withDuration->duration());
        $this->assertEquals(3.01, $withDuration->durationInMinutes());
        // Original should be unchanged
        $this->assertNull($original->duration());
    }
    
    public function testGetSupportedFormats(): void
    {
        $formats = AudioFile::getSupportedFormats();
        
        $this->assertIsArray($formats);
        $this->assertContains('mp3', $formats);
        $this->assertContains('wav', $formats);
        $this->assertContains('mp4', $formats);
    }
    
    public function testGetMaxFileSize(): void
    {
        $maxSize = AudioFile::getMaxFileSize();
        
        $this->assertEquals(25 * 1024 * 1024, $maxSize); // 25MB
    }
    
    public function testToArrayReturnsCompleteStructure(): void
    {
        $audioFile = AudioFile::create(
            '/tmp/test.mp3',
            'test.mp3',
            'audio/mpeg',
            2 * 1024 * 1024, // 2MB
            180 // 3 minutes
        );
        
        $array = $audioFile->toArray();
        
        $this->assertArrayHasKey('path', $array);
        $this->assertArrayHasKey('original_name', $array);
        $this->assertArrayHasKey('mime_type', $array);
        $this->assertArrayHasKey('size', $array);
        $this->assertArrayHasKey('size_mb', $array);
        $this->assertArrayHasKey('duration', $array);
        $this->assertArrayHasKey('duration_minutes', $array);
        $this->assertArrayHasKey('extension', $array);
        $this->assertArrayHasKey('is_valid', $array);
        
        $this->assertEquals(2.0, $array['size_mb']);
        $this->assertEquals(3.0, $array['duration_minutes']);
    }
}