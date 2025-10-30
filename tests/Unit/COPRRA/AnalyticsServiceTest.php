<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(AnalyticsService::class)]
#[CoversClass(AnalyticsEvent::class)]
final class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testItTracksPriceComparisonEvent(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $event = $analyticsService->trackPriceComparison($product->id, $user->id);

        self::assertInstanceOf(AnalyticsEvent::class, $event);
        self::assertSame(AnalyticsEvent::TYPE_PRICE_COMPARISON, $event->event_type);
        self::assertSame($product->id, $event->product_id);
        self::assertSame($user->id, $event->user_id);
    }

    public function testItTracksProductViewEvent(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $event = $analyticsService->trackProductView($product->id);

        self::assertInstanceOf(AnalyticsEvent::class, $event);
        self::assertSame(AnalyticsEvent::TYPE_PRODUCT_VIEW, $event->event_type);
        self::assertSame($product->id, $event->product_id);
    }

    public function testItTracksSearchEvent(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $query = 'laptop';
        $filters = ['category' => 'electronics'];

        $event = $analyticsService->trackSearch($query, null, $filters);

        self::assertInstanceOf(AnalyticsEvent::class, $event);
        self::assertSame(AnalyticsEvent::TYPE_SEARCH, $event->event_type);
        self::assertSame($query, $event->metadata['query']);
        self::assertSame($filters, $event->metadata['filters']);
    }

    public function testItTracksStoreClickEvent(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();
        $store = Store::factory()->create();

        $event = $analyticsService->trackStoreClick($store->id, $product->id);

        self::assertInstanceOf(AnalyticsEvent::class, $event);
        self::assertSame(AnalyticsEvent::TYPE_STORE_CLICK, $event->event_type);
        self::assertSame($store->id, $event->store_id);
        self::assertSame($product->id, $event->product_id);
    }

    public function testItGetsMostViewedProducts(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Create view events
        for ($i = 0; $i < 5; ++$i) {
            $analyticsService->trackProductView($product1->id);
        }

        for ($i = 0; $i < 3; ++$i) {
            $analyticsService->trackProductView($product2->id);
        }

        $mostViewed = $analyticsService->getMostViewedProducts(10, 30);

        self::assertCount(2, $mostViewed);
        self::assertSame($product1->id, $mostViewed[0]['product_id']);
        self::assertSame(5, $mostViewed[0]['view_count']);
    }

    public function testItGetsMostSearchedQueries(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $analyticsService->trackSearch('laptop');
        $analyticsService->trackSearch('laptop');
        $analyticsService->trackSearch('phone');

        $mostSearched = $analyticsService->getMostSearchedQueries(10, 30);

        self::assertArrayHasKey('laptop', $mostSearched);
        self::assertSame(2, $mostSearched['laptop']);
        self::assertArrayHasKey('phone', $mostSearched);
        self::assertSame(1, $mostSearched['phone']);
    }

    public function testItGetsMostPopularStores(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();

        // Create click events
        for ($i = 0; $i < 4; ++$i) {
            $analyticsService->trackStoreClick($store1->id, $product->id);
        }

        for ($i = 0; $i < 2; ++$i) {
            $analyticsService->trackStoreClick($store2->id, $product->id);
        }

        $mostPopular = $analyticsService->getMostPopularStores(10, 30);

        self::assertCount(2, $mostPopular);
        self::assertSame($store1->id, $mostPopular[0]['store_id']);
        self::assertSame(4, $mostPopular[0]['click_count']);
    }

    public function testItGetsPriceComparisonStatistics(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();
        $user = User::factory()->create();

        for ($i = 0; $i < 5; ++$i) {
            $analyticsService->trackPriceComparison($product->id, $user->id);
        }

        $stats = $analyticsService->getPriceComparisonStats(30);

        self::assertSame(5, $stats['total_comparisons']);
        self::assertSame(1, $stats['unique_products']);
        self::assertSame(1, $stats['unique_users']);
        self::assertIsFloat($stats['average_per_day']);
    }

    public function testItGetsDashboardData(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $analyticsService->trackProductView($product->id);
        $analyticsService->trackPriceComparison($product->id);
        $analyticsService->trackSearch('laptop');

        $dashboardData = $analyticsService->getDashboardData(30);

        self::assertArrayHasKey('overview', $dashboardData);
        self::assertArrayHasKey('price_comparisons', $dashboardData);
        self::assertArrayHasKey('most_viewed_products', $dashboardData);
        self::assertArrayHasKey('most_searched_queries', $dashboardData);
        self::assertArrayHasKey('most_popular_stores', $dashboardData);
    }

    public function testItCleansOldAnalyticsData(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        // Create old and recent events
        $oldEvent = AnalyticsEvent::factory()->create([
            'created_at' => now()->subDays(100),
        ]);

        $recentEvent = AnalyticsEvent::factory()->create([
            'created_at' => now()->subDays(10),
        ]);

        $cleanedCount = $analyticsService->cleanOldData(90);

        self::assertSame(1, $cleanedCount);
        $this->assertDatabaseMissing('analytics_events', ['id' => $oldEvent->id]);
        $this->assertDatabaseHas('analytics_events', ['id' => $recentEvent->id]);
    }

    public function testItDoesNotTrackWhenDisabled(): void
    {
        // Disable tracking
        config(['coprra.analytics.track_user_behavior' => false]);

        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $event = $analyticsService->trackProductView($product->id);

        self::assertNull($event);
        $this->assertDatabaseCount('analytics_events', 0);
    }

    public function testItHandlesNullUserId(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $event = $analyticsService->trackPriceComparison($product->id, null);

        self::assertInstanceOf(AnalyticsEvent::class, $event);
        self::assertNull($event->user_id);
    }

    public function testItHandlesEmptyMetadata(): void
    {
        $analyticsService = $this->app->make(AnalyticsService::class);
        $product = Product::factory()->create();

        $event = $analyticsService->trackPriceComparison($product->id, null, []);

        self::assertInstanceOf(AnalyticsEvent::class, $event);
        self::assertSame([], $event->metadata);
    }
}
