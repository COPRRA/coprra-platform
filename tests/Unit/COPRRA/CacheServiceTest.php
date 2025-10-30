<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CacheServiceTest extends TestCase
{
    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = new CacheService();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function testItGeneratesCorrectProductCacheKey(): void
    {
        $key = $this->cacheService->getProductKey(123);

        self::assertSame('product:123', $key);
    }

    public function testItGeneratesCorrectCategoryCacheKey(): void
    {
        $key = $this->cacheService->getCategoryKey(456);

        self::assertSame('category:456', $key);
    }

    public function testItGeneratesCorrectStoreCacheKey(): void
    {
        $key = $this->cacheService->getStoreKey(789);

        self::assertSame('store:789', $key);
    }

    public function testItGeneratesCorrectPriceComparisonCacheKey(): void
    {
        $key = $this->cacheService->getPriceComparisonKey(123);

        self::assertSame('price_comparison:123', $key);
    }

    public function testItGeneratesCorrectExchangeRateCacheKey(): void
    {
        $key = $this->cacheService->getExchangeRateKey('USD', 'EUR');

        self::assertSame('exchange_rate:USD_EUR', $key);
    }

    public function testItGeneratesCorrectSearchCacheKey(): void
    {
        $key = $this->cacheService->getSearchKey('laptop');

        self::assertStringStartsWith('search:', $key);
        self::assertStringContainsString(md5('laptop'), $key);
    }

    public function testItGeneratesSearchCacheKeyWithFilters(): void
    {
        $filters = ['category' => 'electronics', 'price_min' => 100];
        $key = $this->cacheService->getSearchKey('laptop', $filters);

        self::assertStringStartsWith('search:', $key);
        self::assertStringContainsString(md5('laptop'), $key);
    }

    public function testItCachesProductData(): void
    {
        $productData = ['id' => 123, 'name' => 'Test Product'];

        $result = $this->cacheService->cacheProduct(123, $productData);

        self::assertTrue($result);

        $cached = $this->cacheService->getCachedProduct(123);
        self::assertSame($productData, $cached);
    }

    public function testItCachesPriceComparisonData(): void
    {
        $priceData = [
            'product_id' => 123,
            'prices' => [
                ['store' => 'Store A', 'price' => 100.00],
                ['store' => 'Store B', 'price' => 95.00],
            ],
        ];

        $result = $this->cacheService->cachePriceComparison(123, $priceData);

        self::assertTrue($result);

        $cached = $this->cacheService->getCachedPriceComparison(123);
        self::assertSame($priceData, $cached);
    }

    public function testItCachesSearchResults(): void
    {
        $searchResults = [
            ['id' => 1, 'name' => 'Product 1'],
            ['id' => 2, 'name' => 'Product 2'],
        ];

        $result = $this->cacheService->cacheSearchResults('laptop', [], $searchResults);

        self::assertTrue($result);

        $cached = $this->cacheService->getCachedSearchResults('laptop');
        self::assertSame($searchResults, $cached);
    }

    public function testItInvalidatesProductCache(): void
    {
        $productData = ['id' => 123, 'name' => 'Test Product'];
        $this->cacheService->cacheProduct(123, $productData);

        self::assertNotNull($this->cacheService->getCachedProduct(123));

        $this->cacheService->invalidateProduct(123);

        self::assertNull($this->cacheService->getCachedProduct(123));
    }

    public function testItInvalidatesCategoryCache(): void
    {
        $key = $this->cacheService->getCategoryKey(456);
        Cache::put($key, ['id' => 456, 'name' => 'Test Category'], 3600);

        self::assertNotNull(Cache::get($key));

        $this->cacheService->invalidateCategory(456);

        self::assertNull(Cache::get($key));
    }

    public function testItInvalidatesStoreCache(): void
    {
        $key = $this->cacheService->getStoreKey(789);
        Cache::put($key, ['id' => 789, 'name' => 'Test Store'], 3600);

        self::assertNotNull(Cache::get($key));

        $this->cacheService->invalidateStore(789);

        self::assertNull(Cache::get($key));
    }

    public function testItReturnsNullForNonExistentCache(): void
    {
        $cached = $this->cacheService->getCachedProduct(999);

        self::assertNull($cached);
    }

    public function testItReturnsCacheStatistics(): void
    {
        $stats = $this->cacheService->getStatistics();

        self::assertIsArray($stats);
        self::assertArrayHasKey('driver', $stats);
        self::assertArrayHasKey('prefixes', $stats);
        self::assertArrayHasKey('durations', $stats);
    }

    public function testItUsesRememberMethodCorrectly(): void
    {
        $key = 'test_remember_key';
        $value = 'test_value';

        $result = $this->cacheService->remember($key, 3600, static function () use ($value) {
            return $value;
        });

        self::assertSame($value, $result);

        // Second call should return cached value
        $result2 = $this->cacheService->remember($key, 3600, static function () {
            return 'different_value';
        });

        self::assertSame($value, $result2);
    }

    public function testItClearsAllCache(): void
    {
        // Add some cache entries
        $this->cacheService->cacheProduct(1, ['name' => 'Product 1']);
        $this->cacheService->cacheProduct(2, ['name' => 'Product 2']);

        self::assertNotNull($this->cacheService->getCachedProduct(1));
        self::assertNotNull($this->cacheService->getCachedProduct(2));

        // Clear all cache
        $this->cacheService->clearAll();

        self::assertNull($this->cacheService->getCachedProduct(1));
        self::assertNull($this->cacheService->getCachedProduct(2));
    }

    public function testItRespectsCustomCacheDuration(): void
    {
        $productData = ['id' => 123, 'name' => 'Test Product'];
        $customDuration = 60; // 1 minute

        $this->cacheService->cacheProduct(123, $productData, $customDuration);

        $cached = $this->cacheService->getCachedProduct(123);
        self::assertSame($productData, $cached);
    }

    public function testItHandlesSearchWithDifferentFilters(): void
    {
        $results1 = [['id' => 1]];
        $results2 = [['id' => 2]];

        $filters1 = ['category' => 'electronics'];
        $filters2 = ['category' => 'books'];

        $this->cacheService->cacheSearchResults('laptop', $filters1, $results1);
        $this->cacheService->cacheSearchResults('laptop', $filters2, $results2);

        $cached1 = $this->cacheService->getCachedSearchResults('laptop', $filters1);
        $cached2 = $this->cacheService->getCachedSearchResults('laptop', $filters2);

        self::assertSame($results1, $cached1);
        self::assertSame($results2, $cached2);
        self::assertNotSame($cached1, $cached2);
    }
}
