<?php

declare(strict_types=1);

namespace Tests\Performance;

use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class MemoryUsageTest extends TestCase
{
    private int $initialMemory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initialMemory = memory_get_usage(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testMemoryUsageIsReasonable(): void
    {
        $currentMemory = memory_get_usage(true);
        $memoryUsageMB = ($currentMemory - $this->initialMemory) / 1024 / 1024;

        // Memory usage should be reasonable (less than 50MB for basic operations)
        self::assertLessThan(50, $memoryUsageMB, 'Memory usage should be less than 50MB');

        // Peak memory should be reasonable
        $peakMemoryMB = memory_get_peak_usage(true) / 1024 / 1024;
        self::assertLessThan(128, $peakMemoryMB, 'Peak memory usage should be less than 128MB');
    }

    public function testMemoryLeaksArePrevented(): void
    {
        $initialMemory = memory_get_usage(true);

        // Simulate operations that could cause memory leaks
        for ($i = 0; $i < 1000; ++$i) {
            $data = str_repeat('x', 1000);
            unset($data);
        }

        // Force garbage collection
        gc_collect_cycles();

        $finalMemory = memory_get_usage(true);
        $memoryDifference = $finalMemory - $initialMemory;

        // Memory difference should be minimal (less than 1MB)
        self::assertLessThan(1024 * 1024, $memoryDifference, 'Memory leak detected: difference is '.($memoryDifference / 1024).' KB');
    }

    public function testMemoryCleanupWorks(): void
    {
        $beforeMemory = memory_get_usage(true);

        // Create large objects
        $largeArray = [];
        for ($i = 0; $i < 10000; ++$i) {
            $largeArray[] = str_repeat('test', 100);
        }

        $afterCreationMemory = memory_get_usage(true);

        // Verify memory increased
        self::assertGreaterThan($beforeMemory, $afterCreationMemory, 'Memory should increase after creating large objects');

        // Clean up
        unset($largeArray);
        gc_collect_cycles();

        $afterCleanupMemory = memory_get_usage(true);

        // Memory should be significantly reduced after cleanup
        $memoryReduction = $afterCreationMemory - $afterCleanupMemory;
        self::assertGreaterThan(1024 * 100, $memoryReduction, 'Memory cleanup should free at least 100KB');
    }

    public function testMemoryLimitIsNotExceeded(): void
    {
        $memoryLimit = \ini_get('memory_limit');
        $currentUsage = memory_get_usage(true);

        if ('-1' !== $memoryLimit) {
            $limitBytes = $this->parseMemoryLimit($memoryLimit);
            $usagePercentage = ($currentUsage / $limitBytes) * 100;

            // Current usage should be less than 80% of memory limit
            self::assertLessThan(80, $usagePercentage, 'Memory usage is too close to the limit');
        }

        self::assertIsInt($currentUsage);
        self::assertGreaterThan(0, $currentUsage);
    }

    private function parseMemoryLimit(string $memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
