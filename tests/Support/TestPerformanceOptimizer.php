<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/**
 * Test Performance Optimizer.
 *
 * Provides utilities and configurations to optimize test performance
 * and reduce execution time across the test suite.
 */
class TestPerformanceOptimizer
{
    /**
     * Optimize database operations for testing.
     */
    public static function optimizeDatabase(): void
    {
        // Use in-memory SQLite for faster tests
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        // Disable foreign key constraints for faster inserts
        DB::statement('PRAGMA foreign_keys=OFF');

        // Optimize SQLite for speed
        DB::statement('PRAGMA synchronous=OFF');
        DB::statement('PRAGMA journal_mode=MEMORY');
        DB::statement('PRAGMA temp_store=MEMORY');
        DB::statement('PRAGMA cache_size=10000');
    }

    /**
     * Optimize cache operations for testing.
     */
    public static function optimizeCache(): void
    {
        // Use array driver for fastest cache operations
        config(['cache.default' => 'array']);

        // Clear any existing cache
        Cache::flush();
    }

    /**
     * Optimize queue operations for testing.
     */
    public static function optimizeQueue(): void
    {
        // Use sync driver to avoid queue delays
        config(['queue.default' => 'sync']);

        // Fake queue for tests that don't need actual processing
        Queue::fake();
    }

    /**
     * Optimize session and cookie handling.
     */
    public static function optimizeSession(): void
    {
        // Use array driver for sessions
        config(['session.driver' => 'array']);

        // Disable cookie encryption for tests
        config(['session.encrypt' => false]);
    }

    /**
     * Optimize mail and notifications for testing.
     */
    public static function optimizeNotifications(): void
    {
        // Use array driver for mail
        config(['mail.default' => 'array']);

        // Fake notifications
        Notification::fake();
    }

    /**
     * Optimize event handling for testing.
     */
    public static function optimizeEvents(): void
    {
        // Fake events that don't need to be tested
        Event::fake([
            'Illuminate\Auth\Events\Login',
            'Illuminate\Auth\Events\Logout',
            'Illuminate\Database\Events\QueryExecuted',
        ]);
    }

    /**
     * Apply all optimizations.
     */
    public static function applyAllOptimizations(): void
    {
        static::optimizeDatabase();
        static::optimizeCache();
        static::optimizeQueue();
        static::optimizeSession();
        static::optimizeNotifications();
        static::optimizeEvents();
    }

    /**
     * Create optimized test data in batches.
     */
    public static function createTestDataInBatches(string $model, array $data, int $batchSize = 100): void
    {
        $chunks = array_chunk($data, $batchSize);

        foreach ($chunks as $chunk) {
            $model::insert($chunk);
        }
    }

    /**
     * Measure test execution time.
     */
    public static function measureExecutionTime(callable $callback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $result = $callback();

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        return [
            'result' => $result,
            'execution_time' => $endTime - $startTime,
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Clean up test data efficiently.
     */
    public static function cleanupTestData(array $tables = []): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('PRAGMA foreign_keys=ON');
    }

    /**
     * Optimize test environment for specific test types.
     */
    public static function optimizeForTestType(string $testType): void
    {
        switch ($testType) {
            case 'unit':
                // Minimal setup for unit tests
                static::optimizeCache();
                static::optimizeQueue();

                break;

            case 'feature':
                // Full optimization for feature tests
                static::applyAllOptimizations();

                break;

            case 'integration':
                // Database and cache optimization for integration tests
                static::optimizeDatabase();
                static::optimizeCache();
                static::optimizeQueue();

                break;

            case 'performance':
                // Minimal interference for performance tests
                static::optimizeSession();
                static::optimizeNotifications();

                break;

            default:
                static::applyAllOptimizations();
        }
    }

    /**
     * Profile test performance.
     */
    public static function profileTest(string $testName, callable $test): array
    {
        $profile = static::measureExecutionTime($test);

        // Log performance metrics
        $metrics = [
            'test_name' => $testName,
            'execution_time' => round($profile['execution_time'], 4),
            'memory_usage' => static::formatBytes($profile['memory_usage']),
            'peak_memory' => static::formatBytes($profile['peak_memory']),
            'timestamp' => now()->toISOString(),
        ];

        // Store metrics for analysis
        static::storePerformanceMetrics($metrics);

        return $metrics;
    }

    /**
     * Generate performance report.
     */
    public static function generatePerformanceReport(): array
    {
        $logFile = storage_path('logs/test-performance.log');

        if (! file_exists($logFile)) {
            return ['error' => 'No performance data available'];
        }

        $lines = file($logFile, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        $metrics = array_map('json_decode', $lines);

        // Calculate statistics
        $executionTimes = array_column($metrics, 'execution_time');
        $memoryUsages = array_column($metrics, 'memory_usage');

        return [
            'total_tests' => \count($metrics),
            'average_execution_time' => round(array_sum($executionTimes) / \count($executionTimes), 4),
            'max_execution_time' => max($executionTimes),
            'min_execution_time' => min($executionTimes),
            'slowest_tests' => static::getSlowTestsFromMetrics($metrics),
            'memory_intensive_tests' => static::getMemoryIntensiveTestsFromMetrics($metrics),
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, \count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Store performance metrics for analysis.
     */
    private static function storePerformanceMetrics(array $metrics): void
    {
        $logFile = storage_path('logs/test-performance.log');

        if (! file_exists(\dirname($logFile))) {
            mkdir(\dirname($logFile), 0755, true);
        }

        file_put_contents(
            $logFile,
            json_encode($metrics).\PHP_EOL,
            \FILE_APPEND | \LOCK_EX
        );
    }

    /**
     * Get slowest tests from metrics.
     */
    private static function getSlowTestsFromMetrics(array $metrics): array
    {
        usort($metrics, static function ($a, $b) {
            return $b->execution_time <=> $a->execution_time;
        });

        return \array_slice($metrics, 0, 5);
    }

    /**
     * Get memory intensive tests from metrics.
     */
    private static function getMemoryIntensiveTestsFromMetrics(array $metrics): array
    {
        usort($metrics, static function ($a, $b) {
            return strcmp($b->peak_memory, $a->peak_memory);
        });

        return \array_slice($metrics, 0, 5);
    }
}
