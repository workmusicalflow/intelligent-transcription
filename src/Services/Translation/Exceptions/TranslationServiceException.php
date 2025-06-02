<?php

namespace App\Services\Translation\Exceptions;

use Exception;

/**
 * Exception pour erreurs du service de traduction
 */
class TranslationServiceException extends Exception
{
    public const CODE_API_ERROR = 1001;
    public const CODE_VALIDATION_ERROR = 1002;
    public const CODE_TIMESTAMP_PRESERVATION_FAILED = 1003;
    public const CODE_UNSUPPORTED_LANGUAGE = 1004;
    public const CODE_RATE_LIMIT = 1005;

    public function __construct(
        string $message,
        int $code = 0,
        ?\Exception $previous = null,
        private readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function apiError(string $message, array $context = []): self
    {
        return new self($message, self::CODE_API_ERROR, null, $context);
    }

    public static function validationError(string $message, array $context = []): self
    {
        return new self($message, self::CODE_VALIDATION_ERROR, null, $context);
    }

    public static function timestampPreservationFailed(string $message, array $context = []): self
    {
        return new self($message, self::CODE_TIMESTAMP_PRESERVATION_FAILED, null, $context);
    }

    public static function unsupportedLanguage(string $language): self
    {
        return new self(
            "Language '{$language}' is not supported by this translation service",
            self::CODE_UNSUPPORTED_LANGUAGE,
            null,
            ['language' => $language]
        );
    }

    public static function rateLimitExceeded(?int $retryAfter = null): self
    {
        $message = 'Translation API rate limit exceeded';
        if ($retryAfter) {
            $message .= ", retry after {$retryAfter} seconds";
        }

        return new self(
            $message,
            self::CODE_RATE_LIMIT,
            null,
            ['retry_after' => $retryAfter]
        );
    }
}