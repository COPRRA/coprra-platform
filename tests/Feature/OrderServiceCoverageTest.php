<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class OrderServiceCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateOrderComputesTotalsAndPersistsItems(): void
    {
        $user = User::factory()->create();
        $p1 = Product::factory()->create(['price' => 40.00]);
        $p2 = Product::factory()->create(['price' => 25.50]);

        $cartItems = [
            ['product_id' => $p1->id, 'quantity' => 2],
            ['product_id' => $p2->id, 'quantity' => 1],
        ];

        $addresses = [
            'shipping' => '123 Test St, City',
            'billing' => '456 Billing Ave, City',
        ];

        $service = new OrderService();
        $order = $service->createOrder($user, $cartItems, $addresses);

        self::assertInstanceOf(Order::class, $order);
        self::assertSame(OrderStatus::PENDING, $order->status_enum);

        // Subtotal = (40 * 2) + (25.5 * 1) = 105.5
        // Tax (10%) = 10.55, Shipping (<= 100 threshold -> 10) => subtotal (105.5) > 100 so free
        $expectedSubtotal = 105.50;
        $expectedTax = round($expectedSubtotal * 0.10, 2);
        $expectedShipping = 0.0; // free shipping threshold default is 100
        $expectedTotal = round($expectedSubtotal + $expectedTax + $expectedShipping, 2);

        self::assertSame(round($expectedSubtotal, 2), (float) $order->subtotal);
        self::assertSame(round($expectedTax, 2), (float) $order->tax_amount);
        self::assertSame($expectedShipping, (float) $order->shipping_amount);
        self::assertSame($expectedTotal, (float) $order->total_amount);

        self::assertCount(2, $order->items);

        // Each item total should be computed by model booted hooks (unit_price * quantity)
        $item1 = $order->items->firstWhere('product_id', $p1->id);
        $item2 = $order->items->firstWhere('product_id', $p2->id);
        self::assertNotNull($item1);
        self::assertNotNull($item2);
        self::assertSame(2, $item1->quantity);
        self::assertSame(1, $item2->quantity);
        self::assertSame(40.00, (float) $item1->unit_price);
        self::assertSame(25.50, (float) $item2->unit_price);
        self::assertSame(80.00, (float) $item1->total_price);
        self::assertSame(25.50, (float) $item2->total_price);
    }

    public function testUpdateOrderStatusAllowsTransitionAndFiresEvent(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 20.0]);
        $order = (new OrderService())->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ], [
            'shipping' => 'Shipping Address',
            'billing' => 'Billing Address',
        ]);

        $service = new OrderService();
        // PENDING -> PROCESSING (allowed)
        self::assertTrue($service->updateOrderStatus($order, OrderStatus::PROCESSING));
        self::assertSame(OrderStatus::PROCESSING, $order->status_enum);
        Event::assertDispatched(OrderStatusChanged::class);

        // PROCESSING -> SHIPPED (allowed)
        self::assertTrue($service->updateOrderStatus($order, OrderStatus::SHIPPED));
        self::assertSame(OrderStatus::SHIPPED, $order->status_enum);
        self::assertNotNull($order->shipped_at);
        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function testCancelOrderRestoresStockAndReturnsTrueForPending(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 15.0, 'stock_quantity' => 5]);

        $order = (new OrderService())->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 3],
        ], [
            'shipping' => 'Shipping Address',
            'billing' => 'Billing Address',
        ]);

        $service = new OrderService();
        self::assertTrue($service->cancelOrder($order, 'Customer request'));

        $product->refresh();
        // Stock restored by incrementing stock_quantity preferred over legacy stock
        self::assertSame(8, (int) $product->stock_quantity);
        self::assertSame(OrderStatus::CANCELLED, $order->status_enum);
    }

    public function testUpdateOrderStatusRejectsInvalidTransition(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.0]);
        $order = (new OrderService())->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ], [
            'shipping' => 'Shipping Address',
            'billing' => 'Billing Address',
        ]);

        // Force final state to DELIVERED
        $order->update(['status' => OrderStatus::DELIVERED, 'delivered_at' => now()]);

        $service = new OrderService();
        // DELIVERED -> PROCESSING should be rejected
        self::assertFalse($service->updateOrderStatus($order, OrderStatus::PROCESSING));
        self::assertSame(OrderStatus::DELIVERED, $order->status_enum);
    }

    public function testCreateOrderRespectsShippingThresholdBoundaryAndTaxConfig(): void
    {
        Config::set('coprra.tax.rate', 0.10);
        Config::set('coprra.shipping.free_threshold', 100);
        Config::set('coprra.shipping.standard_fee', 10);

        $user = User::factory()->create();
        $p1 = Product::factory()->create(['price' => 50.00]);
        $p2 = Product::factory()->create(['price' => 50.00]);

        // Subtotal equals free shipping threshold (100) -> shipping should apply (10)
        $cartItems = [
            ['product_id' => $p1->id, 'quantity' => 1],
            ['product_id' => $p2->id, 'quantity' => 1],
        ];

        $addresses = [
            'shipping' => '123 Test St, City',
            'billing' => '456 Billing Ave, City',
        ];

        $order = (new OrderService())->createOrder($user, $cartItems, $addresses);

        $expectedSubtotal = 100.00;
        $expectedTax = round($expectedSubtotal * 0.10, 2); // 10.00
        $expectedShipping = 10.00; // boundary equals threshold -> fee applies
        $expectedTotal = round($expectedSubtotal + $expectedTax + $expectedShipping, 2); // 120.00

        self::assertSame($expectedSubtotal, (float) $order->subtotal);
        self::assertSame($expectedTax, (float) $order->tax_amount);
        self::assertSame($expectedShipping, (float) $order->shipping_amount);
        self::assertSame($expectedTotal, (float) $order->total_amount);
    }

    public function testUpdateOrderStatusAcceptsCompletedAliasAsDeliveredAndFiresEvent(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 30.0]);
        $order = (new OrderService())->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ], [
            'shipping' => 'Shipping Address',
            'billing' => 'Billing Address',
        ]);

        $service = new OrderService();

        // Move through allowed transitions before aliasing to delivered
        self::assertTrue($service->updateOrderStatus($order, OrderStatus::PROCESSING));
        self::assertTrue($service->updateOrderStatus($order, OrderStatus::SHIPPED));

        // Alias 'completed' should map to DELIVERED and be allowed from SHIPPED
        self::assertTrue($service->updateOrderStatus($order, 'completed'));
        self::assertSame(OrderStatus::DELIVERED, $order->status_enum);
        Event::assertDispatched(OrderStatusChanged::class);
        Event::assertDispatched(OrderStatusChanged::class, static function (OrderStatusChanged $event) use ($order) {
            return $event->order->is($order)
                && OrderStatus::SHIPPED === $event->oldStatus
                && OrderStatus::DELIVERED === $event->newStatus;
        });
    }
}
