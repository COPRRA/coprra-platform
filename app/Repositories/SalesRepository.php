<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Repository for sales-related database operations.
 * Centralizes all sales data queries for reports and analytics.
 */
class SalesRepository
{
    /**
     * Get orders within the specified period.
     */
    public function getOrdersInPeriod(Carbon $startDate, Carbon $endDate): Collection
    {
        return Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get()
        ;
    }

    /**
     * Get order items with orders and products for top selling products analysis.
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
     * Get first order for a customer.
     */
    public function getFirstOrderForCustomer(int $customerId): ?Order
    {
        return Order::where('user_id', $customerId)
            ->orderBy('created_at')
            ->first()
        ;
    }

    /**
     * Calculate total revenue from orders.
     */
    public function calculateTotalRevenue(Collection $orders): float
    {
        return round($orders->sum('total_amount'), 2);
    }

    /**
     * Calculate average order value.
     */
    public function calculateAverageOrderValue(Collection $orders): float
    {
        $count = $orders->count();
        if (0 === $count) {
            return 0.0;
        }

        return round($orders->sum('total_amount') / $count, 2);
    }

    /**
     * Get order status breakdown.
     */
    public function getOrderStatusBreakdown(Collection $orders): array
    {
        return $orders->groupBy('status')
            ->map(static function (Collection $statusOrders): int {
                return $statusOrders->count();
            })
            ->toArray()
        ;
    }

    /**
     * Group orders by period (daily, weekly, monthly).
     */
    public function groupOrdersByPeriod(Collection $orders, string $periodType = 'daily'): Collection
    {
        return match ($periodType) {
            'weekly' => $orders->groupBy(static function (Order $order) {
                return $order->created_at->format('Y-W');
            }),
            'monthly' => $orders->groupBy(static function (Order $order) {
                return $order->created_at->format('Y-m');
            }),
            default => $orders->groupBy(static function (Order $order) {
                return $order->created_at->format('Y-m-d');
            }),
        };
    }

    /**
     * Process order items to get product statistics.
     */
    public function getProductStatistics(Collection $orderItems, int $limit = 10): array
    {
        $productStats = $orderItems->groupBy('product_id')->map(static function (Collection $items, int $productId): array {
            $product = $items->first()->product;
            $totalQuantity = $items->sum('quantity');
            $totalRevenue = $items->sum(static function ($item) {
                return $item->quantity * $item->price;
            });
            $ordersCount = $items->pluck('order_id')->unique()->count();

            return [
                'product_id' => $productId,
                'product_name' => $product->name ?? 'Unknown Product',
                'total_quantity' => $totalQuantity,
                'total_revenue' => round($totalRevenue, 2),
                'orders_count' => $ordersCount,
            ];
        });

        return $productStats
            ->sortByDesc('total_quantity')
            ->take($limit)
            ->values()
            ->toArray()
        ;
    }

    /**
     * Get unique customer IDs from orders.
     */
    public function getUniqueCustomerIds(Collection $orders): Collection
    {
        return $orders->pluck('user_id')->unique();
    }

    /**
     * Filter customers who made their first order in the specified period.
     */
    public function getNewCustomersInPeriod(Collection $customerIds, Carbon $startDate): Collection
    {
        return $customerIds->filter(function (int $customerId) use ($startDate): bool {
            $firstOrder = $this->getFirstOrderForCustomer($customerId);

            return $firstOrder && $firstOrder->created_at >= $startDate;
        });
    }

    /**
     * Get customer's first order.
     */
    public function getCustomerFirstOrder(int $customerId): ?Order
    {
        return Order::where('user_id', $customerId)
            ->orderBy('created_at')
            ->first();
    }
}
