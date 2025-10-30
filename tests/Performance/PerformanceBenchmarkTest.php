<?php

declare(strict_types=1);

namespace Tests\Performance;

use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PerformanceBenchmarkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testPerformanceBenchmark(): void
    {
        $startTime = microtime(true);

        // Simulate some work
        $data = [];
        for ($i = 0; $i < 1000; ++$i) {
            $data[] = $i * $i;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        self::assertLessThan(1.0, $executionTime); // Should complete in less than 1 second
        self::assertCount(1000, $data);
    }

    public function testBenchmarkComparison(): void
    {
        $method1Start = microtime(true);
        $result1 = array_map('strtoupper', range('a', 'z'));
        $method1Time = microtime(true) - $method1Start;

        $method2Start = microtime(true);
        $result2 = [];
        foreach (range('a', 'z') as $letter) {
            $result2[] = strtoupper($letter);
        }
        $method2Time = microtime(true) - $method2Start;

        self::assertCount(26, $result1);
        self::assertCount(26, $result2);
        self::assertSame($result1, $result2);
    }

    public function testBenchmarkTrends(): void
    {
        $iterations = 100;
        $times = [];

        for ($i = 0; $i < $iterations; ++$i) {
            $start = microtime(true);

            // Simulate varying workload
            $workload = $i % 10 + 1;
            $data = array_fill(0, $workload * 100, 'test');
            $result = array_sum(array_map('strlen', $data));
            self::assertGreaterThan(0, $result);

            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum((array) $times) / \count((array) $times);
        $maxTime = max($times);

        self::assertLessThan(0.1, $avgTime); // Average should be less than 0.1 seconds
        self::assertLessThan(0.5, $maxTime); // Max should be less than 0.5 seconds
    }
}
