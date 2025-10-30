<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Repositories\SalesRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for generating sales reports and analytics.
 * Handles order analysis, revenue tracking, and sales performance metrics.
 */
class SalesReportGenerator
{
    protected $salesRepository;

    public function __construct(SalesRepository $salesRepository)
    {
        $this->salesRepository = $salesRepository;
    }

    /**
     * Generate comprehensive sales report.
     *
     * @return array{
     *     total_orders: int,
     *     total_revenue: float,
     *     average_order_value: float,
     *     top_selling_products: array<int, array<string, mixed>>,
     *     sales_by_period: array<string, array<string, mixed>>,
     *     order_status_breakdown: array<string, int>,
     *     revenue_trends: array<string, float>
     * }
     */
    public function generateReport(Carbon $startDate, Carbon $endDate): array
    {
        $orders = $this->salesRepository->getOrdersInPeriod($startDate, $endDate);

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $this->salesRepository->calculateTotalRevenue($orders),
            'average_order_value' => $this->salesRepository->calculateAverageOrderValue($orders),
            'top_selling_products' => $this->getTopSellingProducts($startDate, $endDate),
            'sales_by_period' => $this->getSalesByPeriod($startDate, $endDate),
            'order_status_breakdown' => $this->salesRepository->getOrderStatusBreakdown($orders),
            'revenue_trends' => $this->getRevenueTrends($startDate, $endDate),
        ];
    }

    /**
     * Get daily sales summary.
     *
     * @return array{
     *     date: string,
     *     orders_count: int,
     *     revenue: float,
     *     average_order_value: float
     * }
     */
    public function getDailySalesSummary(Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $orders = $this->salesRepository->getOrdersInPeriod($startOfDay, $endOfDay);
        $revenue = $this->salesRepository->calculateTotalRevenue($orders);
        $ordersCount = $orders->count();

        return [
            'date' => $date->toDateString(),
            'orders_count' => $ordersCount,
            'revenue' => $revenue,
            'average_order_value' => $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0.0,
        ];
    }

    /**
     * Get top selling products in the period.
     *
     * @return array<int, array{
     *     product_id: int,
     *     product_name: string,
     *     total_quantity: int,
     *     total_revenue: float,
     *     orders_count: int
     * }>
     */
    public function getTopSellingProducts(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        $orderItems = $this->salesRepository->getOrderItemsInPeriod($startDate, $endDate);

        return $this->salesRepository->getProductStatistics($orderItems, $limit);
    }

    /**
     * Get sales breakdown by time period (daily, weekly, monthly).
     *
     * @return array<string, array{
     *     period: string,
     *     orders_count: int,
     *     revenue: float,
     *     average_order_value: float
     * }>
     */
    public function getSalesByPeriod(Carbon $startDate, Carbon $endDate, string $periodType = 'daily'): array
    {
        $orders = $this->salesRepository->getOrdersInPeriod($startDate, $endDate);
        $groupedOrders = $this->salesRepository->groupOrdersByPeriod($orders, $periodType);

        return $groupedOrders->map(function (Collection $periodOrders, string $period): array {
            $revenue = $this->salesRepository->calculateTotalRevenue($periodOrders);
            $ordersCount = $periodOrders->count();

            return [
                'period' => $period,
                'orders_count' => $ordersCount,
                'revenue' => $revenue,
                'average_order_value' => $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0.0,
            ];
        })->toArray();
    }

    /**
     * Get order status breakdown.
     *
     * @return array<string, int>
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
     * Get revenue trends over time.
     *
     * @return array<string, float>
     */
    public function getRevenueTrends(Carbon $startDate, Carbon $endDate): array
    {
        $dailySales = $this->getSalesByPeriod($startDate, $endDate, 'daily');

        return collect($dailySales)->mapWithKeys(static function (array $dayData): array {
            return [$dayData['period'] => $dayData['revenue']];
        })->toArray();
    }

    /**
     * Get customer purchase analysis.
     *
     * @return array{
     *     total_customers: int,
     *     new_customers: int,
     *     returning_customers: int,
     *     average_orders_per_customer: float,
     *     customer_lifetime_value: float
     * }
     */
    public function getCustomerAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $orders = $this->salesRepository->getOrdersInPeriod($startDate, $endDate);
        $customerIds = $this->salesRepository->getUniqueCustomerIds($orders);

        // Get customers who made their first order in this period
        $newCustomers = $this->salesRepository->getNewCustomersInPeriod($customerIds, $startDate);

        $totalCustomers = $customerIds->count();
        $newCustomersCount = $newCustomers->count();
        $returningCustomersCount = $totalCustomers - $newCustomersCount;

        $totalRevenue = $this->salesRepository->calculateTotalRevenue($orders);
        $totalOrders = $orders->count();

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomersCount,
            'returning_customers' => $returningCustomersCount,
            'average_orders_per_customer' => $totalCustomers > 0 ? round($totalOrders / $totalCustomers, 2) : 0.0,
            'customer_lifetime_value' => $totalCustomers > 0 ? round($totalRevenue / $totalCustomers, 2) : 0.0,
        ];
    }

    // Get orders within the specified period.
}
