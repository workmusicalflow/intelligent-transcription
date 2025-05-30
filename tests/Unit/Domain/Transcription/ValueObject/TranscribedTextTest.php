<?php

namespace Tests\Unit\Domain\Transcription\ValueObject;

use PHPUnit\Framework\TestCase;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Common\Exception\InvalidArgumentException;

class TranscribedTextTest extends TestCase
{
    public function testCanCreateTranscribedTextWithContent(): void
    {
        $text = new TranscribedText('Hello world');
        
        $this->assertEquals('Hello world', $text->content());
        $this->assertEquals(2, $text->wordCount());
        $this->assertEquals(11, $text->characterCount());
    }
    
    public function testThrowsExceptionForEmptyContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TranscribedText('');
    }
    
    public function testThrowsExceptionForWhitespaceOnlyContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TranscribedText('   ');
    }
    
    public function testCanCreateWithSegments(): void
    {
        $segments = [
            ['text' => 'Hello', 'start' => 0.0, 'end' => 1.0],
            ['text' => 'world', 'start' => 1.0, 'end' => 2.0]
        ];
        
        $text = new TranscribedText('Hello world', $segments);
        
        $this->assertCount(2, $text->segments());
        $this->assertEquals(2.0, $text->duration());
    }
    
    public function testExcerptTruncatesLongText(): void
    {
        $longText = str_repeat('word ', 50);
        $text = new TranscribedText($longText);
        
        $excerpt = $text->excerpt(20);
        $this->assertEquals(23, strlen($excerpt)); // 20 chars + '...'
        $this->assertStringEndsWith('...', $excerpt);
    }
    
    public function testExcerptReturnsFullTextIfShort(): void
    {
        $text = new TranscribedText('Short text');
        
        $excerpt = $text->excerpt(20);
        $this->assertEquals('Short text', $excerpt);
    }
    
    public function testGetSegmentAtTimestamp(): void
    {
        $segments = [
            ['text' => 'First', 'start' => 0.0, 'end' => 1.0],
            ['text' => 'Second', 'start' => 1.0, 'end' => 2.5],
            ['text' => 'Third', 'start' => 2.5, 'end' => 4.0]
        ];
        
        $text = new TranscribedText('First Second Third', $segments);
        
        $segment = $text->getSegmentAt(1.5);
        $this->assertNotNull($segment);
        $this->assertEquals('Second', $segment['text']);
        
        $noSegment = $text->getSegmentAt(5.0);
        $this->assertNull($noSegment);
    }
    
    public function testGetTextBetweenTimestamps(): void
    {
        $segments = [
            ['text' => 'One', 'start' => 0.0, 'end' => 1.0],
            ['text' => 'Two', 'start' => 1.0, 'end' => 2.0],
            ['text' => 'Three', 'start' => 2.0, 'end' => 3.0],
            ['text' => 'Four', 'start' => 3.0, 'end' => 4.0]
        ];
        
        $text = new TranscribedText('One Two Three Four', $segments);
        
        $extracted = $text->getTextBetween(1.0, 3.0);
        $this->assertEquals('Two Three', $extracted);
    }
    
    public function testDurationReturnsNullWithoutSegments(): void
    {
        $text = new TranscribedText('No segments');
        
        $this->assertNull($text->duration());
        $this->assertFalse($text->hasSegments());
    }
    
    public function testValidatesSegmentStructure(): void
    {
        $invalidSegments = [
            ['invalid' => 'structure'],
            'not an array',
            ['text' => 'Missing timestamps']
        ];
        
        $text = new TranscribedText('Test', $invalidSegments);
        
        // Should filter out invalid segments
        $this->assertCount(0, $text->segments());
    }
}