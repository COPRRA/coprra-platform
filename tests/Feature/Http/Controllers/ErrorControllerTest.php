<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class ErrorControllerTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[Test]
    public function itCanDisplayErrorDashboard(): void
    {
        $response = $this->get('/admin/errors');

        $response->assertStatus(200);
        $response->assertViewIs('admin.errors.dashboard');
        $response->assertViewHas(['errors', 'statistics']);
    }

    #[Test]
    public function itCanDisplayErrorDashboardAsJson(): void
    {
        $response = $this->getJson('/admin/errors');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'errors' => [],
            'statistics' => [
                'total_errors',
                'recent_errors',
                'error_rate',
            ],
        ]);
    }

    #[Test]
    public function itCanShowErrorDetails(): void
    {
        $response = $this->get('/admin/errors/1');

        $response->assertStatus(200);
        $response->assertViewIs('admin.errors.show');
        $response->assertViewHas(['error', 'context']);
    }

    #[Test]
    public function itReturns404ForNonexistentError(): void
    {
        $response = $this->get('/admin/errors/999999');

        $response->assertStatus(404);
    }

    #[Test]
    public function itCanGetRecentErrors(): void
    {
        $response = $this->getJson('/admin/errors/recent');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [],
            'meta' => [
                'total',
                'per_page',
                'current_page',
            ],
        ]);
    }

    #[Test]
    public function itCanGetErrorStatistics(): void
    {
        $response = $this->getJson('/admin/errors/statistics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_errors',
            'errors_today',
            'errors_this_week',
            'error_rate',
            'top_errors',
        ]);
    }

    #[Test]
    public function itCanGetSystemHealth(): void
    {
        $response = $this->getJson('/admin/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'checks' => [
                'database',
                'cache',
                'storage',
                'memory',
                'disk_space',
            ],
            'timestamp',
        ]);
    }

    #[Test]
    public function itChecksDatabaseHealth(): void
    {
        $response = $this->getJson('/admin/health/database');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'connection_time',
            'active_connections',
        ]);
    }

    #[Test]
    public function itChecksCacheHealth(): void
    {
        Cache::put('health_check', 'test', 60);

        $response = $this->getJson('/admin/health/cache');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'response_time',
            'hit_rate',
        ]);
    }

    #[Test]
    public function itChecksStorageHealth(): void
    {
        $response = $this->getJson('/admin/health/storage');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'available_space',
            'total_space',
            'usage_percentage',
        ]);
    }

    #[Test]
    public function itChecksMemoryHealth(): void
    {
        $response = $this->getJson('/admin/health/memory');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'memory_usage',
            'memory_limit',
            'peak_usage',
        ]);
    }

    #[Test]
    public function itChecksDiskSpaceHealth(): void
    {
        $response = $this->getJson('/admin/health/disk');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'free_space',
            'total_space',
            'usage_percentage',
        ]);
    }

    #[Test]
    public function itHandlesDatabaseConnectionFailure(): void
    {
        // Simulate database connection failure by using invalid connection
        config(['database.connections.testing.database' => 'invalid_database']);

        $response = $this->getJson('/admin/health/database');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Database connection failed',
        ]);
    }

    #[Test]
    public function itHandlesCacheFailure(): void
    {
        // Mock cache failure
        Cache::shouldReceive('put')->andThrow(new \Exception('Cache connection failed'));

        $response = $this->getJson('/admin/health/cache');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Cache system unavailable',
        ]);
    }

    #[Test]
    public function itHandlesStorageFailure(): void
    {
        // Mock storage failure
        Storage::shouldReceive('disk')->andThrow(new \Exception('Storage unavailable'));

        $response = $this->getJson('/admin/health/storage');

        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Storage system unavailable',
        ]);
    }
}
