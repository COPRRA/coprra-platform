<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Review;
use App\Services\Product\Services\ProductCacheService;
use App\Services\Product\Services\ProductQueryBuilderService;
use App\Services\Product\Services\ProductValidationService;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\ValidationException;

/**
 * Product Repository - Handles product data access with improved separation of concerns.
 */
class ProductRepository
{
    private readonly ProductValidationService $validationService;

    private readonly ProductQueryBuilderService $queryBuilderService;

    private readonly ProductCacheService $cacheService;

    private readonly DatabaseManager $dbManager;

    public function __construct(
        ProductValidationService $validationService,
        ProductQueryBuilderService $queryBuilderService,
        ProductCacheService $cacheService,
        DatabaseManager $dbManager
    ) {
        $this->validationService = $validationService;
        $this->queryBuilderService = $queryBuilderService;
        $this->cacheService = $cacheService;
        $this->dbManager = $dbManager;
    }

    /**
     * Get paginated active products.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedActive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->cacheService->rememberActiveProducts($perPage, function () use ($perPage) {
            return $this->queryBuilderService->buildActiveProductsQuery()->paginate($perPage);
        });
    }

    /**
     * Find product by slug with caching.
     *
     * @throws \InvalidArgumentException If slug is invalid
     */
    public function findBySlug(string $slug): ?Product
    {
        $this->validationService->validateSlug($slug);

        return $this->cacheService->rememberProductBySlug($slug, function () use ($slug) {
            return $this->queryBuilderService->buildProductBySlugQuery($slug)->first();
        });
    }

    /**
     * Get related products with caching.
     *
     * @return Collection<int, Product>
     *
     * @throws \InvalidArgumentException If limit is invalid
     */
    public function getRelated(Product $product, int $limit = 4): Collection
    {
        $this->validationService->validateRelatedLimit($limit);

        return $this->cacheService->rememberRelatedProducts($product->id, $limit, function () use ($product, $limit) {
            return $this->queryBuilderService->buildRelatedQuery($product, $limit)->get();
        });
    }

    /**
     * Search products with validation and rate limiting.
     *
     * @param array<string, float|int|string> $filters
     *
     * @return LengthAwarePaginator<int, Product>
     *
     * @throws ValidationException       If filters are invalid
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function search(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $validated = $this->validationService->validateSearchParameters($query, $filters, $perPage);

        $page = is_numeric(request()->get('page', 1)) ? (int) request()->get('page', 1) : 1;

        return $this->cacheService->rememberSearch(
            $validated['query'],
            $validated['filters'],
            $validated['perPage'],
            $page,
            function () use ($validated) {
                return $this->queryBuilderService->buildSearchQuery(
                    $validated['query'],
                    $validated['filters']
                )->paginate($validated['perPage']);
            }
        );
    }

    // Product Performance Report Methods

    /**
     * Get total products count.
     */
    public function getTotalProductsCount(): int
    {
        return Product::count();
    }

    /**
     * Get products with offers count.
     */
    public function getProductsWithOffersCount(): int
    {
        return Product::has('priceOffers')->count();
    }

    /**
     * Get total price offers count.
     */
    public function getTotalOffersCount(): int
    {
        return PriceOffer::count();
    }

    /**
     * Get price offer prices in a period.
     */
    public function getPriceOfferPricesInPeriod(Carbon $startDate, Carbon $endDate): array
    {
        return PriceOffer::whereBetween('created_at', [$startDate, $endDate])
            ->pluck('price')
            ->toArray()
        ;
    }

