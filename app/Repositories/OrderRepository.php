<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository for Order-related database operations.
 * Encapsulates all database queries related to orders and order items.
 */
class OrderRepository
{
    /**
     * Create a new order with transaction support.
     */
    public function createOrder(array $orderData): Order
    {
        return Order::query()->create($orderData);
    }

    /**
     * Create order item.
     */
    public function createOrderItem(array $orderItemData): OrderItem
    {
        return OrderItem::query()->create($orderItemData);
    }

    /**
     * Find product by ID.
     */
    public function findProduct(int $productId): ?Product
    {
        return Product::find($productId);
    }

    /**
     * Update order data.
     */
    public function updateOrder(Order $order, array $data): bool
    {
        return $order->update($data);
    }

    /**
     * Get user orders with relationships.
     *
     * @return Collection<int, Order>
     */
    public function getUserOrders(User $user, int $limit = 10): Collection
    {
        return $user->orders()
            ->with(['items.product', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
        ;
    }

    /**
     * Get order items for an order.
     *
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(Order $order): Collection
    {
        return $order->items()->with('product')->get();
    }

    /**
     * Check if product has sufficient stock.
     */
    public function hasStock(Product $product, int $quantity): bool
    {
        return $product->stock >= $quantity;
    }

    /**
     * Increment product stock.
     */
    public function incrementProductStock(Product $product, int $quantity): bool
    {
        $schema = $product->getConnection()->getSchemaBuilder();
        $table = $product->getTable();

        // Prefer stock_quantity (new schema), fallback to legacy stock
        if ($schema->hasColumn($table, 'stock_quantity')) {
            return $product->increment('stock_quantity', $quantity);
        }
        if ($schema->hasColumn($table, 'stock')) {
            return $product->increment('stock', $quantity);
        }

        return false;
    }

    /**
     * Check if order item table has product_details column.
     */
    public function hasProductDetailsColumn(): bool
    {
        $orderItemModel = new OrderItem();
        $schema = $orderItemModel->getConnection()->getSchemaBuilder();

        return $schema->hasColumn($orderItemModel->getTable(), 'product_details');
    }

    /**
     * Execute database transaction.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }

    /**
     * Get orders within a date range.
     *
     * @return Collection<int, Order>
     */
    public function getOrdersInPeriod(Carbon $startDate, Carbon $endDate): Collection
    {
        return Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get()
        ;
    }

    /**
     * Get order items within a date range with relationships.
     *
     * @return Collection<int, OrderItem>
     */
    public function getOrderItemsInPeriod(Carbon $startDate, Carbon $endDate): Collection
    {
        return OrderItem::whereHas('order', static function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
            ;
        })
            ->with(['product', 'order'])
            ->get()
        ;
    }

    /**
     * Get total revenue from orders.
     */
    public function getTotalRevenue(Collection $orders): float
    {
        return $orders->sum('total_amount');
    }

    /**
     * Get average order value.
     */
    public function getAverageOrderValue(Collection $orders): float
    {
        $count = $orders->count();

        return $count > 0 ? $this->getTotalRevenue($orders) / $count : 0.0;
    }
}
