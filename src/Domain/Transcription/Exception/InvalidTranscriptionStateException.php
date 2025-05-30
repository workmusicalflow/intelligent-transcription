<?php

namespace Domain\Transcription\Exception;

use Domain\Transcription\ValueObject\TranscriptionStatus;

final class InvalidTranscriptionStateException extends TranscriptionException
{
    public static function cannotCompleteNonProcessingTranscription(TranscriptionStatus $currentStatus): self
    {
        return new self(
            sprintf(
                'Cannot complete transcription with status "%s". Only processing transcriptions can be completed.',
                $currentStatus->value()
            )
        );
    }
    
    public static function cannotFailCompletedTranscription(): self
    {
        return new self('Cannot mark a completed transcription as failed.');
    }
    
    public static function cannotStartProcessingNonPendingTranscription(TranscriptionStatus $currentStatus): self
    {
        return new self(
            sprintf(
                'Cannot start processing transcription with status "%s". Only pending transcriptions can start processing.',
                $currentStatus->value()
            )
        );
    }
    
    public static function invalidStatusTransition(TranscriptionStatus $from, TranscriptionStatus $to): self
    {
        return new self(
            sprintf(
                'Invalid status transition from "%s" to "%s".',
                $from->value(),
                $to->value()
            )
        );
    }
}