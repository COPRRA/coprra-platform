<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when external service integration fails.
 *
 * Used for API failures, network timeouts, authentication errors,
 * or any other issues with third-party services.
 */
class ExternalServiceException extends ServiceException
{
    protected string $errorCode = 'EXTERNAL_SERVICE_ERROR';

    public function __construct(
        string $message = 'External service error',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 503, $previous, $context);
    }

    /**
     * Create exception for API failures.
     */
    public static function apiFailure(string $service, int $statusCode, string $response = ''): self
    {
        return new self(
            "API call to {$service} failed with status {$statusCode}",
            [
                'service' => $service,
                'status_code' => $statusCode,
                'response' => $response,
                'type' => 'api_failure',
            ]
        );
    }

    /**
     * Create exception for network timeouts.
     */
    public static function timeout(string $service, int $timeoutSeconds): self
    {
        return new self(
            "Request to {$service} timed out after {$timeoutSeconds} seconds",
            [
                'service' => $service,
                'timeout_seconds' => $timeoutSeconds,
                'type' => 'timeout',
            ]
        );
    }

    /**
     * Create exception for authentication failures.
     */
    public static function authenticationFailed(string $service, string $reason = ''): self
    {
        $message = "Authentication failed for {$service}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            $message,
            ['service' => $service, 'reason' => $reason, 'type' => 'authentication_failed']
        );
    }

    /**
     * Create exception for rate limiting.
     */
    public static function rateLimited(string $service, int $retryAfterSeconds = 0): self
    {
        return new self(
            "Rate limited by {$service}",
            [
                'service' => $service,
                'retry_after_seconds' => $retryAfterSeconds,
                'type' => 'rate_limited',
            ]
        );
    }
}
