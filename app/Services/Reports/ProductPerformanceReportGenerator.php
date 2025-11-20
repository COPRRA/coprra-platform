<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Repositories\ProductRepository;
use Carbon\Carbon;

class ProductPerformanceReportGenerator
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Generate comprehensive product performance report.
     */
    public function generateReport(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'overview' => [
                'total_products' => $this->productRepository->getTotalProductsCount(),
                'products_with_offers' => $this->productRepository->getProductsWithOffersCount(),
                'total_offers' => $this->productRepository->getTotalOffersCount(),
            ],
            'price_statistics' => $this->getPriceStatistics($startDate, $endDate),
            'offer_availability' => $this->getOfferAvailability($startDate, $endDate),
            'review_analysis' => $this->getReviewAnalysis($startDate, $endDate),
            'top_products' => $this->getTopProducts($startDate, $endDate),
            'price_trends' => $this->getPriceTrends($startDate, $endDate),
            'price_changes' => $this->getPriceChanges($startDate, $endDate),
        ];
    }

    /**
     * Get price statistics for the given period.
     */
    private function getPriceStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $prices = $this->productRepository->getPriceOfferPricesInPeriod($startDate, $endDate);

        return $this->productRepository->calculatePriceStatistics($prices);
    }

    /**
     * Get offer availability statistics.
     */
    private function getOfferAvailability(Carbon $startDate, Carbon $endDate): array
    {
        $totalOffers = $this->productRepository->getTotalOffersCountInPeriod($startDate, $endDate);
        $availableOffers = $this->productRepository->getAvailableOffersCountInPeriod($startDate, $endDate);

        return $this->productRepository->calculateOfferAvailability($totalOffers, $availableOffers);
    }

    /**
     * Get review analysis for the period.
     */
    private function getReviewAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $reviews = $this->productRepository->getReviewsInPeriod($startDate, $endDate);

        return $this->productRepository->calculateReviewAnalysis($reviews);
    }

    /**
     * Get top performing products.
     */
    private function getTopProducts(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        $products = $this->productRepository->getTopProductsWithCounts($startDate, $endDate, $limit);

        return $this->productRepository->formatTopProductsData($products);
    }

    /**
     * Get price trends over time.
     */
    private function getPriceTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = $this->productRepository->getPriceTrends($startDate, $endDate);

        return $this->productRepository->formatPriceTrendsData($trends);
    }

    /**
     * Get price changes statistics.
     */
    private function getPriceChanges(Carbon $startDate, Carbon $endDate): array
    {
        $priceChanges = $this->productRepository->getPriceChangesCount($startDate, $endDate);

        return $this->productRepository->calculatePriceChangesStatistics($priceChanges, $startDate, $endDate);
    }
}
