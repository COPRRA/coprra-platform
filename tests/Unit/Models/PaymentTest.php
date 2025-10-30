<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the Payment model.
 *
 * @internal
 */
#[CoversClass(Payment::class)]
final class PaymentTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that order relation is a BelongsTo instance.
     */
    public function testOrderRelation(): void
    {
        $payment = new Payment();

        $relation = $payment->order();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(Order::class, $relation->getRelated()::class);
    }

    /**
     * Test that paymentMethod relation is a BelongsTo instance.
     */
    public function testPaymentMethodRelation(): void
    {
        $payment = new Payment();

        $relation = $payment->paymentMethod();

        self::assertInstanceOf(BelongsTo::class, $relation);
        self::assertSame(PaymentMethod::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeByStatus adds where clause for status.
     */
    public function testScopeByStatus(): void
    {
        $status = 'completed';
        $query = \Mockery::mock(Builder::class);
        $query->shouldReceive('where')->once()->with('status', $status)->andReturnSelf();

        $payment = new Payment();
        $result = $payment->scopeByStatus($query, $status);

        self::assertInstanceOf(Builder::class, $result);
    }
}
