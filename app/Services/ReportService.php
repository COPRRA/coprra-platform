<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Reports\PriceAnalysisReportGenerator;
use App\Services\Reports\ProductPerformanceReportGenerator;
use App\Services\Reports\SalesReportGenerator;
use App\Services\Reports\UserActivityReportGenerator;
use Carbon\Carbon;

/**
 * Main report service that coordinates different report generators.
 * Acts as a facade for various specialized report generation services.
 */
final readonly class ReportService
{
    public function __construct(
        private ProductPerformanceReportGenerator $productPerformanceGenerator,
        private UserActivityReportGenerator $userActivityGenerator,
        private SalesReportGenerator $salesGenerator,
        private PriceAnalysisReportGenerator $priceAnalysisGenerator
    ) {}

    /**
     * Generate product performance report.
     *
     * @return array{
     *     product_id: int,
     *     name: string,
     *     price_stats: array<string, mixed>,
     *     offer_availability: array<string, mixed>,
     *     review_analysis: array<string, mixed>,
     *     engagement_metrics: array<string, mixed>,
     *     price_trends: array<int, array<string, mixed>>,
     *     top_products: array<int, array<string, mixed>>
     * }
     */
    public function generateProductPerformanceReport(
        int $productId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $endDate ??= Carbon::now();
        $startDate ??= $endDate->copy()->subDays(30);

        return $this->productPerformanceGenerator->generateReport($productId, $startDate, $endDate);
    }

    /**
     * Generate user activity report.
     *
     * @return array{
     *     total_users_active: int,
     *     wishlist_activity: array<int, array<string, mixed>>,
     *     price_alerts_activity: array<int, array<string, mixed>>,
     *     reviews_activity: array<int, array<string, mixed>>,
     *     engagement_summary: array<string, mixed>
     * }
     */
    public function generateUserActivityReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate ??= now()->subMonth();
        $endDate ??= now();

        return $this->userActivityGenerator->generateReport($startDate, $endDate);
    }

    /**
     * Generate sales report.
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
    public function generateSalesReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate ??= now()->subMonth();
        $endDate ??= now();

        return $this->salesGenerator->generateReport($startDate, $endDate);
    }

    /**
     * Generate price analysis report.
     *
     * @return array{
     *     price_changes_summary: array<string, mixed>,
     *     trending_products: array<int, array<string, mixed>>,
     *     price_volatility_analysis: array<int, array<string, mixed>>,
     *     market_trends: array<string, mixed>,
     *     price_alerts_triggered: int
     * }
     */
    public function generatePriceAnalysisReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate ??= now()->subMonth();
        $endDate ??= now();

        return $this->priceAnalysisGenerator->generateReport($startDate, $endDate);
    }

    /**
     * Generate comprehensive dashboard report combining all report types.
     *
     * @return array{
     *     sales_summary: array<string, mixed>,
     *     user_activity_summary: array<string, mixed>,
     *     price_analysis_summary: array<string, mixed>,
     *     generated_at: string
     * }
     */
    public function generateDashboardReport(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate ??= now()->subMonth();
        $endDate ??= now();

        return [
            'sales_summary' => $this->salesGenerator->generateReport($startDate, $endDate),
            'user_activity_summary' => $this->userActivityGenerator->generateReport($startDate, $endDate),
            'price_analysis_summary' => $this->priceAnalysisGenerator->generateReport($startDate, $endDate),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get daily sales summary.
     */
    public function getDailySalesSummary(Carbon $date): array
    {
        return $this->salesGenerator->getDailySalesSummary($date);
    }

    /**
     * Get user activity summary for a specific user.
     */
    public function getUserActivitySummary(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->userActivityGenerator->getUserActivitySummary($userId, $startDate, $endDate);
    }

    /**
     * Get price trends for a specific product.
     */
    public function getProductPriceTrends(int $productId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->priceAnalysisGenerator->getProductPriceTrends($productId, $startDate, $endDate);
    }

    /**
     * Get top selling products.
     */
    public function getTopSellingProducts(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return $this->salesGenerator->getTopSellingProducts($startDate, $endDate, $limit);
    }

    /**
     * Get customer analysis.
     */
    public function getCustomerAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        return $this->salesGenerator->getCustomerAnalysis($startDate, $endDate);
    }
}
