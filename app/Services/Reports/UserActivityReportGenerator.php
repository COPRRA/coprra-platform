<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Repositories\UserActivityRepository;
use Carbon\Carbon;

class UserActivityReportGenerator
{
    protected $userActivityRepository;

    public function __construct(UserActivityRepository $userActivityRepository)
    {
        $this->userActivityRepository = $userActivityRepository;
    }

    /**
     * Generate comprehensive user activity report.
     *
     * @return array{
     *     total_users_active: int,
     *     wishlist_activity: array<int, array<string, mixed>>,
     *     price_alerts_activity: array<int, array<string, mixed>>,
     *     reviews_activity: array<int, array<string, mixed>>,
     *     engagement_summary: array<string, mixed>
     * }
     */
    public function generateReport(Carbon $startDate, Carbon $endDate): array
    {
        $wishlistActivity = $this->getWishlistActivity($startDate, $endDate);
        $priceAlertsActivity = $this->getPriceAlertsActivity($startDate, $endDate);
        $reviewsActivity = $this->getReviewsActivity($startDate, $endDate);

        // Get unique active users across all activities
        $activeUserIds = collect()
            ->merge(collect($wishlistActivity)->pluck('user_id'))
            ->merge(collect($priceAlertsActivity)->pluck('user_id'))
            ->merge(collect($reviewsActivity)->pluck('user_id'))
            ->unique()
            ->count()
        ;

        return [
            'total_users_active' => $activeUserIds,
            'wishlist_activity' => $wishlistActivity,
            'price_alerts_activity' => $priceAlertsActivity,
            'reviews_activity' => $reviewsActivity,
            'engagement_summary' => $this->getEngagementSummary($startDate, $endDate),
        ];
    }

    /**
     * Get user activity summary for a specific user.
     *
     * @return array{
     *     wishlist_adds: int,
     *     price_alerts_created: int,
     *     reviews_written: int,
     *     total_activity: int
     * }
     */
    public function getUserActivitySummary(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $wishlists = $this->countUserActivity(Wishlist::class, $userId, $startDate, $endDate);
        $priceAlerts = $this->countUserActivity(PriceAlert::class, $userId, $startDate, $endDate);
        $reviews = $this->countUserActivity(Review::class, $userId, $startDate, $endDate);

        return [
            'wishlist_adds' => $wishlists,
            'price_alerts_created' => $priceAlerts,
            'reviews_written' => $reviews,
            'total_activity' => $wishlists + $priceAlerts + $reviews,
        ];
    }

    /**
     * Get overall activity summary for the period.
     */
    private function getOverallActivitySummary(Carbon $startDate, Carbon $endDate): array
    {
        $totalWishlists = $this->userActivityRepository->countWishlistsInPeriod($startDate, $endDate);
        $totalPriceAlerts = $this->userActivityRepository->countPriceAlertsInPeriod($startDate, $endDate);
        $totalReviews = $this->userActivityRepository->countReviewsInPeriod($startDate, $endDate);

        return [
            'total_wishlists' => $totalWishlists,
            'total_price_alerts' => $totalPriceAlerts,
            'total_reviews' => $totalReviews,
            'total_activities' => $totalWishlists + $totalPriceAlerts + $totalReviews,
        ];
    }

    /**
     * Get wishlist activity details.
     */
    private function getWishlistActivity(Carbon $startDate, Carbon $endDate): array
    {
        $wishlists = $this->userActivityRepository->getWishlistsInPeriod($startDate, $endDate);

        return $this->userActivityRepository->formatWishlistActivity($wishlists);
    }

    /**
     * Get price alerts activity details.
     */
    private function getPriceAlertsActivity(Carbon $startDate, Carbon $endDate): array
    {
        $alerts = $this->userActivityRepository->getPriceAlertsInPeriod($startDate, $endDate);

        return $this->userActivityRepository->formatPriceAlertsActivity($alerts);
    }

    /**
     * Get reviews activity details.
     */
    private function getReviewsActivity(Carbon $startDate, Carbon $endDate): array
    {
        $reviews = $this->userActivityRepository->getReviewsInPeriod($startDate, $endDate);

        return $this->userActivityRepository->formatReviewsActivity($reviews);
    }

    /**
     * Get engagement summary with statistics.
     */
    private function getEngagementSummary(array $wishlistActivity, array $priceAlertsActivity, array $reviewsActivity, Carbon $startDate, Carbon $endDate): array
    {
        $totalWishlists = \count($wishlistActivity);
        $totalPriceAlerts = \count($priceAlertsActivity);
        $totalReviews = \count($reviewsActivity);

        // Calculate unique active users
        $uniqueUsers = $this->userActivityRepository->getUniqueActiveUserIds($wishlistActivity, $priceAlertsActivity, $reviewsActivity);

        // Get most active day
        $mostActiveDay = $this->getMostActiveDay($startDate, $endDate);

        return $this->userActivityRepository->calculateEngagementSummary(
            $totalWishlists,
            $totalPriceAlerts,
            $totalReviews,
            $startDate,
            $endDate,
            $mostActiveDay
        ) + ['unique_active_users' => $uniqueUsers];
    }

    /**
     * Find the most active day in the period.
     */
    private function getMostActiveDay(Carbon $startDate, Carbon $endDate): ?string
    {
        $wishlistsByDay = $this->userActivityRepository->getWishlistsByDate($startDate, $endDate);
        $alertsByDay = $this->userActivityRepository->getPriceAlertsByDate($startDate, $endDate);
        $reviewsByDay = $this->userActivityRepository->getReviewsByDate($startDate, $endDate);

        return $this->userActivityRepository->findMostActiveDay($wishlistsByDay, $alertsByDay, $reviewsByDay);
    }
}
