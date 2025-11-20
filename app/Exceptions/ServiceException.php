<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Base exception for all service-related errors.
 *
 * Provides a standardized way to handle errors across all service classes
 * with consistent error codes, messages, and context information.
 */
class ServiceException extends \Exception
{
    protected array $context = [];
    protected string $errorCode = 'SERVICE_ERROR';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context information about the error.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the service-specific error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Set additional context information.
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Add context information to existing context.
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    /**
     * Convert exception to array for logging or API responses.
     */
    public function toArray(): array
    {
        return [
            'error_code' => $this->getErrorCode(),
            'message' => $this->getMessage(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
}
