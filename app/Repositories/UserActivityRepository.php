<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PriceAlert;
use App\Models\Review;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UserActivityRepository
{
    /**
     * Get wishlists with product information in a period.
     */
    public function getWishlistsInPeriod(Carbon $startDate, Carbon $endDate): Collection
    {
        return Wishlist::with('product')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
        ;
    }

    /**
     * Get price alerts with product information in a period.
     */
    public function getPriceAlertsInPeriod(Carbon $startDate, Carbon $endDate): Collection
    {
        return PriceAlert::with('product')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
        ;
    }

    /**
     * Get reviews with product information in a period.
     */
    public function getReviewsInPeriod(Carbon $startDate, Carbon $endDate): Collection
    {
        return Review::with('product')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
        ;
    }

    /**
     * Count wishlists in a period.
     */
    public function countWishlistsInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        return Wishlist::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Count price alerts in a period.
     */
    public function countPriceAlertsInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        return PriceAlert::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Count reviews in a period.
     */
    public function countReviewsInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        return Review::whereBetween('created_at', [$startDate, $endDate])->count();
    }

    /**
     * Count user activity for a specific user and model.
     */
    public function countUserActivity(string $modelClass, int $userId, Carbon $startDate, Carbon $endDate): int
    {
        return $modelClass::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count()
        ;
    }

    /**
     * Get wishlists grouped by date.
     */
    public function getWishlistsByDate(Carbon $startDate, Carbon $endDate): Collection
    {
        return Wishlist::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
        ;
    }

    /**
     * Get price alerts grouped by date.
     */
    public function getPriceAlertsByDate(Carbon $startDate, Carbon $endDate): Collection
    {
        return PriceAlert::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
        ;
    }

    /**
     * Get reviews grouped by date.
     */
    public function getReviewsByDate(Carbon $startDate, Carbon $endDate): Collection
    {
        return Review::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
        ;
    }

    /**
     * Format wishlist activity data.
     */
    public function formatWishlistActivity(Collection $wishlists): array
    {
        return $wishlists->map(static function (Wishlist $wishlist): array {
            return [
                'user_id' => $wishlist->user_id,
                'product_id' => $wishlist->product_id,
                'product_name' => $wishlist->product->name ?? 'Unknown Product',
                'created_at' => $wishlist->created_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Format price alerts activity data.
     */
    public function formatPriceAlertsActivity(Collection $alerts): array
    {
        return $alerts->map(static function (PriceAlert $alert): array {
            return [
                'user_id' => $alert->user_id,
                'product_id' => $alert->product_id,
                'product_name' => $alert->product->name ?? 'Unknown Product',
                'target_price' => $alert->target_price,
                'created_at' => $alert->created_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Format reviews activity data.
     */
    public function formatReviewsActivity(Collection $reviews): array
    {
        return $reviews->map(static function (Review $review): array {
            return [
                'user_id' => $review->user_id,
                'product_id' => $review->product_id,
                'product_name' => $review->product->name ?? 'Unknown Product',
                'rating' => $review->rating,
                'comment' => $review->content ?? '',
                'created_at' => $review->created_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Calculate engagement summary statistics.
     */
    public function calculateEngagementSummary(int $totalWishlists, int $totalPriceAlerts, int $totalReviews, Carbon $startDate, Carbon $endDate, ?string $mostActiveDay): array
    {
        $daysDiff = max(1, $startDate->diffInDays($endDate));

        return [
            'total_wishlists' => $totalWishlists,
            'total_price_alerts' => $totalPriceAlerts,
            'total_reviews' => $totalReviews,
            'average_daily_wishlists' => round($totalWishlists / $daysDiff, 2),
            'average_daily_alerts' => round($totalPriceAlerts / $daysDiff, 2),
            'average_daily_reviews' => round($totalReviews / $daysDiff, 2),
            'most_active_day' => $mostActiveDay,
        ];
    }

    /**
     * Find the most active day from daily activity data.
     */
    public function findMostActiveDay(Collection $wishlistsByDay, Collection $alertsByDay, Collection $reviewsByDay): ?string
    {
        // Combine all activities
        $allDates = collect()
            ->merge($wishlistsByDay->keys())
            ->merge($alertsByDay->keys())
            ->merge($reviewsByDay->keys())
            ->unique()
        ;

        if ($allDates->isEmpty()) {
            return null;
        }

        $dailyTotals = $allDates->mapWithKeys(static function ($date) use ($wishlistsByDay, $alertsByDay, $reviewsByDay) {
            $total = ($wishlistsByDay[$date] ?? 0)
                    + ($alertsByDay[$date] ?? 0)
                    + ($reviewsByDay[$date] ?? 0);

            return [$date => $total];
        });

        return $dailyTotals->sortDesc()->keys()->first();
    }

    /**
     * Get unique active user IDs from activity collections.
     */
    public function getUniqueActiveUserIds(array $wishlistActivity, array $priceAlertsActivity, array $reviewsActivity): int
    {
        return collect()
            ->merge(collect($wishlistActivity)->pluck('user_id'))
            ->merge(collect($priceAlertsActivity)->pluck('user_id'))
            ->merge(collect($reviewsActivity)->pluck('user_id'))
            ->unique()
            ->count();
    }
}
