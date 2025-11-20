<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when service input validation fails.
 *
 * Used for invalid parameters, missing required data, or data format errors.
 */
class ValidationException extends ServiceException
{
    protected string $errorCode = 'VALIDATION_ERROR';

    public function __construct(
        string $message = 'Validation failed',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 422, $previous, $context);
    }

    /**
     * Create exception for missing required field.
     */
    public static function missingField(string $field): self
    {
        return new self(
            "Required field '{$field}' is missing",
            ['field' => $field, 'type' => 'missing_field']
        );
    }

    /**
     * Create exception for invalid field value.
     */
    public static function invalidField(string $field, mixed $value, string $reason = ''): self
    {
        $message = "Invalid value for field '{$field}'";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            $message,
            ['field' => $field, 'value' => $value, 'reason' => $reason, 'type' => 'invalid_field']
        );
    }

    /**
     * Create exception for invalid data format.
     */
    public static function invalidFormat(string $field, string $expectedFormat): self
    {
        return new self(
            "Field '{$field}' must be in format: {$expectedFormat}",
            ['field' => $field, 'expected_format' => $expectedFormat, 'type' => 'invalid_format']
        );
    }
}
