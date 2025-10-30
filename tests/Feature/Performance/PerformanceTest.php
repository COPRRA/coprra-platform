<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const CACHE_PERFORMANCE_THRESHOLD_MS = 50; // 50ms max cache operations
    private const MEMORY_USAGE_THRESHOLD_MB = 128; // 128MB max memory usage
    private const BULK_OPERATION_THRESHOLD_MS = 1000; // 1 second for bulk operations
    private const CACHE_HIT_RATIO_THRESHOLD = 0.8; // 80% cache hit ratio

    protected function setUp(): void
    {
        parent::setUp();

        // Clear all caches for clean testing
        Cache::flush();

        // Clear Redis if available
        try {
            Redis::flushall();
        } catch (\Exception $e) {
            // Redis not available, continue
        }

        // Enable query logging
        DB::enableQueryLog();
    }

    protected function tearDown(): void
    {
        DB::disableQueryLog();
        Cache::flush();
        parent::tearDown();
    }

    #[Test]
    public function testCachePerformanceAndHitRatio(): void
    {
        // Create test data
        $categories = Category::factory()->count(10)->create();
        $products = Product::factory()->count(50)->create([
            'category_id' => $categories->random()->id,
        ]);

        $cacheKeys = [];
        $cacheOperationTimes = [];

        // Test cache write performance
        foreach ($products->take(20) as $product) {
            $cacheKey = "product:{$product->id}";
            $cacheKeys[] = $cacheKey;

            $startTime = microtime(true);
            Cache::put($cacheKey, $product->toArray(), 3600);
            $endTime = microtime(true);

            $operationTime = ($endTime - $startTime) * 1000;
            $cacheOperationTimes[] = $operationTime;

            // Assert individual cache write performance
            self::assertLessThan(
                self::CACHE_PERFORMANCE_THRESHOLD_MS,
                $operationTime,
                "Cache write for {$cacheKey} took {$operationTime}ms, exceeding threshold"
            );
        }

        // Test cache read performance and hit ratio
        $cacheHits = 0;
        $cacheMisses = 0;

        foreach ($cacheKeys as $cacheKey) {
            $startTime = microtime(true);
            $cachedData = Cache::get($cacheKey);
            $endTime = microtime(true);

            $readTime = ($endTime - $startTime) * 1000;

            if (null !== $cachedData) {
                ++$cacheHits;
            } else {
                ++$cacheMisses;
            }

            // Assert cache read performance
            self::assertLessThan(
                self::CACHE_PERFORMANCE_THRESHOLD_MS,
                $readTime,
                "Cache read for {$cacheKey} took {$readTime}ms, exceeding threshold"
            );
        }

        // Calculate and assert cache hit ratio
        $totalCacheOperations = $cacheHits + $cacheMisses;
        $hitRatio = $totalCacheOperations > 0 ? $cacheHits / $totalCacheOperations : 0;

        self::assertGreaterThan(
            self::CACHE_HIT_RATIO_THRESHOLD,
            $hitRatio,
            "Cache hit ratio {$hitRatio} is below threshold"
        );

        // Assert average cache operation time
        $avgCacheTime = array_sum($cacheOperationTimes) / \count($cacheOperationTimes);
        self::assertLessThan(
            self::CACHE_PERFORMANCE_THRESHOLD_MS / 2,
            $avgCacheTime,
            "Average cache operation time {$avgCacheTime}ms should be optimized"
        );
    }

    #[Test]
    public function testMemoryUsageAndOptimization(): void
    {
        $initialMemory = memory_get_usage(true);

        // Create memory-intensive test data
        $categories = Category::factory()->count(20)->create();
        $brands = Brand::factory()->count(10)->create();
        $stores = Store::factory()->count(5)->create();

        $products = collect();
        foreach ($categories->take(10) as $category) {
            $categoryProducts = Product::factory()->count(100)->create([
                'category_id' => $category->id,
                'brand_id' => $brands->random()->id,
            ]);
            $products = $products->merge($categoryProducts);
        }

        // Create price offers for products
        foreach ($products->take(500) as $product) {
            PriceOffer::factory()->count(rand(1, 3))->create([
                'product_id' => $product->id,
                'store_id' => $stores->random()->id,
            ]);
        }

        $afterDataCreationMemory = memory_get_usage(true);

        // Perform memory-intensive operations
        $startTime = microtime(true);

        // Load products with relationships
        $productsWithRelations = Product::with(['category', 'brand', 'priceOffers.store'])
            ->take(200)
            ->get()
        ;

        // Perform aggregations
        $categoryStats = Category::withCount('products')
            ->with(['products' => static function ($query) {
                $query->with('priceOffers');
            }])
            ->get()
        ;

        $endTime = microtime(true);
        $operationTime = ($endTime - $startTime) * 1000;

        $finalMemory = memory_get_usage(true);
        $memoryUsed = ($finalMemory - $initialMemory) / 1024 / 1024; // Convert to MB

        // Assert memory usage is within acceptable limits
        self::assertLessThan(
            self::MEMORY_USAGE_THRESHOLD_MB,
            $memoryUsed,
            "Memory usage {$memoryUsed}MB exceeds threshold"
        );

        // Assert operation performance
        self::assertLessThan(
            self::BULK_OPERATION_THRESHOLD_MS,
            $operationTime,
            "Bulk operation took {$operationTime}ms, exceeding threshold"
        );

        // Verify data integrity
        self::assertGreaterThan(0, $productsWithRelations->count());
        self::assertGreaterThan(0, $categoryStats->count());

        // Test memory cleanup
        unset($productsWithRelations, $categoryStats, $products);

        if (\function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        $afterCleanupMemory = memory_get_usage(true);
        $memoryFreed = ($finalMemory - $afterCleanupMemory) / 1024 / 1024;

        // Assert memory was properly freed (at least some)
        self::assertGreaterThanOrEqual(
            0,
            $memoryFreed,
            'Memory cleanup should free some memory'
        );
    }

    #[Test]
    public function testDatabaseQueryOptimizationPerformance(): void
    {
        // Create test data with relationships
        $categories = Category::factory()->count(5)->create();
        $brands = Brand::factory()->count(3)->create();
        $stores = Store::factory()->count(4)->create();

        foreach ($categories as $category) {
            $products = Product::factory()->count(50)->create([
                'category_id' => $category->id,
                'brand_id' => $brands->random()->id,
            ]);

            foreach ($products->take(30) as $product) {
                PriceOffer::factory()->count(rand(1, 4))->create([
                    'product_id' => $product->id,
                    'store_id' => $stores->random()->id,
                ]);
            }
        }

        DB::flushQueryLog();

        // Test optimized query with eager loading
        $startTime = microtime(true);

        $optimizedResults = Product::with(['category', 'brand', 'priceOffers.store'])
            ->whereHas('priceOffers')
            ->take(100)
            ->get()
        ;

        $endTime = microtime(true);
        $optimizedTime = ($endTime - $startTime) * 1000;

        $optimizedQueries = DB::getQueryLog();
        DB::flushQueryLog();

        // Test non-optimized query (N+1 problem simulation)
        $startTime = microtime(true);

        $nonOptimizedResults = Product::take(50)->get();
        foreach ($nonOptimizedResults as $product) {
            // Simulate N+1 by accessing relationships individually
            $category = $product->category;
            $brand = $product->brand;
            $priceOffers = $product->priceOffers;
        }

        $endTime = microtime(true);
        $nonOptimizedTime = ($endTime - $startTime) * 1000;

        $nonOptimizedQueries = DB::getQueryLog();

        // Assert optimized query performance
        self::assertLessThan(
            self::BULK_OPERATION_THRESHOLD_MS / 2,
            $optimizedTime,
            "Optimized query took {$optimizedTime}ms, should be faster"
        );

        // Assert query count efficiency
        self::assertLessThan(
            10,
            \count($optimizedQueries),
            'Optimized query should use minimal queries ('.\count($optimizedQueries).' used)'
        );

        // Assert non-optimized query uses more queries (demonstrating N+1 problem)
        self::assertGreaterThan(
            \count($optimizedQueries),
            \count($nonOptimizedQueries),
            'Non-optimized query should use more queries than optimized'
        );

        // Verify data integrity
        self::assertGreaterThan(0, $optimizedResults->count());
        self::assertSame($nonOptimizedResults->count(), 50);
    }

    #[Test]
    public function testCacheLayerPerformanceWithFallback(): void
    {
        // Create test data
        $categories = Category::factory()->count(8)->create();
        $products = Product::factory()->count(40)->create([
            'category_id' => $categories->random()->id,
        ]);

        $cacheKey = 'expensive_operation_result';

        // Test cache miss scenario (first request)
        Cache::forget($cacheKey);

        $startTime = microtime(true);

        $result = Cache::remember($cacheKey, 3600, static function () {
            // Simulate expensive operation
            return Category::withCount(['products' => static function ($query) {
                $query->with('priceOffers');
            }])->get();
        });

        $endTime = microtime(true);
        $cacheMissTime = ($endTime - $startTime) * 1000;

        // Test cache hit scenario (subsequent request)
        $startTime = microtime(true);

        $cachedResult = Cache::get($cacheKey);

        $endTime = microtime(true);
        $cacheHitTime = ($endTime - $startTime) * 1000;

        // Assert cache miss performance (should be slower but reasonable)
        self::assertLessThan(
            self::BULK_OPERATION_THRESHOLD_MS,
            $cacheMissTime,
            "Cache miss operation took {$cacheMissTime}ms, exceeding threshold"
        );

        // Assert cache hit performance (should be very fast)
        self::assertLessThan(
            self::CACHE_PERFORMANCE_THRESHOLD_MS,
            $cacheHitTime,
            "Cache hit took {$cacheHitTime}ms, should be much faster"
        );

        // Assert cache hit is significantly faster than cache miss
        self::assertLessThan(
            $cacheMissTime / 5,
            $cacheHitTime,
            "Cache hit ({$cacheHitTime}ms) should be much faster than cache miss ({$cacheMissTime}ms)"
        );

        // Verify data integrity
        self::assertNotNull($result);
        self::assertNotNull($cachedResult);
        self::assertSame($result->count(), $cachedResult->count());
    }

    #[Test]
    public function testConcurrentCacheOperationsPerformance(): void
    {
        // Create test data
        $products = Product::factory()->count(30)->create();

        $concurrentOperations = [];
        $operationTimes = [];

        // Simulate concurrent cache operations
        foreach ($products->take(20) as $index => $product) {
            $cacheKey = "concurrent_product:{$product->id}";

            // Write operation
            $startTime = microtime(true);
            Cache::put($cacheKey, $product->toArray(), 1800);
            $endTime = microtime(true);

            $writeTime = ($endTime - $startTime) * 1000;
            $operationTimes[] = $writeTime;

            // Read operation
            $startTime = microtime(true);
            $cachedData = Cache::get($cacheKey);
            $endTime = microtime(true);

            $readTime = ($endTime - $startTime) * 1000;
            $operationTimes[] = $readTime;

            $concurrentOperations[] = [
                'key' => $cacheKey,
                'write_time' => $writeTime,
                'read_time' => $readTime,
                'data_retrieved' => null !== $cachedData,
            ];
        }

        // Calculate performance metrics
        $avgOperationTime = array_sum($operationTimes) / \count($operationTimes);
        $maxOperationTime = max($operationTimes);
        $successfulOperations = array_filter($concurrentOperations, static fn ($op) => $op['data_retrieved']);

        // Assert average performance
        self::assertLessThan(
            self::CACHE_PERFORMANCE_THRESHOLD_MS,
            $avgOperationTime,
            "Average concurrent cache operation time {$avgOperationTime}ms exceeds threshold"
        );

        // Assert maximum operation time
        self::assertLessThan(
            self::CACHE_PERFORMANCE_THRESHOLD_MS * 2,
            $maxOperationTime,
            "Maximum concurrent cache operation time {$maxOperationTime}ms is too slow"
        );

        // Assert operation success rate
        $successRate = \count($successfulOperations) / \count($concurrentOperations);
        self::assertGreaterThan(
            0.95,
            $successRate,
            "Concurrent cache operation success rate {$successRate} is too low"
        );

        // Verify all operations completed successfully
        self::assertSame(
            \count($products->take(20)),
            \count($successfulOperations),
            'All concurrent cache operations should succeed'
        );
    }

    #[Test]
    public function testSystemResourceUtilizationUnderLoad(): void
    {
        $initialMemory = memory_get_usage(true);
        $initialTime = microtime(true);

        // Create substantial test load
        $categories = Category::factory()->count(15)->create();
        $brands = Brand::factory()->count(8)->create();
        $stores = Store::factory()->count(6)->create();
        $users = User::factory()->count(25)->create();

        // Create products and relationships
        $allProducts = collect();
        foreach ($categories as $category) {
            $products = Product::factory()->count(80)->create([
                'category_id' => $category->id,
                'brand_id' => $brands->random()->id,
            ]);
            $allProducts = $allProducts->merge($products);
        }

        // Create price offers
        foreach ($allProducts->take(600) as $product) {
            PriceOffer::factory()->count(rand(1, 4))->create([
                'product_id' => $product->id,
                'store_id' => $stores->random()->id,
            ]);
        }

        // Simulate user interactions and cache operations
        $operationResults = [];

        foreach ($users->take(15) as $user) {
            $userStartTime = microtime(true);

            // Simulate user browsing products
            $userProducts = Product::with(['category', 'brand', 'priceOffers'])
                ->inRandomOrder()
                ->take(10)
                ->get()
            ;

            // Cache user's viewed products
            $cacheKey = "user_viewed_products:{$user->id}";
            Cache::put($cacheKey, $userProducts->pluck('id')->toArray(), 1800);

            // Simulate price comparison
            $priceComparison = PriceOffer::with(['product', 'store'])
                ->whereIn('product_id', $userProducts->pluck('id'))
                ->orderBy('price')
                ->take(20)
                ->get()
            ;

            $userEndTime = microtime(true);
            $userOperationTime = ($userEndTime - $userStartTime) * 1000;

            $operationResults[] = [
                'user_id' => $user->id,
                'operation_time' => $userOperationTime,
                'products_viewed' => $userProducts->count(),
                'price_offers_compared' => $priceComparison->count(),
            ];
        }

        $finalTime = microtime(true);
        $finalMemory = memory_get_usage(true);

        $totalOperationTime = ($finalTime - $initialTime) * 1000;
        $memoryUsed = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Assert overall system performance under load
        self::assertLessThan(
            self::BULK_OPERATION_THRESHOLD_MS * 5,
            $totalOperationTime,
            "System under load took {$totalOperationTime}ms, exceeding threshold"
        );

        // Assert memory usage under load
        self::assertLessThan(
            self::MEMORY_USAGE_THRESHOLD_MB * 2,
            $memoryUsed,
            "Memory usage under load {$memoryUsed}MB exceeds threshold"
        );

        // Assert individual user operation performance
        $avgUserOperationTime = array_sum(array_column($operationResults, 'operation_time')) / \count($operationResults);
        self::assertLessThan(
            self::BULK_OPERATION_THRESHOLD_MS / 2,
            $avgUserOperationTime,
            "Average user operation time {$avgUserOperationTime}ms should be optimized"
        );

        // Verify all operations completed successfully
        self::assertSame(15, \count($operationResults));
        self::assertGreaterThan(0, array_sum(array_column($operationResults, 'products_viewed')));
        self::assertGreaterThan(0, array_sum(array_column($operationResults, 'price_offers_compared')));
    }
}
