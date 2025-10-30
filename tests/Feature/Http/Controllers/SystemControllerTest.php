<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\SystemController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class SystemControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]
    public function itCanGetSystemInformation(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'laravel_version',
                    'php_version',
                    'os',
                    'server_software',
                    'memory_limit',
                    'max_execution_time',
                    'disk_free_space',
                    'disk_total_space',
                    'uptime',
                    'cpu_count',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'System information retrieved successfully',
            ])
        ;

        self::assertArrayHasKey('load_average', $response->json('data'));
    }

    #[Test]
    public function itCanRunDatabaseMigrations(): void
    {
        $response = $this->postJson('/api/system/migrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'output',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Migrations ran successfully',
            ])
        ;
    }

    #[Test]
    public function itCanClearApplicationCache(): void
    {
        $response = $this->postJson('/api/system/cache/clear');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ])
        ;
    }

    #[Test]
    public function itCanOptimizeApplication(): void
    {
        $response = $this->postJson('/api/system/optimize');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Application optimized successfully',
            ])
        ;
    }

    #[Test]
    public function itCanRunComposerUpdate(): void
    {
        $response = $this->postJson('/api/system/composer-update');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'output',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Composer update ran successfully',
            ])
        ;
    }

    #[Test]
    public function itCanGetPerformanceMetrics(): void
    {
        $response = $this->getJson('/api/system/performance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'memory_usage',
                    'memory_peak',
                    'memory_limit',
                    'execution_time',
                    'database_connections',
                    'cache_hits',
                    'response_time',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Performance metrics retrieved successfully',
            ])
        ;
    }

    #[Test]
    public function itHandlesMigrationErrors(): void
    {
        // Mock Artisan::call failure
        Artisan::shouldReceive('call')
            ->with('migrate', ['--force' => true])
            ->andThrow(new \Exception('Migration failed'))
        ;

        $response = $this->postJson('/api/system/migrations');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to run migrations',
            ])
        ;
    }

    #[Test]
    public function itHandlesCacheClearErrors(): void
    {
        // Mock Artisan::call failure
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->andThrow(new \Exception('Cache clear failed'))
        ;

        $response = $this->postJson('/api/system/cache/clear');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to clear cache',
            ])
        ;
    }

    #[Test]
    public function itHandlesOptimizationErrors(): void
    {
        // Mock Artisan::call failure
        Artisan::shouldReceive('call')
            ->with('optimize')
            ->andThrow(new \Exception('Optimization failed'))
        ;

        $response = $this->postJson('/api/system/optimize');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to optimize application',
            ])
        ;
    }

    #[Test]
    public function itHandlesComposerUpdateErrors(): void
    {
        // Mock Process failure
        $this->mock(Process::class, static function ($mock) {
            $mock->shouldReceive('setTimeout')->andReturnSelf();
            $mock->shouldReceive('run')->andReturnSelf();
            $mock->shouldReceive('isSuccessful')->andReturn(false);
            $mock->shouldReceive('getErrorOutput')->andReturn('Composer update failed');
        });

        $response = $this->postJson('/api/system/composer-update');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to run composer update',
            ])
        ;
    }

    #[Test]
    public function itHandlesSystemInfoErrors(): void
    {
        // Mock system info failure
        $this->mock(SystemController::class, static function ($mock) {
            $mock->shouldReceive('getSystemInfo')
                ->andThrow(new \Exception('System info failed'))
            ;
        });

        $response = $this->getJson('/api/system/info');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to get system information',
            ])
        ;
    }

    #[Test]
    public function itHandlesPerformanceMetricsErrors(): void
    {
        // Mock performance metrics failure
        $this->mock(SystemController::class, static function ($mock) {
            $mock->shouldReceive('getPerformanceMetrics')
                ->andThrow(new \Exception('Performance metrics failed'))
            ;
        });

        $response = $this->getJson('/api/system/performance');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to get performance metrics',
            ])
        ;
    }

    #[Test]
    public function itCallsMultipleArtisanCommandsForCacheClear(): void
    {
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once()
        ;

        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once()
        ;

        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->once()
        ;

        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->once()
        ;

        $response = $this->postJson('/api/system/cache/clear');

        $response->assertStatus(200);
    }

    #[Test]
    public function itReturnsValidSystemInformation(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify required fields exist
        self::assertArrayHasKey('laravel_version', $data);
        self::assertArrayHasKey('php_version', $data);
        self::assertArrayHasKey('os', $data);
        self::assertArrayHasKey('memory_limit', $data);
        self::assertArrayHasKey('disk_free_space', $data);
        self::assertArrayHasKey('disk_total_space', $data);

        // Verify data types
        self::assertIsString($data['laravel_version']);
        self::assertIsString($data['php_version']);
        self::assertIsString($data['os']);
        self::assertIsString($data['memory_limit']);
        self::assertIsString($data['disk_free_space']);
        self::assertIsString($data['disk_total_space']);
    }

    #[Test]
    public function itReturnsValidPerformanceMetrics(): void
    {
        $response = $this->getJson('/api/system/performance');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify required fields exist
        self::assertArrayHasKey('memory_usage', $data);
        self::assertArrayHasKey('memory_peak', $data);
        self::assertArrayHasKey('memory_limit', $data);
        self::assertArrayHasKey('execution_time', $data);
        self::assertArrayHasKey('database_connections', $data);
        self::assertArrayHasKey('cache_hits', $data);
        self::assertArrayHasKey('response_time', $data);

        // Verify data types
        self::assertIsInt($data['memory_usage']);
        self::assertIsInt($data['memory_peak']);
        self::assertIsString($data['memory_limit']);
        self::assertIsFloat($data['execution_time']);
        self::assertIsInt($data['database_connections']);
        self::assertIsInt($data['cache_hits']);
        self::assertIsFloat($data['response_time']);
    }

    #[Test]
    public function itHandlesUptimeCalculation(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200);

        $data = $response->json('data');
        self::assertArrayHasKey('uptime', $data);
        self::assertIsString($data['uptime']);
    }

    #[Test]
    public function itHandlesLoadAverageCalculation(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200);

        $data = $response->json('data');
        self::assertArrayHasKey('load_average', $data);
        self::assertTrue(\is_array($data['load_average']) || \is_string($data['load_average']), 'load_average is not an array or a string');
    }

    #[Test]
    public function itHandlesCpuCountCalculation(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200);

        $data = $response->json('data');
        self::assertArrayHasKey('cpu_count', $data);
        self::assertIsInt($data['cpu_count']);
        self::assertGreaterThan(0, $data['cpu_count']);
    }
}
