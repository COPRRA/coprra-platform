<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AnalyticsEvent;
use App\Services\AnalyticsService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Edge case tests for AnalyticsService covering critical failure scenarios.
 *
 * @internal
 *
 * @coversNothing
 */
final class AnalyticsServiceEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    public function testTrackWithDatabaseConnectionFailure(): void
    {
        Log::shouldReceive('warning')->once()->with(
            'Failed to track analytics event',
            \Mockery::on(static function ($context) {
                return isset($context['error']) && str_contains($context['error'], 'Database connection');
            })
        );

        // Mock AnalyticsEvent to throw database exception
        $this->mock(AnalyticsEvent::class, static function ($mock) {
            $mock->shouldReceive('create')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'INSERT INTO analytics_events',
                    [],
                    new \Exception('Database connection failed')
                ))
            ;
        });

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithExtremelyLargeMetadata(): void
    {
        // Create metadata that exceeds typical JSON column limits
        $largeMetadata = [];
        for ($i = 0; $i < 10000; ++$i) {
            $largeMetadata["key_{$i}"] = str_repeat('x', 1000);
        }

        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            $largeMetadata
        );

        self::assertNull($result);
    }

    public function testTrackWithInvalidMetadataTypes(): void
    {
        // Test with metadata containing non-serializable objects
        $invalidMetadata = [
            'resource' => fopen('php://memory', 'r'),
            'closure' => static function () { return 'test'; },
            'object' => new \stdClass(),
        ];

        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            $invalidMetadata
        );

        self::assertNull($result);
    }

    public function testTrackWithNullEventType(): void
    {
        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            null, // Invalid null event type
            'test_event',
            1,
            1,
            1,
            1,
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithEmptyEventName(): void
    {
        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            'test_type',
            '', // Empty event name
            1,
            1,
            1,
            1,
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithNegativeIds(): void
    {
        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            -1, // Negative user ID
            -1, // Negative product ID
            -1, // Negative category ID
            -1, // Negative store ID
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithCircularReferenceInMetadata(): void
    {
        $metadata = ['key' => 'value'];
        $metadata['circular'] = &$metadata; // Create circular reference

        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            $metadata
        );

        self::assertNull($result);
    }

    public function testCleanOldDataWithDatabaseLockTimeout(): void
    {
        // Create some test data
        AnalyticsEvent::factory()->count(5)->create([
            'created_at' => now()->subDays(400),
        ]);

        // Mock DB to throw lock timeout exception
        DB::shouldReceive('table')
            ->with('analytics_events')
            ->andReturnSelf()
        ;

        DB::shouldReceive('where')
            ->with('created_at', '<', \Mockery::any())
            ->andReturnSelf()
        ;

        DB::shouldReceive('delete')
            ->andThrow(new QueryException(
                'mysql',
                'DELETE FROM analytics_events',
                [],
                new \Exception('Lock wait timeout exceeded')
            ))
        ;

        Log::shouldReceive('info')->never();

        $result = $this->analyticsService->cleanOldData(365);

        self::assertSame(0, $result);
    }

    public function testCleanOldDataWithInvalidDaysParameter(): void
    {
        $result = $this->analyticsService->cleanOldData(-1); // Negative days

        self::assertSame(0, $result);
    }

    public function testCleanOldDataWithZeroDays(): void
    {
        // Create recent data
        AnalyticsEvent::factory()->count(3)->create([
            'created_at' => now()->subHours(1),
        ]);

        Log::shouldReceive('info')->once();

        $result = $this->analyticsService->cleanOldData(0);

        self::assertSame(3, $result);
        $this->assertDatabaseCount('analytics_events', 0);
    }

    public function testTrackPriceComparisonWithInvalidProductId(): void
    {
        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->trackPriceComparison(
            0, // Invalid product ID
            1,
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithMemoryExhaustion(): void
    {
        // Simulate memory exhaustion by creating extremely large metadata
        $memoryLimit = \ini_get('memory_limit');
        ini_set('memory_limit', '1M'); // Set very low memory limit

        try {
            $largeArray = array_fill(0, 1000000, 'large_string_'.str_repeat('x', 1000));

            Log::shouldReceive('warning')->once();

            $result = $this->analyticsService->track(
                'test_type',
                'test_event',
                1,
                1,
                1,
                1,
                $largeArray
            );

            self::assertNull($result);
        } finally {
            ini_set('memory_limit', $memoryLimit); // Restore original limit
        }
    }

    public function testTrackWithDatabaseTableMissing(): void
    {
        // Drop the analytics_events table to simulate missing table
        DB::statement('DROP TABLE IF EXISTS analytics_events');

        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithReadOnlyDatabase(): void
    {
        // Mock to simulate read-only database
        $this->mock(AnalyticsEvent::class, static function ($mock) {
            $mock->shouldReceive('create')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'INSERT INTO analytics_events',
                    [],
                    new \Exception('The MySQL server is running with the --read-only option')
                ))
            ;
        });

        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithDiskSpaceExhausted(): void
    {
        $this->mock(AnalyticsEvent::class, static function ($mock) {
            $mock->shouldReceive('create')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'INSERT INTO analytics_events',
                    [],
                    new \Exception('No space left on device')
                ))
            ;
        });

        Log::shouldReceive('warning')->once();

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithConfigDisabledDuringExecution(): void
    {
        // Start with tracking enabled
        Config::set('coprra.analytics.track_user_behavior', true);

        // Disable tracking during execution (simulating config change)
        Config::set('coprra.analytics.track_user_behavior', false);

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            ['key' => 'value']
        );

        self::assertNull($result);
    }

    public function testTrackWithUnicodeMetadata(): void
    {
        $unicodeMetadata = [
            'emoji' => '🚀🎉💻',
            'chinese' => '你好世界',
            'arabic' => 'مرحبا بالعالم',
            'special_chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'null_bytes' => "test\0null\0bytes",
        ];

        $result = $this->analyticsService->track(
            'test_type',
            'test_event',
            1,
            1,
            1,
            1,
            $unicodeMetadata
        );

        self::assertInstanceOf(AnalyticsEvent::class, $result);
        self::assertSame($unicodeMetadata, $result->metadata);
    }

    public function testCleanOldDataWithConcurrentDeletion(): void
    {
        // Create test data
        AnalyticsEvent::factory()->count(10)->create([
            'created_at' => now()->subDays(400),
        ]);

        // Simulate concurrent deletion by having another process delete some records
        AnalyticsEvent::where('created_at', '<', now()->subDays(365))->limit(5)->delete();

        Log::shouldReceive('info')->once();

        $result = $this->analyticsService->cleanOldData(365);

        self::assertSame(5, $result); // Should only count what it actually deleted
    }
}
