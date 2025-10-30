<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Services\AI\ModelVersionTracker;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for ModelVersionTracker functionality.
 *
 * @internal
 *
 * @coversNothing
 */
final class ModelVersionTrackerTest extends TestCase
{
    private ModelVersionTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker = new ModelVersionTracker();
    }

    public function testGetModelInfo(): void
    {
        $modelInfo = $this->tracker->getModelInfo('gpt-4');

        self::assertIsArray($modelInfo);
        self::assertArrayHasKey('name', $modelInfo);
        self::assertArrayHasKey('version', $modelInfo);
        self::assertArrayHasKey('capabilities', $modelInfo);
        self::assertArrayHasKey('cost_per_token', $modelInfo);
        self::assertArrayHasKey('max_tokens', $modelInfo);
        self::assertArrayHasKey('release_date', $modelInfo);

        self::assertSame('gpt-4', $modelInfo['name']);
        self::assertSame('2024.1', $modelInfo['version']);
    }

    public function testGetModelInfoForUnknownModel(): void
    {
        $modelInfo = $this->tracker->getModelInfo('unknown-model');

        self::assertNull($modelInfo);
    }

    public function testTrackUsage(): void
    {
        // Track successful usage
        $this->tracker->trackUsage('gpt-4', 'text_analysis', true, 150.5);

        $metrics = $this->tracker->getModelMetrics('gpt-4');

        self::assertIsArray($metrics);
        self::assertArrayHasKey('total_requests', $metrics);
        self::assertArrayHasKey('successful_requests', $metrics);
        self::assertArrayHasKey('failed_requests', $metrics);
        self::assertArrayHasKey('average_response_time', $metrics);
        self::assertArrayHasKey('success_rate', $metrics);
        self::assertArrayHasKey('total_cost', $metrics);

        self::assertSame(1, $metrics['total_requests']);
        self::assertSame(1, $metrics['successful_requests']);
        self::assertSame(0, $metrics['failed_requests']);
        self::assertSame(150.5, $metrics['average_response_time']);
        self::assertSame(100.0, $metrics['success_rate']);
    }

    public function testTrackFailedUsage(): void
    {
        // Track failed usage
        $this->tracker->trackUsage('gpt-4', 'text_analysis', false, 200.0);

        $metrics = $this->tracker->getModelMetrics('gpt-4');

        self::assertSame(1, $metrics['total_requests']);
        self::assertSame(0, $metrics['successful_requests']);
        self::assertSame(1, $metrics['failed_requests']);
        self::assertSame(200.0, $metrics['average_response_time']);
        self::assertSame(0.0, $metrics['success_rate']);
    }

    public function testTrackMultipleUsages(): void
    {
        // Track multiple usages
        $this->tracker->trackUsage('gpt-4', 'text_analysis', true, 100.0);
        $this->tracker->trackUsage('gpt-4', 'text_analysis', true, 200.0);
        $this->tracker->trackUsage('gpt-4', 'text_analysis', false, 300.0);

        $metrics = $this->tracker->getModelMetrics('gpt-4');

        self::assertSame(3, $metrics['total_requests']);
        self::assertSame(2, $metrics['successful_requests']);
        self::assertSame(1, $metrics['failed_requests']);
        self::assertSame(200.0, $metrics['average_response_time']); // (100+200+300)/3
        self::assertSame(66.67, round($metrics['success_rate'], 2)); // 2/3 * 100
    }

    public function testGetRecommendedModel(): void
    {
        $recommended = $this->tracker->getRecommendedModel('text_analysis');

        self::assertIsString($recommended);
        self::assertContains($recommended, ['gpt-4', 'gpt-3.5-turbo', 'claude-3']);
    }

    public function testGetRecommendedModelForImageAnalysis(): void
    {
        $recommended = $this->tracker->getRecommendedModel('image_analysis');

        self::assertIsString($recommended);
        self::assertContains($recommended, ['gpt-4-vision', 'claude-3-vision']);
    }

    public function testGetRecommendedModelForUnknownTask(): void
    {
        $recommended = $this->tracker->getRecommendedModel('unknown_task');

        self::assertSame('gpt-4', $recommended); // Should default to gpt-4
    }

    public function testCompareModels(): void
    {
        // Track usage for multiple models
        $this->tracker->trackUsage('gpt-4', 'text_analysis', true, 150.0);
        $this->tracker->trackUsage('gpt-3.5-turbo', 'text_analysis', true, 100.0);

        $comparison = $this->tracker->compareModels(['gpt-4', 'gpt-3.5-turbo']);

        self::assertIsArray($comparison);
        self::assertArrayHasKey('gpt-4', $comparison);
        self::assertArrayHasKey('gpt-3.5-turbo', $comparison);

        foreach ($comparison as $model => $metrics) {
            self::assertArrayHasKey('performance_score', $metrics);
            self::assertArrayHasKey('cost_efficiency', $metrics);
            self::assertArrayHasKey('reliability', $metrics);
            self::assertArrayHasKey('speed', $metrics);
        }
    }

    public function testGetOutdatedModels(): void
    {
        $outdatedModels = $this->tracker->getOutdatedModels();

        self::assertIsArray($outdatedModels);

        foreach ($outdatedModels as $model) {
            self::assertArrayHasKey('name', $model);
            self::assertArrayHasKey('current_version', $model);
            self::assertArrayHasKey('latest_version', $model);
            self::assertArrayHasKey('days_behind', $model);
        }
    }

    public function testGetAllModels(): void
    {
        $allModels = $this->tracker->getAllModels();

        self::assertIsArray($allModels);
        self::assertNotEmpty($allModels);

        foreach ($allModels as $modelName => $modelInfo) {
            self::assertIsString($modelName);
            self::assertIsArray($modelInfo);
            self::assertArrayHasKey('version', $modelInfo);
            self::assertArrayHasKey('capabilities', $modelInfo);
        }
    }

    public function testGetModelMetricsForUnknownModel(): void
    {
        $metrics = $this->tracker->getModelMetrics('unknown-model');

        self::assertIsArray($metrics);
        self::assertSame(0, $metrics['total_requests']);
        self::assertSame(0, $metrics['successful_requests']);
        self::assertSame(0, $metrics['failed_requests']);
        self::assertSame(0, $metrics['average_response_time']);
        self::assertSame(0, $metrics['success_rate']);
        self::assertSame(0, $metrics['total_cost']);
    }

    public function testCostCalculation(): void
    {
        // Track usage with known token count
        $this->tracker->trackUsage('gpt-4', 'text_analysis', true, 150.0, 1000);

        $metrics = $this->tracker->getModelMetrics('gpt-4');

        self::assertGreaterThan(0, $metrics['total_cost']);
        self::assertIsFloat($metrics['total_cost']);
    }

    public function testPerformanceScoring(): void
    {
        // Track multiple usages to test performance scoring
        for ($i = 0; $i < 10; ++$i) {
            $success = $i < 8; // 80% success rate
            $responseTime = 100 + ($i * 10); // Varying response times
            $this->tracker->trackUsage('gpt-4', 'text_analysis', $success, $responseTime);
        }

        $comparison = $this->tracker->compareModels(['gpt-4']);
        $gpt4Metrics = $comparison['gpt-4'];

        self::assertIsFloat($gpt4Metrics['performance_score']);
        self::assertGreaterThanOrEqual(0, $gpt4Metrics['performance_score']);
        self::assertLessThanOrEqual(100, $gpt4Metrics['performance_score']);
    }

    public function testResetMetrics(): void
    {
        // Track some usage
        $this->tracker->trackUsage('gpt-4', 'text_analysis', true, 150.0);

        // Verify metrics exist
        $metrics = $this->tracker->getModelMetrics('gpt-4');
        self::assertSame(1, $metrics['total_requests']);

        // Reset metrics
        $this->tracker->resetMetrics();

        // Verify metrics are reset
        $metricsAfterReset = $this->tracker->getModelMetrics('gpt-4');
        self::assertSame(0, $metricsAfterReset['total_requests']);
    }

    public function testGetTopPerformingModels(): void
    {
        // Track usage for multiple models
        $this->tracker->trackUsage('gpt-4', 'text_analysis', true, 100.0);
        $this->tracker->trackUsage('gpt-3.5-turbo', 'text_analysis', true, 80.0);
        $this->tracker->trackUsage('claude-3', 'text_analysis', false, 200.0);

        $topModels = $this->tracker->getTopPerformingModels(2);

        self::assertIsArray($topModels);
        self::assertLessThanOrEqual(2, \count($topModels));

        foreach ($topModels as $model) {
            self::assertArrayHasKey('name', $model);
            self::assertArrayHasKey('performance_score', $model);
        }
    }
}
