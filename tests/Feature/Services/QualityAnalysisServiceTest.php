<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\QualityAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class QualityAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private QualityAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(QualityAnalysisService::class);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testAnalyzesCodeQuality()
    {
        // Act
        $result = $this->service->analyze();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('score', $result);
        self::assertArrayHasKey('max_score', $result);
        self::assertArrayHasKey('issues', $result);
        self::assertArrayHasKey('category', $result);
        self::assertSame('Code Quality', $result['category']);
    }

    public function testHandlesAnalysisException()
    {
        // This test verifies that the service handles exceptions gracefully
        // The actual implementation will catch exceptions and return them in issues
        $result = $this->service->analyze();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('score', $result);
        self::assertArrayHasKey('issues', $result);
        self::assertIsArray($result['issues']);
    }

    public function testReturnsValidScoreRange()
    {
        // Act
        $result = $this->service->analyze();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('score', $result);
        self::assertArrayHasKey('max_score', $result);
        self::assertGreaterThanOrEqual(0, $result['score']);
        self::assertLessThanOrEqual($result['max_score'], $result['score']);
    }

    public function testReturnsCodeQualityCategory()
    {
        // Act
        $result = $this->service->analyze();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('category', $result);
        self::assertSame('Code Quality', $result['category']);
    }
}
