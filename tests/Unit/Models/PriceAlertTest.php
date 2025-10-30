<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the PriceAlert model.
 *
 * @internal
 */
#[CoversClass(PriceAlert::class)]
final class PriceAlertTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
    public function testFillableAttributes(): void
    {
        $fillable = [
            'user_id',
            'product_id',
            'target_price',
            'repeat_alert',
            'is_active',
        ];

        self::assertSame($fillable, (new PriceAlert())->getFillable());
    }

    /**
     * Test casts.
     */
    public function testCasts(): void
    {
        $casts = [
            'target_price' => 'decimal:2',
            'repeat_alert' => 'boolean',
            'is_active' => 'boolean',
        ];

        self::assertSame($casts, (new PriceAlert())->getCasts());
    }

    /**
     * Test uses SoftDeletes.
     */
    public function testUsesSoftDeletes(): void
    {
        self::assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses(PriceAlert::class));
    }

    /**
     * Test user relation is a BelongsTo instance.
     */
    public function testUserRelation(): void
    {
        $priceAlert = new PriceAlert();

        $relation = $priceAlert->user();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(User::class, $relation->getRelated()::class);
    }

    /**
     * Test product relation is a BelongsTo instance.
     */
    public function testProductRelation(): void
    {
        $priceAlert = new PriceAlert();

        $relation = $priceAlert->product();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(Product::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive applies correct where clause.
     */
    public function testScopeActive(): void
    {
        $query = PriceAlert::query()->active();

        self::assertSame('select * from "price_alerts" where "is_active" = ? and "price_alerts"."deleted_at" is null', $query->toSql());
        self::assertSame([true], $query->getBindings());
    }

    /**
     * Test scopeForUser applies correct where clause.
     */
    public function testScopeForUser(): void
    {
        $query = PriceAlert::query()->forUser(1);

        self::assertSame('select * from "price_alerts" where "user_id" = ? and "price_alerts"."deleted_at" is null', $query->toSql());
        self::assertSame([1], $query->getBindings());
    }

    /**
     * Test scopeForProduct applies correct where clause.
     */
    public function testScopeForProduct(): void
    {
        $query = PriceAlert::query()->forProduct(1);

        self::assertSame('select * from "price_alerts" where "product_id" = ? and "price_alerts"."deleted_at" is null', $query->toSql());
        self::assertSame([1], $query->getBindings());
    }

    /**
     * Test getRules returns validation rules.
     */
    public function testGetRules(): void
    {
        $priceAlert = new PriceAlert();

        $rules = $priceAlert->getRules();

        self::assertIsArray($rules);
        self::assertArrayHasKey('user_id', $rules);
        self::assertArrayHasKey('product_id', $rules);
        self::assertArrayHasKey('target_price', $rules);
    }

    /**
     * Test isPriceTargetReached returns true when current price is less than or equal to target.
     */
    public function testIsPriceTargetReachedTrue(): void
    {
        $priceAlert = new PriceAlert(['target_price' => 100.00]);

        self::assertTrue($priceAlert->isPriceTargetReached(90.00));
        self::assertTrue($priceAlert->isPriceTargetReached(100.00));
    }

    /**
     * Test isPriceTargetReached returns false when current price is greater than target.
     */
    public function testIsPriceTargetReachedFalse(): void
    {
        $priceAlert = new PriceAlert(['target_price' => 100.00]);

        self::assertFalse($priceAlert->isPriceTargetReached(110.00));
    }
}
