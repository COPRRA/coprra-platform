<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;
use App\Models\PriceHistory;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PriceAnalysisRepository
{
    /**
     * Get price history for a specific product in a period.
     */
    public function getProductPriceHistory(int $productId, Carbon $startDate, Carbon $endDate): Collection
    {
        return PriceHistory::where('product_id', $productId)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->orderBy('recorded_at')
            ->get()
        ;
    }

    /**
     * Get price history with product information in a period.
     */
    public function getPriceHistoryWithProducts(Carbon $startDate, Carbon $endDate): Collection
    {
        return PriceHistory::whereBetween('recorded_at', [$startDate, $endDate])
            ->with('product')
            ->get()
        ;
    }

    /**
     * Get price changes from audit log.
     */
    public function getPriceChangesFromAuditLog(Carbon $startDate, Carbon $endDate): Collection
    {
        $auditLogs = AuditLog::where('auditable_type', Product::class)
            ->where('event', 'updated')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereJsonContains('old_values', ['price'])
            ->get()
        ;

        return $auditLogs->map(static function (AuditLog $log): array {
            $oldPrice = $log->old_values['price'] ?? 0;
            $newPrice = $log->new_values['price'] ?? 0;

            $changePercentage = 0;
            if ($oldPrice > 0) {
                $changePercentage = (($newPrice - $oldPrice) / $oldPrice) * 100;
            }

            return [
                'product_id' => $log->auditable_id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'change_percentage' => $changePercentage,
                'changed_at' => $log->created_at,
            ];
        })->filter(static function (array $change): bool {
            return 0 !== $change['change_percentage'];
        });
    }

    /**
     * Count triggered price alerts in the period.
     */
    public function countTriggeredPriceAlerts(Carbon $startDate, Carbon $endDate): int
    {
        return AuditLog::where('event', 'price_alert_triggered')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count()
        ;
    }

    /**
     * Get product by ID.
     */
    public function getProductById(int $productId): ?Product
    {
        return Product::find($productId);
    }

    /**
     * Format trend data for display.
     */
    public function formatTrendData(Collection $priceHistory): array
    {
        $formatted = [];
        $previousPrice = null;

        foreach ($priceHistory as $record) {
            $changePercentage = null;
            if (null !== $previousPrice && $previousPrice > 0) {
                $changePercentage = round((($record->price - $previousPrice) / $previousPrice) * 100, 2);
            }

            $formatted[] = [
                'date' => $record->recorded_at->toDateString(),
                'price' => $record->price,
                'change_percentage' => $changePercentage,
                'source' => $record->source ?? 'system',
            ];

            $previousPrice = $record->price;
        }

        return $formatted;
    }

    /**
     * Calculate price changes summary.
     */
    public function calculatePriceChangesSummary(Collection $priceChanges): array
    {
        if ($priceChanges->isEmpty()) {
            return [
                'total_price_changes' => 0,
                'price_increases' => 0,
                'price_decreases' => 0,
                'average_change_percentage' => 0.0,
                'biggest_increase' => null,
                'biggest_decrease' => null,
            ];
        }

        $increases = $priceChanges->where('change_percentage', '>', 0);
        $decreases = $priceChanges->where('change_percentage', '<', 0);

        $biggestIncrease = $increases->sortByDesc('change_percentage')->first();
        $biggestDecrease = $decreases->sortBy('change_percentage')->first();

        return [
            'total_price_changes' => $priceChanges->count(),
            'price_increases' => $increases->count(),
            'price_decreases' => $decreases->count(),
            'average_change_percentage' => round($priceChanges->avg('change_percentage'), 2),
            'biggest_increase' => $biggestIncrease ? $this->formatPriceChangeData($biggestIncrease) : null,
            'biggest_decrease' => $biggestDecrease ? $this->formatPriceChangeData($biggestDecrease) : null,
        ];
    }

    /**
     * Calculate trending products data.
     */
    public function calculateTrendingProducts(Collection $priceChanges, int $limit = 20): array
    {
        $productTrends = $priceChanges->groupBy('product_id')->map(function (Collection $changes, int $productId): array {
            $product = Product::find($productId);
            $totalChangePercentage = $changes->sum('change_percentage');
            $volatilityScore = $this->calculateVolatilityScore($changes);

            $trendDirection = match (true) {
                $totalChangePercentage > 5 => 'strong_upward',
                $totalChangePercentage > 0 => 'upward',
                $totalChangePercentage < -5 => 'strong_downward',
                $totalChangePercentage < 0 => 'downward',
                default => 'stable',
            };

            return [
                'product_id' => $productId,
                'product_name' => $product->name ?? 'Unknown Product',
                'current_price' => $product->price ?? 0.0,
                'price_change_percentage' => round($totalChangePercentage, 2),
                'trend_direction' => $trendDirection,
                'volatility_score' => $volatilityScore,
            ];
        });

        return $productTrends
            ->sortByDesc(static function (array $trend): float {
                return abs($trend['price_change_percentage']);
            })
            ->take($limit)
            ->values()
            ->toArray()
        ;
    }

    /**
     * Calculate price volatility analysis.
     */
    public function calculatePriceVolatilityAnalysis(Collection $priceHistory, int $limit = 15): array
    {
        $productVolatility = $priceHistory->groupBy('product_id')->map(function (Collection $history, int $productId): array {
            $product = $history->first()->product;
            $prices = $history->pluck('price');

            $volatilityScore = $this->calculatePriceVolatility($prices);
            $minPrice = $prices->min();
            $maxPrice = $prices->max();

            $stabilityRating = match (true) {
                $volatilityScore < 0.1 => 'very_stable',
                $volatilityScore < 0.3 => 'stable',
                $volatilityScore < 0.6 => 'moderate',
                $volatilityScore < 1.0 => 'volatile',
                default => 'highly_volatile',
            };

            return [
                'product_id' => $productId,
                'product_name' => $product->name ?? 'Unknown Product',
                'volatility_score' => round($volatilityScore, 3),
                'price_changes_count' => $history->count(),
                'price_range' => [
                    'min' => $minPrice,
                    'max' => $maxPrice,
                ],
                'stability_rating' => $stabilityRating,
            ];
        });

        return $productVolatility
            ->sortByDesc('volatility_score')
            ->take($limit)
            ->values()
            ->toArray()
        ;
    }

    /**
     * Calculate market trends analysis.
     */
    public function calculateMarketTrends(Collection $priceChanges): array
    {
        if ($priceChanges->isEmpty()) {
            return [
                'overall_trend' => 'stable',
                'average_price_change' => 0.0,
                'products_with_increases' => 0,
                'products_with_decreases' => 0,
                'market_volatility' => 0.0,
                'trend_strength' => 'weak',
            ];
        }

        $averageChange = $priceChanges->avg('change_percentage');
        $increases = $priceChanges->where('change_percentage', '>', 0)->count();
        $decreases = $priceChanges->where('change_percentage', '<', 0)->count();

        $overallTrend = match (true) {
            $averageChange > 2 => 'strong_upward',
            $averageChange > 0 => 'upward',
            $averageChange < -2 => 'strong_downward',
            $averageChange < 0 => 'downward',
            default => 'stable',
        };

        $marketVolatility = $this->calculateVolatilityScore($priceChanges);

        $trendStrength = match (true) {
            abs($averageChange) > 5 => 'very_strong',
            abs($averageChange) > 2 => 'strong',
            abs($averageChange) > 0.5 => 'moderate',
            default => 'weak',
        };

        return [
            'overall_trend' => $overallTrend,
            'average_price_change' => round($averageChange, 2),
            'products_with_increases' => $increases,
            'products_with_decreases' => $decreases,
            'market_volatility' => $marketVolatility,
            'trend_strength' => $trendStrength,
        ];
    }

    /**
     * Calculate volatility score for price changes.
     */
    public function calculateVolatilityScore(Collection $changes): float
    {
        if ($changes->isEmpty()) {
            return 0.0;
        }

        $percentages = $changes->pluck('change_percentage');
        $mean = $percentages->avg();
        $variance = $percentages->map(static function ($value) use ($mean) {
            return ($value - $mean) ** 2;
        })->avg();

        return round(sqrt($variance), 3);
    }

    /**
     * Calculate price volatility from price values.
     */
    public function calculatePriceVolatility(Collection $prices): float
    {
        if ($prices->count() < 2) {
            return 0.0;
        }

        $mean = $prices->avg();
        $variance = $prices->map(static function ($price) use ($mean) {
            return ($price - $mean) ** 2;
        })->avg();

        $standardDeviation = sqrt($variance);

        // Return coefficient of variation (standard deviation / mean)
        return $mean > 0 ? $standardDeviation / $mean : 0.0;
    }

    /**
     * Format price change data for display.
     */
    public function formatPriceChangeData(array $change): array
    {
        return [
            'product_id' => $change['product_id'],
            'old_price' => $change['old_price'],
            'new_price' => $change['new_price'],
            'change_percentage' => round($change['change_percentage'], 2),
            'changed_at' => $change['changed_at']->toDateTimeString(),
        ];
    }
}
