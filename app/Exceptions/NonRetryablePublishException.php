<?php

namespace App\Exceptions;

/**
 * Exception for non-retryable publish failures
 * These are structural/validation errors that won't be fixed by retrying
 */
class NonRetryablePublishException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Check if this is a non-retryable error based on message/context
     * Fix: Include Meta 400 validation errors
     */
    public static function isNonRetryable(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        // Check for specific non-retryable patterns
        $nonRetryablePatterns = [
            'no valid meta ad account',
            'account id resolved for draft',
            'invalid publish context',
            'missing required field',
            'object with id \'act_\' does not exist',
            'unsupported post request',
            // Fix: Meta 400 validation errors are non-retryable
            'meta api validation error (400)',
            'status must be one of the following values',
            '(#100)',  // Meta error code for parameter validation
        ];

        foreach ($nonRetryablePatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        // Check if it's already a NonRetryablePublishException
        return $exception instanceof self;
    }
}
