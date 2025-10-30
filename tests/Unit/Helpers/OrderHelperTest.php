<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\OrderStatus;
use App\Helpers\OrderHelper;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class OrderHelperTest extends TestCase
{
    use RefreshDatabase;

    public function testGetStatusBadgeReturnsCorrectHtml(): void
    {
        $badge = OrderHelper::getStatusBadge(OrderStatus::PENDING);

        self::assertStringContainsString('badge', $badge);
        self::assertStringContainsString('yellow', $badge);
        self::assertStringContainsString('قيد الانتظار', $badge);
    }

    public function testCalculateTotalWithAllValues(): void
    {
        $data = [
            'subtotal' => 100.00,
            'tax_amount' => 15.00,
            'shipping_amount' => 10.00,
            'discount_amount' => 5.00,
        ];

        $total = OrderHelper::calculateTotal($data);

        self::assertSame(120.00, $total);
    }

    public function testCalculateTotalWithMissingValues(): void
    {
        $data = ['subtotal' => 100.00];

        $total = OrderHelper::calculateTotal($data);

        self::assertSame(100.00, $total);
    }

    public function testCalculateTotalReturnsZeroForNegativeResult(): void
    {
        $data = [
            'subtotal' => 50.00,
            'discount_amount' => 100.00,
        ];

        $total = OrderHelper::calculateTotal($data);

        self::assertSame(0.00, $total);
    }

    public function testCalculateTaxWithDefaultRate(): void
    {
        $tax = OrderHelper::calculateTax(100.00);

        self::assertSame(15.00, $tax);
    }

    public function testCalculateTaxWithCustomRate(): void
    {
        $tax = OrderHelper::calculateTax(100.00, 0.20);

        self::assertSame(20.00, $tax);
    }

    public function testGenerateOrderNumberIsUnique(): void
    {
        $number1 = OrderHelper::generateOrderNumber();
        $number2 = OrderHelper::generateOrderNumber();

        self::assertNotSame($number1, $number2);
        self::assertStringStartsWith('ORD-', $number1);
        self::assertStringStartsWith('ORD-', $number2);
    }

    public function testCanBeCancelledReturnsTrueForPending(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        self::assertTrue(OrderHelper::canBeCancelled($order));
    }

    public function testCanBeCancelledReturnsTrueForProcessing(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PROCESSING]);

        self::assertTrue(OrderHelper::canBeCancelled($order));
    }

    public function testCanBeCancelledReturnsFalseForShipped(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::SHIPPED]);

        self::assertFalse(OrderHelper::canBeCancelled($order));
    }

    public function testCanBeRefundedReturnsTrueForDelivered(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::DELIVERED]);

        self::assertTrue(OrderHelper::canBeRefunded($order));
    }

    public function testCanBeRefundedReturnsFalseForPending(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        self::assertFalse(OrderHelper::canBeRefunded($order));
    }

    public function testGetProgressPercentageForAllStatuses(): void
    {
        self::assertSame(0, OrderHelper::getProgressPercentage(OrderStatus::PENDING));
        self::assertSame(25, OrderHelper::getProgressPercentage(OrderStatus::PROCESSING));
        self::assertSame(50, OrderHelper::getProgressPercentage(OrderStatus::SHIPPED));
        self::assertSame(100, OrderHelper::getProgressPercentage(OrderStatus::DELIVERED));
        self::assertSame(0, OrderHelper::getProgressPercentage(OrderStatus::CANCELLED));
        self::assertSame(0, OrderHelper::getProgressPercentage(OrderStatus::REFUNDED));
    }

    public function testFormatTotalWithDefaultCurrency(): void
    {
        $formatted = OrderHelper::formatTotal(150.50);

        self::assertStringContainsString('$', $formatted);
        self::assertStringContainsString('150.50', $formatted);
    }

    public function testFormatTotalWithSarCurrency(): void
    {
        $formatted = OrderHelper::formatTotal(150.50, 'SAR');

        self::assertStringContainsString('ر.س', $formatted);
        self::assertStringContainsString('150.50', $formatted);
    }

    public function testGetEstimatedDeliveryDateForShippedOrder(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::SHIPPED,
            'shipped_at' => now(),
        ]);

        $estimatedDate = OrderHelper::getEstimatedDeliveryDate($order);

        self::assertNotNull($estimatedDate);
        self::assertSame(now()->addDays(3)->format('Y-m-d'), $estimatedDate->format('Y-m-d'));
    }

    public function testGetEstimatedDeliveryDateForProcessingOrder(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PROCESSING]);

        $estimatedDate = OrderHelper::getEstimatedDeliveryDate($order);

        self::assertNotNull($estimatedDate);
        self::assertSame(now()->addDays(5)->format('Y-m-d'), $estimatedDate->format('Y-m-d'));
    }

    public function testGetEstimatedDeliveryDateReturnsNullForPending(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $estimatedDate = OrderHelper::getEstimatedDeliveryDate($order);

        self::assertNull($estimatedDate);
    }

    public function testIsOverdueReturnsFalseForNonShippedOrder(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        self::assertFalse(OrderHelper::isOverdue($order));
    }

    public function testIsOverdueReturnsFalseForRecentShipment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::SHIPPED,
            'shipped_at' => now(),
        ]);

        self::assertFalse(OrderHelper::isOverdue($order));
    }
}
