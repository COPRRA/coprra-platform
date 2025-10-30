<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
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
final class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanGetBackupsList(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);

        // Mock backup files in storage
        Storage::fake('backups');
        Storage::disk('backups')->put('backup-2024-01-01.sql', 'test backup content');
        Storage::disk('backups')->put('backup-2024-01-02.sql', 'test backup content 2');

        $response = $this->actingAs($admin)->getJson('/admin/backups');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'name',
                    'size',
                    'created_at',
                    'download_url',
                ],
            ],
            'meta' => [
                'total_backups',
                'total_size',
            ],
        ]);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanCreateFullBackup(): void
    {
        // Create admin user
        $admin = User::factory()->create(['role' => 'admin']);

        // Mock Artisan command
        Artisan::shouldReceive('call')
            ->with('backup:run')
            ->once()
            ->andReturn(0)
        ;

        $response = $this->actingAs($admin)->postJson('/admin/backups', [
            'type' => 'full',
            'description' => 'Test backup',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Backup created successfully',
            'status' => 'success',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'backup_id',
                'filename',
                'created_at',
            ],
        ]);
    }

    #[Test]
    public function itRequiresAuthenticationForBackupsList(): void
    {
        $response = $this->getJson('/admin/backups');

        $response->assertStatus(401);
    }

    #[Test]
    public function itRequiresAdminRoleForBackupCreation(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->postJson('/admin/backups', [
            'type' => 'full',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function itValidatesBackupCreationRequest(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/admin/backups', [
            'type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function itCanDownloadBackupFile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Storage::fake('backups');
        Storage::disk('backups')->put('backup-test.sql', 'test backup content');

        $response = $this->actingAs($admin)->get('/admin/backups/backup-test.sql/download');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('Content-Disposition', 'attachment; filename="backup-test.sql"');
    }

    #[Test]
    public function itReturns404ForNonexistentBackupFile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Storage::fake('backups');

        $response = $this->actingAs($admin)->get('/admin/backups/nonexistent.sql/download');

        $response->assertStatus(404);
    }

    #[Test]
    public function itCanDeleteBackupFile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Storage::fake('backups');
        Storage::disk('backups')->put('backup-to-delete.sql', 'test backup content');

        $response = $this->actingAs($admin)->deleteJson('/admin/backups/backup-to-delete.sql');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Backup deleted successfully',
        ]);

        Storage::disk('backups')->assertMissing('backup-to-delete.sql');
    }
}
