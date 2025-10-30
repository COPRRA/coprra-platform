<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService();
    }

    public function testCreateOrderCreatesOrderWithItems(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);
        $cartItems = [['product_id' => $product->id, 'quantity' => 2]];
        $addresses = ['shipping' => ['address' => '123 Main St'], 'billing' => ['address' => '123 Main St']];

        // Act
        $order = $this->service->createOrder($user, $cartItems, $addresses);

        // Assert
        self::assertInstanceOf(Order::class, $order);
        self::assertSame($user->id, $order->user_id);
        self::assertSame(OrderStatus::PENDING, $order->status);
        self::assertSame(20.00, $order->subtotal); // 2 * 10
        self::assertSame(1, $order->items->count());
        self::assertSame($product->id, $order->items->first()->product_id);
    }

    public function testUpdateOrderStatusUpdatesValidTransition(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'pending']);

        // Act
        $result = $this->service->updateOrderStatus($order, 'processing');

        // Assert
        self::assertTrue($result);
        self::assertSame(OrderStatus::PROCESSING, $order->fresh()->status);
    }

    public function testUpdateOrderStatusFailsInvalidTransition(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'delivered']);

        // Act
        $result = $this->service->updateOrderStatus($order, 'processing');

        // Assert
        self::assertFalse($result);
        self::assertSame(OrderStatus::DELIVERED, $order->fresh()->status);
    }

    public function testCancelOrderCancelsPendingOrder(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'pending']);
        $product = Product::factory()->create(['stock' => 10]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);

        // Act
        $result = $this->service->cancelOrder($order, 'Changed mind');

        // Assert
        self::assertTrue($result);
        self::assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
        self::assertSame(12, $product->fresh()->stock); // 10 + 2
    }

    public function testCancelOrderFailsForShippedOrder(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'shipped']);

        // Act
        $result = $this->service->cancelOrder($order, 'Changed mind');

        // Assert
        self::assertFalse($result);
        self::assertSame(OrderStatus::SHIPPED, $order->fresh()->status);
    }

    public function testGetOrderHistoryReturnsUserOrders(): void
    {
        // Arrange
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->create(); // Other user

        // Act
        $orders = $this->service->getOrderHistory($user, 10);

        // Assert
        self::assertCount(1, $orders);
        self::assertSame($user->id, $orders->first()->user_id);
    }
}
