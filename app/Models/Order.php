<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $order_number
 * @property int         $user_id
 * @property string      $status
 * @property OrderStatus $status_enum
 * @property float       $total_amount
 * @property float       $subtotal
 * @property float       $tax_amount
 * @property float       $shipping_amount
 ** @property float|null $discount_amount
 * @property array<string, mixed> $shipping_address
 * @property array<string, mixed> $billing_address
 * @property string|null          $notes
 * @property Carbon|null          $shipped_at
 * @property Carbon|null          $delivered_at
 */
class Order extends Model
{
    /** @phpstan-ignore-next-line */
    use HasFactory;

    // Use default application connection to avoid cross-DB inconsistencies in tests

    // Use the default database connection to ensure consistency across tests
    // and application runtime. Explicit per-model connections can cause
    // data to be written and read from different in-memory databases.

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'total_amount',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'currency',
        'shipping_address',
        'billing_address',
        'notes',
        'order_date',
        'shipped_at',
        'delivered_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => OrderStatus::class,
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'order_date' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, Order>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, Order>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * @return HasMany<Payment, Order>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // --- Scopes ---

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Ensure total_amount synced with subtotal/tax/shipping/discount.
     */
    #[\Override]
    protected static function booted(): void
    {
        static::saving(static function (self $order): void {
            $subtotal = (float) ($order->subtotal ?? 0.0);
            $tax = (float) ($order->tax_amount ?? 0.0);
            $shipping = (float) ($order->shipping_amount ?? 0.0);
            $discount = (float) ($order->discount_amount ?? 0.0);

            $order->total_amount = round($subtotal + $tax + $shipping - $discount, 2);
        });
    }
}
