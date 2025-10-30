<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Repositories\PriceAnalysisRepository;
use Carbon\Carbon;

/**
 * Service for generating price analysis reports.
 * Handles price tracking, trend analysis, and price change monitoring.
 */
class PriceAnalysisReportGenerator
{
    private PriceAnalysisRepository $priceAnalysisRepository;

    public function __construct(PriceAnalysisRepository $priceAnalysisRepository)
    {
        $this->priceAnalysisRepository = $priceAnalysisRepository;
    }

    /**
     * Generate comprehensive price analysis report.
     *
     * @return array{
     *     price_changes_summary: array<string, mixed>,
     *     trending_products: array<int, array<string, mixed>>,
     *     price_volatility_analysis: array<int, array<string, mixed>>,
     *     market_trends: array<string, mixed>,
     *     price_alerts_triggered: int
     * }
     */
    public function generateReport(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'price_changes_summary' => $this->getPriceChangesSummary($startDate, $endDate),
            'trending_products' => $this->getTrendingProducts($startDate, $endDate),
            'price_volatility_analysis' => $this->getPriceVolatilityAnalysis($startDate, $endDate),
            'market_trends' => $this->getMarketTrends($startDate, $endDate),
            'price_alerts_triggered' => $this->priceAnalysisRepository->countTriggeredPriceAlerts($startDate, $endDate),
        ];
    }

    /**
     * Get price trends for a specific product.
     */
    public function getProductPriceTrends(int $productId, Carbon $startDate, Carbon $endDate): array
    {
        $product = $this->priceAnalysisRepository->getProductById($productId);
        if (! $product) {
            return [
                'error' => 'Product not found',
                'product_id' => $productId,
            ];
        }

        $priceHistory = $this->priceAnalysisRepository->getProductPriceHistory($productId, $startDate, $endDate);

        return [
            'product_id' => $productId,
            'product_name' => $product->name,
            'current_price' => $product->price,
            'price_trends' => $this->priceAnalysisRepository->formatTrendData($priceHistory),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get summary of price changes in the period.
     */
    public function getPriceChangesSummary(Carbon $startDate, Carbon $endDate): array
    {
        $priceChanges = $this->priceAnalysisRepository->getPriceChangesFromAuditLog($startDate, $endDate);

        return $this->priceAnalysisRepository->calculatePriceChangesSummary($priceChanges);
    }

    /**
     * Get trending products based on price changes.
     *
     * @return array<int, array{
     *     product_id: int,
     *     product_name: string,
     *     current_price: float,
     *     price_change_percentage: float,
     *     trend_direction: string,
     *     volatility_score: float
     * }>
     */
    public function getTrendingProducts(Carbon $startDate, Carbon $endDate, int $limit = 20): array
    {
        $priceChanges = $this->priceAnalysisRepository->getPriceChangesFromAuditLog($startDate, $endDate);

        return $this->priceAnalysisRepository->calculateTrendingProducts($priceChanges, $limit);
    }

    /**
     * Get price volatility analysis.
     *
     * @return array<int, array{
     *     product_id: int,
     *     product_name: string,
     *     volatility_score: float,
     *     price_changes_count: int,
     *     price_range: array{min: float, max: float},
     *     stability_rating: string
     * }>
     */
    public function getPriceVolatilityAnalysis(Carbon $startDate, Carbon $endDate, int $limit = 15): array
    {
        $priceHistory = $this->priceAnalysisRepository->getPriceHistoryWithProducts($startDate, $endDate);

        return $this->priceAnalysisRepository->calculatePriceVolatilityAnalysis($priceHistory, $limit);
    }

    /**
     * Get market trends analysis.
     *
     * @return array{
     *     overall_trend: string,
     *     average_price_change: float,
     *     products_with_increases: int,
     *     products_with_decreases: int,
     *     market_volatility: float,
     *     trend_strength: string
     * }
     */
    public function getMarketTrends(Carbon $startDate, Carbon $endDate): array
    {
        $priceChanges = $this->priceAnalysisRepository->getPriceChangesFromAuditLog($startDate, $endDate);

        return $this->priceAnalysisRepository->calculateMarketTrends($priceChanges);
    }
}
