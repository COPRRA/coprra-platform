<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\CreatesApplication;

/**
 * Optimized Test Case.
 *
 * Base test class with performance optimizations and common utilities
 * for all test types in the application.
 */
abstract class OptimizedTestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithFaker;

    /**
     * Indicates if the test should use optimizations.
     */
    protected bool $useOptimizations = true;

    /**
     * Test type for specific optimizations.
     */
    protected string $testType = 'default';

    /**
     * Tables to clean up after test.
     */
    protected array $tablesToCleanup = [];

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->useOptimizations) {
            $this->applyOptimizations();
        }

        $this->setupTestData();
    }

    /**
     * Clean up after test.
     */
    protected function tearDown(): void
    {
        if (! empty($this->tablesToCleanup)) {
            TestPerformanceOptimizer::cleanupTestData($this->tablesToCleanup);
        }

        parent::tearDown();
    }

    /**
     * Apply performance optimizations based on test type.
     */
    protected function applyOptimizations(): void
    {
        TestPerformanceOptimizer::optimizeForTestType($this->testType);
    }

    /**
     * Setup common test data.
     */
    protected function setupTestData(): void
    {
        // Override in child classes for specific test data setup
    }

    /**
     * Create test data efficiently in batches.
     */
    protected function createTestDataInBatches(string $model, array $data, int $batchSize = 100): void
    {
        TestPerformanceOptimizer::createTestDataInBatches($model, $data, $batchSize);
    }

    /**
     * Measure test execution performance.
     */
    protected function measurePerformance(callable $callback): array
    {
        return TestPerformanceOptimizer::measureExecutionTime($callback);
    }

    /**
     * Profile the current test.
     */
    protected function profileCurrentTest(callable $test): array
    {
        $testName = $this->getName();

        return TestPerformanceOptimizer::profileTest($testName, $test);
    }

    /**
     * Assert execution time is within acceptable limits.
     */
    protected function assertExecutionTimeWithin(float $maxSeconds, callable $callback): void
    {
        $performance = $this->measurePerformance($callback);

        self::assertLessThanOrEqual(
            $maxSeconds,
            $performance['execution_time'],
            "Test execution time ({$performance['execution_time']}s) exceeded limit ({$maxSeconds}s)"
        );
    }

    /**
     * Assert memory usage is within acceptable limits.
     */
    protected function assertMemoryUsageWithin(int $maxBytes, callable $callback): void
    {
        $performance = $this->measurePerformance($callback);

        self::assertLessThanOrEqual(
            $maxBytes,
            $performance['memory_usage'],
            "Test memory usage ({$performance['memory_usage']} bytes) exceeded limit ({$maxBytes} bytes)"
        );
    }

    /**
     * Create mock data for testing.
     */
    protected function createMockData(string $type, int $count = 1): array
    {
        switch ($type) {
            case 'user':
                return $this->createMockUsers($count);

            case 'product':
                return $this->createMockProducts($count);

            case 'order':
                return $this->createMockOrders($count);

            case 'payment':
                return $this->createMockPayments($count);

            default:
                return [];
        }
    }

    /**
     * Create mock users.
     */
    protected function createMockUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; ++$i) {
            $users[] = [
                'name' => $this->faker->name,
                'email' => $this->faker->unique()->safeEmail,
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $users;
    }

    /**
     * Create mock products.
     */
    protected function createMockProducts(int $count): array
    {
        $products = [];
        for ($i = 0; $i < $count; ++$i) {
            $products[] = [
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->paragraph,
                'price' => $this->faker->randomFloat(2, 10, 1000),
                'category_id' => $this->faker->numberBetween(1, 10),
                'brand' => $this->faker->company,
                'stock_quantity' => $this->faker->numberBetween(0, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $products;
    }

    /**
     * Create mock orders.
     */
    protected function createMockOrders(int $count): array
    {
        $orders = [];
        for ($i = 0; $i < $count; ++$i) {
            $orders[] = [
                'user_id' => $this->faker->numberBetween(1, 100),
                'total_amount' => $this->faker->randomFloat(2, 20, 2000),
                'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
                'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'refunded']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $orders;
    }

    /**
     * Create mock payments.
     */
    protected function createMockPayments(int $count): array
    {
        $payments = [];
        for ($i = 0; $i < $count; ++$i) {
            $payments[] = [
                'order_id' => $this->faker->numberBetween(1, 100),
                'amount' => $this->faker->randomFloat(2, 20, 2000),
                'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer']),
                'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
                'transaction_id' => $this->faker->uuid,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $payments;
    }

    /**
     * Skip test if condition is not met.
     */
    protected function skipTestIf(bool $condition, string $reason = 'Test condition not met'): void
    {
        if ($condition) {
            self::markTestSkipped($reason);
        }
    }

    /**
     * Skip test if environment is not suitable.
     */
    protected function skipTestIfNotEnvironment(string $environment): void
    {
        if (app()->environment() !== $environment) {
            self::markTestSkipped("Test requires {$environment} environment");
        }
    }

    /**
     * Assert database has records matching criteria.
     */
    protected function assertDatabaseHasRecords(string $table, array $criteria, int $expectedCount): void
    {
        $actualCount = DB::table($table)->where($criteria)->count();

        self::assertSame(
            $expectedCount,
            $actualCount,
            "Expected {$expectedCount} records in {$table} matching criteria, found {$actualCount}"
        );
    }

    /**
     * Assert cache has key with expected value.
     *
     * @param mixed|null $expectedValue
     */
    protected function assertCacheHas(string $key, $expectedValue = null): void
    {
        self::assertTrue(Cache::has($key), "Cache does not have key: {$key}");

        if (null !== $expectedValue) {
            self::assertSame($expectedValue, Cache::get($key), "Cache value mismatch for key: {$key}");
        }
    }

    /**
     * Assert cache does not have key.
     */
    protected function assertCacheDoesNotHave(string $key): void
    {
        self::assertFalse(Cache::has($key), "Cache should not have key: {$key}");
    }

    /**
     * Assert queue has job.
     */
    protected function assertQueueHasJob(string $jobClass): void
    {
        Queue::assertPushed($jobClass);
    }

    /**
     * Assert event was dispatched.
     */
    protected function assertEventDispatched(string $eventClass): void
    {
        Event::assertDispatched($eventClass);
    }

    /**
     * Assert notification was sent.
     */
    protected function assertNotificationSent(string $notificationClass): void
    {
        Notification::assertSent($notificationClass);
    }

    /**
     * Create test with performance profiling.
     */
    protected function runTestWithProfiling(callable $test): array
    {
        return $this->profileCurrentTest($test);
    }

    /**
     * Benchmark test execution.
     */
    protected function benchmarkTest(callable $test, int $iterations = 10): array
    {
        $times = [];
        $memoryUsages = [];

        for ($i = 0; $i < $iterations; ++$i) {
            $performance = $this->measurePerformance($test);
            $times[] = $performance['execution_time'];
            $memoryUsages[] = $performance['memory_usage'];
        }

        return [
            'iterations' => $iterations,
            'average_time' => array_sum($times) / \count($times),
            'min_time' => min($times),
            'max_time' => max($times),
            'average_memory' => array_sum($memoryUsages) / \count($memoryUsages),
            'min_memory' => min($memoryUsages),
            'max_memory' => max($memoryUsages),
        ];
    }
}
