<?php

namespace Domain\Transcription\Exception;

class AudioPreprocessingFailedException extends TranscriptionException
{
    public static function conversionFailed(string $fromFormat, string $toFormat, string $reason): self
    {
        return new self(
            sprintf('Failed to convert audio from %s to %s: %s', $fromFormat, $toFormat, $reason)
        );
    }
    
    public static function fileTooLarge(int $size, int $maxSize): self
    {
        return new self(
            sprintf('Audio file size (%d bytes) exceeds preprocessing limit (%d bytes)', $size, $maxSize)
        );
    }
    
    public static function corruptedFile(string $filename): self
    {
        return new self(sprintf('Audio file "%s" appears to be corrupted', $filename));
    }
    
    public static function insufficientDiskSpace(): self
    {
        return new self('Insufficient disk space for audio preprocessing');
    }
}