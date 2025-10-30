<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the PaymentMethod model.
 *
 * @internal
 */
#[CoversClass(PaymentMethod::class)]
final class PaymentMethodTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
    public function testFillableAttributes(): void
    {
        $fillable = [
            'name',
            'gateway',
            'type',
            'config',
            'is_active',
            'is_default',
        ];

        self::assertSame($fillable, (new PaymentMethod())->getFillable());
    }

    /**
     * Test casts.
     */
    public function testCasts(): void
    {
        $casts = [
            'config' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];

        self::assertSame($casts, (new PaymentMethod())->getCasts());
    }

    /**
     * Test payments relation is a HasMany instance.
     */
    public function testPaymentsRelation(): void
    {
        $paymentMethod = new PaymentMethod();

        $relation = $paymentMethod->payments();

        self::assertInstanceOf(HasMany::class, $relation);
        self::assertSame(Payment::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive applies correct where clause.
     */
    public function testScopeActive(): void
    {
        $query = PaymentMethod::query()->active();

        self::assertSame('select * from "payment_methods" where "is_active" = ?', $query->toSql());
        self::assertSame([true], $query->getBindings());
    }

    /**
     * Test scopeDefault applies correct where clause.
     */
    public function testScopeDefault(): void
    {
        $query = PaymentMethod::query()->default();

        self::assertSame('select * from "payment_methods" where "is_default" = ?', $query->toSql());
        self::assertSame([true], $query->getBindings());
    }
}
