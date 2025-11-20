<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when business logic rules are violated.
 *
 * Used for operations that are technically valid but violate business rules,
 * such as insufficient funds, expired offers, or unauthorized operations.
 */
class BusinessLogicException extends ServiceException
{
    protected string $errorCode = 'BUSINESS_LOGIC_ERROR';

    public function __construct(
        string $message = 'Business logic violation',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 400, $previous, $context);
    }

    /**
     * Create exception for insufficient resources.
     */
    public static function insufficientResources(string $resource, int $required, int $available): self
    {
        return new self(
            "Insufficient {$resource}: required {$required}, available {$available}",
            [
                'resource' => $resource,
                'required' => $required,
                'available' => $available,
                'type' => 'insufficient_resources',
            ]
        );
    }

    /**
     * Create exception for expired resources.
     */
    public static function expired(string $resource, string $expiredAt): self
    {
        return new self(
            "The {$resource} has expired on {$expiredAt}",
            ['resource' => $resource, 'expired_at' => $expiredAt, 'type' => 'expired']
        );
    }

    /**
     * Create exception for unauthorized operations.
     */
    public static function unauthorized(string $operation, string $reason = ''): self
    {
        $message = "Unauthorized to perform operation: {$operation}";
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(
            $message,
            ['operation' => $operation, 'reason' => $reason, 'type' => 'unauthorized']
        );
    }

    /**
     * Create exception for invalid state transitions.
     */
    public static function invalidStateTransition(string $from, string $to, string $entity = 'entity'): self
    {
        return new self(
            "Cannot transition {$entity} from '{$from}' to '{$to}'",
            [
                'entity' => $entity,
                'from_state' => $from,
                'to_state' => $to,
                'type' => 'invalid_state_transition',
            ]
        );
    }
}