    /**
     * Get available offers count in a period.
     */
    public function getAvailableOffersCountInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        return PriceOffer::whereBetween('created_at', [$startDate, $endDate])
            ->where('is_available', true)
            ->count()
        ;
    }

    /**
     * Get total offers count in a period.
     */
    public function getTotalOffersCountInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        return PriceOffer::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Get reviews in a period.
     */
    public function getReviewsInPeriod(Carbon $startDate, Carbon $endDate): SupportCollection
    {
        return Review::whereBetween('created_at', [$startDate, $endDate])->get();
    }

    /**
     * Get top products with counts.
     */
    public function getTopProductsWithCounts(Carbon $startDate, Carbon $endDate, int $limit = 10): Collection
    {
        return Product::withCount(['wishlists', 'priceAlerts', 'reviews'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('wishlists_count', 'desc')
            ->orderBy('price_alerts_count', 'desc')
            ->orderBy('reviews_count', 'desc')
            ->take($limit)
            ->get()
        ;
    }

    /**
     * Get price trends over time.
     */
    public function getPriceTrends(Carbon $startDate, Carbon $endDate): SupportCollection
    {
        return PriceOffer::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->select(
                $this->dbManager->raw('DATE(created_at) as date'),
                $this->dbManager->raw('AVG(price) as average_price')
            )
            ->groupBy('date')
            ->get()
        ;
    }

    /**
     * Get price changes count from audit logs.
     */
    public function getPriceChangesCount(Carbon $startDate, Carbon $endDate): int
    {
        return $this->dbManager->table('audit_logs')
            ->where('event', 'updated')
            ->where('auditable_type', Product::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereJsonContains('metadata->reason', 'Updated from lowest price offer')
            ->count()
        ;
    }

    /**
     * Calculate price statistics from prices array.
     */
    public function calculatePriceStatistics(array $prices): array
    {
        if (empty($prices)) {
            return [
                'total_offers' => 0,
                'price_range' => ['min' => 0, 'max' => 0],
                'average_price' => 0,
                'price_volatility' => 0,
            ];
        }

        $minPrice = min($prices);
        $maxPrice = max($prices);
        $averagePrice = array_sum($prices) / \count($prices);

        // Calculate price volatility (standard deviation)
        $variance = 0;
        foreach ($prices as $price) {
            $variance += ($price - $averagePrice) ** 2;
        }
        $volatility = sqrt($variance / \count($prices));

        return [
            'total_offers' => \count($prices),
            'price_range' => ['min' => round($minPrice, 2), 'max' => round($maxPrice, 2)],
            'average_price' => round($averagePrice, 2),
            'price_volatility' => round($volatility, 2),
        ];
    }

    /**
     * Calculate offer availability statistics.
     */
    public function calculateOfferAvailability(int $totalOffers, int $availableOffers): array
    {
        $unavailableOffers = $totalOffers - $availableOffers;
        $availabilityPercentage = $totalOffers > 0
            ? round(($availableOffers / $totalOffers) * 100, 2)
            : 0;

        return [
            'total_offers' => $totalOffers,
            'available_offers' => $availableOffers,
            'unavailable_offers' => $unavailableOffers,
            'availability_percentage' => $availabilityPercentage,
        ];
    }

    /**
     * Calculate review analysis from reviews collection.
     */
    public function calculateReviewAnalysis(SupportCollection $reviews): array
    {
        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [],
                'approved_reviews' => 0,
                'pending_reviews' => 0,
            ];
        }

        $ratings = $reviews->pluck('rating')->toArray();
        $ratingDistribution = array_count_values($ratings);

        return [
            'total_reviews' => $reviews->count(),
            'average_rating' => round(array_sum($ratings) / \count($ratings), 2),
            'rating_distribution' => $ratingDistribution,
            'approved_reviews' => $reviews->where('is_approved', true)->count(),
            'pending_reviews' => $reviews->where('is_approved', false)->count(),
        ];
    }

    /**
     * Format top products data.
     */
    public function formatTopProductsData(Collection $products): array
    {
        return $products->map(static function (Product $product): array {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'wishlists_count' => $product->wishlists_count ?? 0,
                'price_alerts_count' => $product->price_alerts_count ?? 0,
                'reviews_count' => $product->reviews_count ?? 0,
            ];
        })->toArray();
    }

    /**
     * Format price trends data.
     */
    public function formatPriceTrendsData(SupportCollection $trends): array
    {
        return $trends->map(static function ($trend): array {
            $avgPrice = property_exists($trend, 'average_price') && is_numeric($trend->average_price)
                ? (float) $trend->average_price
                : 0.0;

            return [
                'date' => property_exists($trend, 'date') && \is_string($trend->date) ? $trend->date : '',
                'average_price' => round($avgPrice, 2),
            ];
        })->toArray();
    }

    /**
     * Calculate price changes statistics.
     */
    public function calculatePriceChangesStatistics(int $priceChanges, Carbon $startDate, Carbon $endDate): array
    {
        $daysDiff = $startDate->diffInDays($endDate);
        $averageDailyChanges = $daysDiff > 0 ? $priceChanges / $daysDiff : 0;

        return [
            'total_changes' => $priceChanges,
            'average_daily_changes' => round($averageDailyChanges, 2),
        ];
    }
}
