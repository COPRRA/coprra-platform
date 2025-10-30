<?php

declare(strict_types=1);

// phpcs:disable PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext, PHPCompatibility.FunctionDeclarations.NewParamTypeDeclarations.SelfOutsideClassScopeFound

namespace App\Enums;

use App\Traits\HasStatusUtilities;

enum OrderStatus: string
{
    use HasStatusUtilities;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * Get the display name for the status.
     */
    #[\Override]
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::PROCESSING => 'قيد المعالجة',
            self::SHIPPED => 'تم الشحن',
            self::DELIVERED => 'تم التسليم',
            self::CANCELLED => 'ملغي',
            self::REFUNDED => 'مسترد',
        };
    }

    /**
     * Get the color for the status (for UI).
     */
    #[\Override]
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::SHIPPED => 'purple',
            self::DELIVERED => 'green',
            self::CANCELLED => 'red',
            self::REFUNDED => 'orange',
        };
    }

    /**
     * Get allowed status transitions.
     *
     * @return array<int, OrderStatus>
     */
    #[\Override]
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::DELIVERED => [],
            self::CANCELLED => [],
            self::REFUNDED => [],
            self::SHIPPED => [self::DELIVERED],
        };
    }

    /**
     * Check if status can transition to target status.
     */
    #[\Override]
    public function canTransitionTo(self $targetStatus): bool
    {
        return \in_array($targetStatus, $this->allowedTransitions(), true);
    }

    /**
     * Get all status options as value-label pairs.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
// phpcs:enable
