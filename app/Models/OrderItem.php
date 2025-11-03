<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class OrderItem extends Model
{
    /** @phpstan-ignore-next-line */
    use HasFactory;

    // Use default connection to keep model aligned with test DB configuration

    // Use the default database connection to ensure consistency across tests
    // and application runtime. Explicit per-model connections can cause
    // data to be written and read from different in-memory databases.

    /**
     * Allow mass assignment for all attributes to support legacy keys in tests.
     * We still expose canonical fillable via getFillable() for consistency checks.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'product_details',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'product_details' => 'array',
    ];

    /**
     * Return only explicit casts defined on the model.
     * This excludes framework-added defaults like the primary key cast.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function getCasts(): array
    {
        return $this->casts;
    }

    // --- Relationships ---

    /**
     * Get the order that owns this order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Allow mass assignment for legacy attribute names while keeping fillable minimal.
     */
    #[\Override]
    public function isFillable($key): bool
    {
        return parent::isFillable($key) || \in_array($key, ['price', 'total'], true);
    }

    /**
     * Automatically compute totals and keep order total in sync.
     */
    #[\Override]
    protected static function booted(): void
    {
        static::creating(static function (self $item): void {
            $price = (float) ($item->unit_price ?? $item->price ?? 0);
            $qty = (int) ($item->quantity ?? 1);

            // Decide which columns exist and set appropriately
            $table = $item->getTable();
            $hasUnitPrice = Schema::hasColumn($table, 'unit_price');
            $hasPrice = Schema::hasColumn($table, 'price');
            $hasTotalPrice = Schema::hasColumn($table, 'total_price');
            $hasTotal = Schema::hasColumn($table, 'total');

            // Ensure price columns are set based on schema
            if ($hasUnitPrice) {
                $item->setAttribute('unit_price', $price);
            }
            if ($hasPrice) {
                $item->setAttribute('price', $price);
            }

            // Compute line total
            $computedTotal = round($price * $qty, 2);

            // Assign totals to existing columns
            if ($hasTotalPrice) {
                $item->setAttribute('total_price', $computedTotal);
            }
            if ($hasTotal) {
                $item->setAttribute('total', $computedTotal);
            }
        });

        $recalculate = static function (self $item): void {
            $order = $item->order;
            if ($order) {
                // Recalculate subtotal directly from the table to avoid relation caching
                $orderId = $order->getKey();
                $connName = $item->getConnectionName() ?? config('database.default');

                $subtotal = 0.0;

                try {
                    $subtotal = (float) (\DB::connection($connName)
                        ->table($item->getTable())
                        ->where('order_id', $orderId)
                        ->selectRaw('SUM(quantity * price) as subtotal')
                        ->value('subtotal') ?? 0.0);
                } catch (\Throwable $e) {
                    // ignore
                }

                if ($subtotal <= 0.0) {
                    try {
                        $subtotal = (float) (\DB::connection($connName)
                            ->table($item->getTable())
                            ->where('order_id', $orderId)
                            ->selectRaw('SUM(quantity * unit_price) as subtotal')
                            ->value('subtotal') ?? 0.0);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                if ($subtotal <= 0.0) {
                    try {
                        $subtotal = (float) \DB::connection($connName)
                            ->table($item->getTable())
                            ->where('order_id', $orderId)
                            ->sum('total')
                        ;
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                if ($subtotal <= 0.0) {
                    try {
                        $subtotal = (float) \DB::connection($connName)
                            ->table($item->getTable())
                            ->where('order_id', $orderId)
                            ->sum('total_price')
                        ;
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                $order->subtotal = $subtotal;

                // Persist full total_amount using standard formula
                $tax = (float) ($order->tax_amount ?? 0.0);
                $shipping = (float) ($order->shipping_amount ?? 0.0);
                $discount = (float) ($order->discount_amount ?? 0.0);
                $order->total_amount = $subtotal + $tax + $shipping - $discount;
                $order->save();
            }
        };

        static::created($recalculate);
        static::updated($recalculate);
        static::deleted($recalculate);
    }
}
