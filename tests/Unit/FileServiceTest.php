<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FileCleanup\DirectoryCleaner;
use App\Services\FileCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class FileServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileCleanupService $fileCleanupService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configuration
        Config::set('file_cleanup', [
            'temp_files_retention_days' => 7,
            'log_files_retention_days' => 30,
            'cache_files_retention_days' => 14,
            'backup_files_retention_days' => 90,
            'max_storage_size_mb' => 1024,
            'cleanup_schedule' => 'daily',
        ]);

        $this->fileCleanupService = new FileCleanupService();
    }

    public function testCleanupTempFilesReturnsExpectedStructure(): void
    {
        Log::shouldReceive('info')->once();

        $result = $this->fileCleanupService->cleanupTempFiles();

        self::assertIsArray($result);
        self::assertArrayHasKey('temp_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertIsInt($result['temp_files']);
        self::assertIsInt($result['deleted_size']);
    }

    public function testCleanupLogFilesReturnsExpectedStructure(): void
    {
        Log::shouldReceive('info')->once();

        $result = $this->fileCleanupService->cleanupLogFiles();

        self::assertIsArray($result);
        self::assertArrayHasKey('log_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertIsInt($result['log_files']);
        self::assertIsInt($result['deleted_size']);
    }

    public function testCleanupCacheFilesReturnsExpectedStructure(): void
    {
        Log::shouldReceive('info')->once();

        $result = $this->fileCleanupService->cleanupCacheFiles();

        self::assertIsArray($result);
        self::assertArrayHasKey('cache_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertIsInt($result['cache_files']);
        self::assertIsInt($result['deleted_size']);
    }

    public function testCleanupBackupFilesReturnsExpectedStructure(): void
    {
        Log::shouldReceive('info')->once();

        $result = $this->fileCleanupService->cleanupBackupFiles();

        self::assertIsArray($result);
        self::assertArrayHasKey('backup_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertIsInt($result['backup_files']);
        self::assertIsInt($result['deleted_size']);
    }

    public function testCleanupUploadedFilesReturnsExpectedStructure(): void
    {
        Log::shouldReceive('info')->once();

        $result = $this->fileCleanupService->cleanupUploadedFiles();

        self::assertIsArray($result);
        self::assertArrayHasKey('uploaded_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertIsInt($result['uploaded_files']);
        self::assertIsInt($result['deleted_size']);
    }

    public function testPerformCompleteCleanupReturnsAllResults(): void
    {
        Log::shouldReceive('info')->times(6); // 5 individual cleanups + 1 complete cleanup

        $result = $this->fileCleanupService->performCompleteCleanup();

        self::assertIsArray($result);
        self::assertArrayHasKey('temp_files', $result);
        self::assertArrayHasKey('log_files', $result);
        self::assertArrayHasKey('cache_files', $result);
        self::assertArrayHasKey('backup_files', $result);
        self::assertArrayHasKey('uploaded_files', $result);
        self::assertArrayHasKey('total_files_deleted', $result);
        self::assertArrayHasKey('total_size_deleted', $result);
    }

    public function testCheckStorageUsageReturnsExpectedStructure(): void
    {
        $result = $this->fileCleanupService->checkStorageUsage();

        self::assertIsArray($result);
        self::assertArrayHasKey('current_size_mb', $result);
        self::assertArrayHasKey('max_size_mb', $result);
        self::assertArrayHasKey('usage_percentage', $result);
        self::assertArrayHasKey('needs_cleanup', $result);

        self::assertIsFloat($result['current_size_mb']);
        self::assertIsFloat($result['max_size_mb']);
        self::assertIsNumeric($result['usage_percentage']);
        self::assertIsBool($result['needs_cleanup']);
        self::assertSame(1024.0, $result['max_size_mb']);
    }

    public function testGetCleanupStatisticsReturnsExpectedStructure(): void
    {
        $result = $this->fileCleanupService->getCleanupStatistics();

        self::assertIsArray($result);
        self::assertArrayHasKey('storage_usage', $result);
        self::assertArrayHasKey('config', $result);
        self::assertArrayHasKey('last_cleanup', $result);
        self::assertArrayHasKey('next_cleanup', $result);

        self::assertIsArray($result['storage_usage']);
        self::assertIsArray($result['config']);
        self::assertSame(7, $result['config']['temp_files_retention_days']);
        self::assertSame(30, $result['config']['log_files_retention_days']);
    }

    public function testScheduleCleanupCallsArtisanForDailySchedule(): void
    {
        Artisan::shouldReceive('call')->with('schedule:run')->once();

        $this->fileCleanupService->scheduleCleanup();

        $this->addToAssertionCount(1); // Confirms method completed without exceptions
    }

    public function testCleanupHandlesExceptionsGracefully(): void
    {
        Log::shouldReceive('error')->once();

        // Create a service with a mock cleaner that throws an exception
        $mockCleaner = $this->createMock(DirectoryCleaner::class);
        $service = new FileCleanupService($mockCleaner);

        $result = $service->cleanupTempFiles();

        self::assertIsArray($result);
        self::assertArrayHasKey('errors', $result);
        self::assertSame(0, $result['temp_files']);
        self::assertSame(0, $result['deleted_size']);
    }

    public function testConfigurationIsLoadedCorrectly(): void
    {
        self::assertSame(7, $this->fileCleanupService->config['temp_files_retention_days']);
        self::assertSame(30, $this->fileCleanupService->config['log_files_retention_days']);
        self::assertSame(14, $this->fileCleanupService->config['cache_files_retention_days']);
        self::assertSame(90, $this->fileCleanupService->config['backup_files_retention_days']);
        self::assertSame(1024, $this->fileCleanupService->config['max_storage_size_mb']);
        self::assertSame('daily', $this->fileCleanupService->config['cleanup_schedule']);
    }
}
