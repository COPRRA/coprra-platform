<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
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
final class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanGetApplicationLogs(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);

        // Create some log entries
        Log::info('Test log entry 1');
        Log::error('Test error entry');
        Log::warning('Test warning entry');

        $response = $this->actingAs($admin)->getJson('/admin/logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'level',
                    'message',
                    'timestamp',
                    'context',
                ],
            ],
            'meta' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
            ],
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanClearApplicationLogs(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);

        // Create some log entries
        Log::info('Test log entry to be cleared');
        Log::error('Test error entry to be cleared');

        $response = $this->actingAs($admin)->deleteJson('/admin/logs');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Logs cleared successfully',
            'status' => 'success',
        ]);
    }

    #[Test]
    public function itRequiresAuthenticationForLogAccess(): void
    {
        $response = $this->getJson('/admin/logs');

        $response->assertStatus(401);
    }

    #[Test]
    public function itRequiresAdminRoleForLogAccess(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->getJson('/admin/logs');

        $response->assertStatus(403);
    }

    #[Test]
    public function itCanFilterLogsByLevel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create logs of different levels
        Log::info('Info message');
        Log::error('Error message');
        Log::warning('Warning message');

        $response = $this->actingAs($admin)->getJson('/admin/logs?level=error');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'level',
                    'message',
                    'timestamp',
                ],
            ],
        ]);
    }

    #[Test]
    public function itCanFilterLogsByDateRange(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson('/admin/logs?from=2024-01-01&to=2024-01-31');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [],
            'meta' => [
                'total',
                'filters' => [
                    'from',
                    'to',
                ],
            ],
        ]);
    }

    #[Test]
    public function itCanSearchLogsWithQuery(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Log::info('User login successful');
        Log::error('Database connection failed');

        $response = $this->actingAs($admin)->getJson('/admin/logs?search=login');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [],
            'meta' => [
                'search_query',
                'total_results',
            ],
        ]);
    }

    #[Test]
    public function itCanDownloadLogsAsFile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Log::info('Test log for download');

        $response = $this->actingAs($admin)->get('/admin/logs/download');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain');
        $response->assertHeader('Content-Disposition');
    }

    #[Test]
    public function itValidatesLogFilterParameters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson('/admin/logs?level=invalid_level');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['level']);
    }
}
