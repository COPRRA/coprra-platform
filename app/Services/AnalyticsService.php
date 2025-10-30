<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Log;

final class AnalyticsService
{
    /**
     * Track an analytics event.
     *
     * @param  array<string, string|int|float|bool|* @method static \App\Models\Brand create(array<string, string|bool|null>|null  $metadata
     */
    public function track(
        string $eventType,
        string $eventName,
        ?int $userId = null,
        ?int $productId = null,
        ?int $categoryId = null,
        ?int $storeId = null,
        ?array $metadata = null
    ): ?AnalyticsEvent {
        if (! config('coprra.analytics.track_user_behavior', true)) {
            return null;
        }

        try {
            return AnalyticsEvent::create([
                'event_type' => $eventType,
                'event_name' => $eventName,
                'user_id' => $userId,
                'product_id' => $productId,
                'category_id' => $categoryId,
                'store_id' => $storeId,
                'metadata' => $metadata,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to track analytics event', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Track price comparison event.
     */
    public function trackPriceComparison(int $productId, ?int $userId = null, array $metadata = []): ?AnalyticsEvent
    {
        return $this->track(
            AnalyticsEvent::TYPE_PRICE_COMPARISON,
            'Price Comparison Viewed',
            $userId,
            $productId,
            null,
            null,
            $metadata
        );
    }

    /**
     * Clean old analytics data.
     */
    public function cleanOldData(int $daysToKeep = 365): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $count = AnalyticsEvent::where('created_at', '<', $cutoffDate)->delete();

        Log::info('Cleaned old analytics data', [
            'days_to_keep' => $daysToKeep,
            'records_deleted' => $count,
        ]);

        return \is_int($count) ? $count : 0;
    }
}
