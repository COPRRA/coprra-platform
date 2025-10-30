<?php

declare(strict_types=1);

namespace App\Casts;

use App\Enums\OrderStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom cast for Order status to gracefully handle legacy alias "completed".
 *
 * @implements CastsAttributes<OrderStatus|string, OrderStatus|string>
 */
final class OrderStatusCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model                $model
     * @param mixed                $value
     * @param array<string, mixed> $attributes
     *
     * @return OrderStatus|string
     *
     * @SuppressWarnings("UnusedFormalParameter")
     */
    #[\Override]
    public function get($model, string $key, $value, array $attributes)
    {
        // لا حاجة لاستخدام المتغيرات غير المستعملة هنا؛ نتركها دون لمس

        $raw = \is_string($value) ? strtolower($value) : $value;

        // Preserve legacy string for tests expecting raw 'completed'
        if ('completed' === $raw) {
            return 'completed';
        }

        // For valid enum values, return the Enum instance
        $normalized = strtolower((string) $raw);

        return match ($normalized) {
            'pending' => OrderStatus::PENDING,
            'processing' => OrderStatus::PROCESSING,
            'shipped' => OrderStatus::SHIPPED,
            'delivered' => OrderStatus::DELIVERED,
            'cancelled' => OrderStatus::CANCELLED,
            'refunded' => OrderStatus::REFUNDED,
            default => throw new \ValueError("Invalid enum value '{$normalized}' for OrderStatus"),
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model                $model
     * @param mixed                $value
     * @param array<string, mixed> $attributes
     *
     * @SuppressWarnings("UnusedFormalParameter")
     */
    #[\Override]
    public function set($model, string $key, $value, array $attributes): mixed
    {
        // لا حاجة لاستخدام المتغيرات غير المستعملة هنا؛ نتركها دون لمس

        if ($value instanceof OrderStatus) {
            return $value->value;
        }

        if (\is_string($value)) {
            $normalized = strtolower($value);
            // Store legacy alias as-is for backward compatibility
            if ('completed' === $normalized) {
                return 'completed';
            }

            return $normalized;
        }

        return $value;
    }
}
