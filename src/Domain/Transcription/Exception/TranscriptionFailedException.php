<?php

namespace Domain\Transcription\Exception;

use Exception;

class TranscriptionFailedException extends TranscriptionException
{
    private ?string $errorCode;
    private ?array $context;
    
    public function __construct(
        string $message = '',
        ?string $errorCode = null,
        ?array $context = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }
    
    public static function audioTooLarge(int $size, int $maxSize): self
    {
        return new self(
            sprintf('Audio file size (%d bytes) exceeds maximum allowed size (%d bytes)', $size, $maxSize),
            'AUDIO_TOO_LARGE',
            ['size' => $size, 'max_size' => $maxSize]
        );
    }
    
    public static function unsupportedFormat(string $format, array $supportedFormats): self
    {
        return new self(
            sprintf('Audio format "%s" is not supported. Supported formats: %s', $format, implode(', ', $supportedFormats)),
            'UNSUPPORTED_FORMAT',
            ['format' => $format, 'supported_formats' => $supportedFormats]
        );
    }
    
    public static function apiError(string $service, string $message, ?string $apiErrorCode = null): self
    {
        return new self(
            sprintf('%s API error: %s', $service, $message),
            'API_ERROR',
            ['service' => $service, 'api_error_code' => $apiErrorCode]
        );
    }
    
    public static function timeout(string $service, int $timeoutSeconds): self
    {
        return new self(
            sprintf('%s transcription timed out after %d seconds', $service, $timeoutSeconds),
            'TIMEOUT',
            ['service' => $service, 'timeout_seconds' => $timeoutSeconds]
        );
    }
    
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
    
    public function getContext(): ?array
    {
        return $this->context;
    }
}