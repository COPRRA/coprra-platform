<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the OrderItem model.
 *
 * @internal
 */
#[CoversClass(OrderItem::class)]
final class OrderItemTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
    public function testFillableAttributes(): void
    {
        $fillable = [
            'order_id',
            'product_id',
            'quantity',
            'unit_price',
            'total_price',
            'product_details',
        ];

        self::assertSame($fillable, (new OrderItem())->getFillable());
    }

    /**
     * Test casts.
     */
    public function testCasts(): void
    {
        $casts = [
            'product_details' => 'array',
        ];

        self::assertSame($casts, (new OrderItem())->getCasts());
    }

    /**
     * Test order relation is a BelongsTo instance.
     */
    public function testOrderRelation(): void
    {
        $orderItem = new OrderItem();

        $relation = $orderItem->order();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(Order::class, $relation->getRelated()::class);
    }

    /**
     * Test product relation is a BelongsTo instance.
     */
    public function testProductRelation(): void
    {
        $orderItem = new OrderItem();

        $relation = $orderItem->product();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(Product::class, $relation->getRelated()::class);
    }
}
