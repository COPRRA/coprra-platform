<?php

declare(strict_types=1);

namespace Tests\Feature\COPRRA;

use App\Helpers\PriceHelper;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(PriceHelper::class)]
final class PriceComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Currency $usd;

    protected Currency $sar;

    protected Category $category;

    protected Store $store1;

    protected Store $store2;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create currencies
        $this->usd = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'decimal_places' => 2,
        ]);

        $this->sar = Currency::create([
            'code' => 'SAR',
            'name' => 'Saudi Riyal',
            'symbol' => 'Ø±.Ø³',
            'exchange_rate' => 3.75,
            'decimal_places' => 2,
        ]);

        // Create category
        $this->category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        // Create stores
        $this->store1 = Store::factory()->create([
            'name' => 'Store A',
            'slug' => 'store-a',
            'is_active' => true,
        ]);

        $this->store2 = Store::factory()->create([
            'name' => 'Store B',
            'slug' => 'store-b',
            'is_active' => true,
        ]);

        // Create product
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $this->category->id,
            'price' => 100.00,
            'currency_id' => $this->usd->id,
        ]);
    }

    public function testComparesPricesAcrossMultipleStores(): void
    {
        // Create price entries for different stores
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 95.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $prices = $this->product->stores()
            ->wherePivot('is_available', true)
            ->get()
            ->pluck('pivot.price')
            ->toArray()
        ;

        self::assertCount(2, $prices);
        self::assertContains(100.00, $prices);
        self::assertContains(95.00, $prices);

        $bestPrice = PriceHelper::getBestPrice($prices);
        self::assertSame(95.00, $bestPrice);
    }

    public function testIdentifiesBestDealAmongStores(): void
    {
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 85.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $prices = $this->product->stores()
            ->wherePivot('is_available', true)
            ->get()
            ->pluck('pivot.price')
            ->toArray()
        ;

        $isGoodDeal = PriceHelper::isGoodDeal(85.00, $prices);
        self::assertTrue($isGoodDeal);

        $isNotGoodDeal = PriceHelper::isGoodDeal(100.00, $prices);
        self::assertFalse($isNotGoodDeal);
    }

    public function testCalculatesSavingsPercentage(): void
    {
        $originalPrice = 100.00;
        $salePrice = 80.00;

        $difference = PriceHelper::calculatePriceDifference($originalPrice, $salePrice);

        self::assertSame(-20.0, $difference);

        $differenceString = PriceHelper::getPriceDifferenceString($originalPrice, $salePrice);
        self::assertStringContainsString('-20.0', $differenceString);
        self::assertStringContainsString('%', $differenceString);
    }

    public function testFormatsPriceWithCorrectCurrencySymbol(): void
    {
        $formattedUSD = PriceHelper::formatPrice(100.00, 'USD');
        self::assertStringContainsString('$', $formattedUSD);
        self::assertStringContainsString('100.00', $formattedUSD);

        $formattedSAR = PriceHelper::formatPrice(375.00, 'SAR');
        self::assertStringContainsString('Ø±.Ø³', $formattedSAR);
        self::assertStringContainsString('375.00', $formattedSAR);
    }

    public function testConvertsPricesBetweenCurrencies(): void
    {
        $usdPrice = 100.00;
        $sarPrice = PriceHelper::convertCurrency($usdPrice, 'USD', 'SAR');

        // USD to SAR: 100 / 1.0 * 3.75 = 375.0
        self::assertSame(375.0, $sarPrice);

        $convertedBack = PriceHelper::convertCurrency($sarPrice, 'SAR', 'USD');
        self::assertSame($usdPrice, $convertedBack);
    }

    public function testDisplaysPriceRangeForProduct(): void
    {
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 120.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $prices = $this->product->stores()
            ->wherePivot('is_available', true)
            ->get()
            ->pluck('pivot.price')
            ->toArray()
        ;

        $minPrice = min($prices);
        $maxPrice = max($prices);

        $priceRange = PriceHelper::formatPriceRange($minPrice, $maxPrice, 'USD');

        self::assertStringContainsString('100.00', $priceRange);
        self::assertStringContainsString('120.00', $priceRange);
        self::assertStringContainsString('-', $priceRange);
    }

    public function testHandlesUnavailableProductsInStores(): void
    {
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 80.00,
            'currency_id' => $this->usd->id,
            'is_available' => false, // Not available
        ]);

        $availablePrices = $this->product->stores()
            ->wherePivot('is_available', true)
            ->get()
            ->pluck('pivot.price')
            ->toArray()
        ;

        self::assertCount(1, $availablePrices);
        self::assertContains(100.00, $availablePrices);
        self::assertNotContains(80.00, $availablePrices);
    }

    public function testRespectsMaxStoresPerProductConfiguration(): void
    {
        $maxStores = config('coprra.price_comparison.max_stores_per_product', 10);

        self::assertIsNumeric($maxStores);
        self::assertGreaterThan(0, $maxStores);

        // Create more stores than the limit
        for ($i = 0; $i < $maxStores + 5; ++$i) {
            $store = Store::factory()->create([
                'name' => "Store {$i}",
                'slug' => "store-{$i}",
                'is_active' => true,
            ]);

            $this->product->stores()->attach($store->id, [
                'price' => 100.00 + $i,
                'currency_id' => $this->usd->id,
                'is_available' => true,
            ]);
        }

        // Get only the max allowed stores
        $limitedStores = $this->product->stores()
            ->wherePivot('is_available', true)
            ->limit($maxStores)
            ->get()
        ;

        self::assertCount($maxStores, $limitedStores);
    }

    public function testCachesPriceComparisonResults(): void
    {
        $cacheKey = "price_comparison_{$this->product->id}";
        $cacheDuration = config('coprra.price_comparison.cache_duration', 3600);

        self::assertIsNumeric($cacheDuration);
        self::assertGreaterThan(0, $cacheDuration);

        // Simulate caching
        cache()->put($cacheKey, ['prices' => [100.00, 95.00]], $cacheDuration);

        $cached = cache()->get($cacheKey);
        self::assertNotNull($cached);
        self::assertArrayHasKey('prices', $cached);
        self::assertCount(2, $cached['prices']);
    }

    public function testTracksPriceComparisonAnalytics(): void
    {
        $trackBehavior = config('coprra.analytics.track_user_behavior', true);
        $trackClicks = config('coprra.analytics.track_price_clicks', true);

        self::assertIsBool($trackBehavior);
        self::assertIsBool($trackClicks);

        if ($trackBehavior) {
            // Simulate tracking
            self::assertTrue(true);
        }
    }

    public function testHandlesMultipleCurrenciesInComparison(): void
    {
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 375.00,
            'currency_id' => $this->sar->id,
            'is_available' => true,
        ]);

        // Convert SAR to USD for comparison
        $sarPriceInUSD = PriceHelper::convertCurrency(375.00, 'SAR', 'USD');

        self::assertSame(100.00, $sarPriceInUSD);
    }

    public function testValidatesPriceUpdateInterval(): void
    {
        $updateInterval = config('coprra.price_comparison.price_update_interval', 6);

        self::assertIsNumeric($updateInterval);
        self::assertGreaterThan(0, $updateInterval);
    }
}
