<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\BusinessLogicException;
use App\Helpers\OrderHelper;
use App\Helpers\PriceCalculationHelper;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\OrderRepository;

/**
 * Order Service.
 *
 * Handles all order-related operations including creation, status updates, and cancellation.
 * Not marked as final to allow mocking in unit tests while maintaining production integrity.
 */
class OrderService
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Create a new order.
     *
     * @param array<int, array{product_id: int, quantity: int}> $cartItems
     * @param array<string, string>                             $addresses
     */
    public function createOrder(User $user, array $cartItems, array $addresses): Order
    {
        // @var Order $order
        return $this->orderRepository->executeTransaction(function () use ($user, $cartItems, $addresses): Order {
            $order = $this->orderRepository->createOrder([
                'order_number' => OrderHelper::generateOrderNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'subtotal' => PriceCalculationHelper::calculateSubtotal($cartItems),
                'tax_amount' => PriceCalculationHelper::calculateTax($cartItems),
                'shipping_amount' => PriceCalculationHelper::calculateShipping($cartItems),
                'total_amount' => 0, // Will be calculated
                'currency' => 'USD',
                'shipping_address' => $addresses['shipping'],
                'billing_address' => $addresses['billing'],
            ]);

            $totalAmount = $order->subtotal + $order->tax_amount + $order->shipping_amount;
            $order->update(['total_amount' => $totalAmount]);

            foreach ($cartItems as $item) {
                $productId = $item['product_id'] ?? null;
                if (! is_numeric($productId)) {
                    continue;
                }

                $product = $this->orderRepository->findProduct((int) $productId);
                $quantity = $item['quantity'] ?? 1;
                if (! is_numeric($quantity)) {
                    $quantity = 1;
                }

                // Check stock availability
                if ($product->stock < $quantity) {
                    throw BusinessLogicException::insufficientResources(
                        'stock',
                        $product->stock,
                        $quantity,
                        "Product '{$product->name}' has insufficient stock"
                    );
                }

                $orderItemData = [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => (int) $quantity,
                    'unit_price' => $product->price,
                    'total_price' => (float) $product->price * (int) $quantity,
                ];

                // Only include product_details if the column exists on the same connection as OrderItem
                $orderItemModel = new OrderItem();
                $schema = $orderItemModel->getConnection()->getSchemaBuilder();
                if ($schema->hasColumn($orderItemModel->getTable(), 'product_details')) {
                    $orderItemData['product_details'] = [
                        'name' => $product->name,
                        'sku' => $product->sku ?? '',
                        'image' => $product->image ?? '',
                    ];
                }

                $this->orderRepository->createOrderItem($orderItemData);
            }

            return $order;
        });
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Order $order, OrderStatus|string $status): bool
    {
        // Convert string to Enum if needed, mapping legacy alias "completed" to "delivered"
        if (\is_string($status)) {
            $normalized = strtolower($status);
            if ('completed' === $normalized) {
                $normalized = OrderStatus::DELIVERED->value;
            }
            $newStatus = OrderStatus::from($normalized);
        } else {
            $newStatus = $status;
        }

        // Store old status for event (always enum via accessor)
        $oldStatus = $order->status_enum;

        // Check if transition is allowed
        if (! $oldStatus->canTransitionTo($newStatus)) {
            throw BusinessLogicException::invalidStateTransition(
                'order',
                $oldStatus->value,
                $newStatus->value,
                "Cannot transition order from {$oldStatus->value} to {$newStatus->value}"
            );
        }

        $updateData = ['status' => $newStatus];

        if (OrderStatus::SHIPPED === $newStatus) {
            $updateData['shipped_at'] = now();
        } elseif (OrderStatus::DELIVERED === $newStatus) {
            $updateData['delivered_at'] = now();
        }

        $updated = $this->orderRepository->updateOrder($order->id, $updateData);

        // Fire event if status was updated
        if ($updated) {
            event(new OrderStatusChanged($order, $oldStatus, $newStatus));
        }

        return $updated;
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(Order $order, ?string $reason = null): bool
    {
        // Only allow cancellation for pending or processing orders
        if (! \in_array($order->status_enum, [OrderStatus::PENDING, OrderStatus::PROCESSING], true)) {
            throw BusinessLogicException::invalidStateTransition(
                'order',
                $order->status_enum->value,
                'cancelled',
                'Order can only be cancelled when pending or processing'
            );
        }

        // Eager load items.product to prevent N+1 query
        $order->load('items.product');

        // @var bool $result
        return $this->orderRepository->executeTransaction(function () use ($order, $reason): bool {
            $this->orderRepository->updateOrder($order->id, [
                'status' => OrderStatus::CANCELLED,
                'notes' => $order->notes."\nCancelled: ".($reason ?? 'No reason provided'),
            ]);

            // Restore product stock using available column
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product && method_exists($product, 'increment')) {
                    // Check which stock column is available and increment accordingly
                    if ($this->orderRepository->hasColumn($product->getTable(), 'stock_quantity')) {
                        $this->orderRepository->incrementProductStock($item->product_id, $item->quantity, 'stock_quantity');
                    } elseif ($this->orderRepository->hasColumn($product->getTable(), 'stock')) {
                        $this->orderRepository->incrementProductStock($item->product_id, $item->quantity, 'stock');
                    }
                }
            }

            return true;
        });
    }

    /**
     * Get order history for user.
     *
     * @return Collection<int, Order>
     */
    public function getOrderHistory(User $user, int $limit = 10): Collection
    {
        return $this->orderRepository->getUserOrders($user, $limit);
    }
}
