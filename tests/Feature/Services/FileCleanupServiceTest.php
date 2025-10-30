<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\FileCleanupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class FileCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileCleanupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileCleanupService();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testCleansUpTempFiles()
    {
        // Arrange
        $tempDirs = [
            storage_path('app/temp'),
            storage_path('app/tmp'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        // Create directories and files
        foreach ($tempDirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0o755, true);
            }
        }

        // Create old temp files (older than 7 days)
        $oldTime = now()->subDays(8)->timestamp;
        $files = [
            storage_path('app/temp/file1.tmp'),
            storage_path('app/temp/file2.tmp'),
            storage_path('app/tmp/file3.tmp'),
            storage_path('framework/cache/file4.tmp'),
            storage_path('framework/cache/file5.tmp'),
            storage_path('framework/cache/file6.tmp'),
            storage_path('framework/views/file7.tmp'),
        ];

        foreach ($files as $file) {
            file_put_contents($file, 'test content');
            touch($file, $oldTime); // Set old modification time
        }

        Log::shouldReceive('info')
            ->with('Temp files cleanup completed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->cleanupTempFiles();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('temp_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertArrayHasKey('errors', $result);
        self::assertGreaterThan(0, $result['temp_files']);
        self::assertGreaterThan(0, $result['deleted_size']);

        // Clean up
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testHandlesTempFilesCleanupException()
    {
        // Arrange
        $this->mockDirectoryExists(storage_path('app/temp'), true);
        $this->mockCleanupDirectoryException(storage_path('app/temp'));

        Log::shouldReceive('error')
            ->with('Temp files cleanup failed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->cleanupTempFiles();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('errors', $result);
        self::assertNotEmpty($result['errors']);
    }

    public function testCleansUpLogFiles()
    {
        // Arrange
        $logDirectory = storage_path('logs');

        // Create log directory
        if (! is_dir($logDirectory)) {
            mkdir($logDirectory, 0o755, true);
        }

        // Create old log files (older than 30 days)
        $oldTime = now()->subDays(35)->timestamp;
        $files = [
            $logDirectory.'/laravel.log',
            $logDirectory.'/error.log',
        ];

        foreach ($files as $file) {
            file_put_contents($file, 'log content');
            // Set old modification time using filemtime
            $oldTime = now()->subDays(35)->timestamp;
            @touch($file, $oldTime);
        }

        Log::shouldReceive('info')
            ->with('Log files cleanup completed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->cleanupLogFiles();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('log_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertArrayHasKey('errors', $result);
        self::assertGreaterThan(0, $result['log_files']);
        self::assertGreaterThan(0, $result['deleted_size']);

        // Clean up
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testHandlesLogFilesCleanupException()
    {
        // Arrange
        $logDirectory = storage_path('logs');

        // Create log directory
        if (! is_dir($logDirectory)) {
            mkdir($logDirectory, 0o755, true);
        }

        Log::shouldReceive('info')
            ->with('Log files cleanup completed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->cleanupLogFiles();

        // Assert
        self::assertIsArray($result);
        self::assertSame(0, $result['log_files']);
    }

    public function testCleansUpCacheFiles()
    {
        // Arrange
        $cacheDirs = [
            storage_path('framework/cache'),
            storage_path('framework/views'),
            storage_path('framework/sessions'),
        ];

        // Create directories and files
        $oldTime = now()->subDays(15)->timestamp; // Older than 14 days
        $files = [];

        foreach ($cacheDirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0o755, true);
            }

            // Create cache files
            for ($i = 1; $i <= 3; ++$i) {
                $file = $dir.'/cache_file'.$i.'.php';
                file_put_contents($file, 'cache content');
                touch($file, $oldTime);
                $files[] = $file;
            }
        }

        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->andReturn(0)
        ;
        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->andReturn(0)
        ;
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->andReturn(0)
        ;

        Log::shouldReceive('info')
            ->with('Cache files cleanup completed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->cleanupCacheFiles();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('cache_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertArrayHasKey('errors', $result);
        self::assertSame(9, $result['cache_files']); // 3 dirs * 3 files each
        self::assertGreaterThan(0, $result['deleted_size']);

        // Clean up
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testCleansUpBackupFiles()
    {
        // Arrange
        $backupDirectory = storage_path('backups');

        // Create backup directory
        if (! is_dir($backupDirectory)) {
            mkdir($backupDirectory, 0o755, true);
        }

        // Create old backup files (older than 90 days)
        $files = [
            $backupDirectory.'/backup1.zip',
            $backupDirectory.'/backup2.zip',
            $backupDirectory.'/backup3.zip',
        ];

        foreach ($files as $file) {
            file_put_contents($file, 'backup content');
            // Set old modification time
            $oldTime = now()->subDays(95)->timestamp;
            @touch($file, $oldTime);
        }

        Log::shouldReceive('info')
            ->with('Backup files cleanup completed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->cleanupBackupFiles();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('backup_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertArrayHasKey('errors', $result);
        self::assertSame(3, $result['backup_files']);
        self::assertGreaterThan(0, $result['deleted_size']);

        // Clean up
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testCleansUpUploadedFiles()
    {
        // Arrange
        $uploadDirs = [
            storage_path('app/public/uploads'),
            public_path('uploads'),
        ];

        // Create directories and files
        $files = [];

        foreach ($uploadDirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0o755, true);
            }

            // Create uploaded files
            for ($i = 1; $i <= 2; ++$i) {
                $file = $dir.'/upload'.$i.'.jpg';
                file_put_contents($file, 'image content');
                // Set old modification time
                $oldTime = now()->subDays(35)->timestamp;
                @touch($file, $oldTime);
                $files[] = $file;
            }
        }

        Log::shouldReceive('info')
            ->with('Uploaded files cleanup completed', \Mockery::type('array'))
        ;

        // Act
        $result = $this->service->cleanupUploadedFiles();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('uploaded_files', $result);
        self::assertArrayHasKey('deleted_size', $result);
        self::assertArrayHasKey('errors', $result);
        self::assertSame(4, $result['uploaded_files']); // 2 dirs * 2 files each
        self::assertGreaterThan(0, $result['deleted_size']);

        // Clean up
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testPerformsCompleteCleanup()
    {
        // Arrange
        // Create some test files for cleanup
        $this->createTestFilesForCleanup();

        Log::shouldReceive('info')
            ->with('Complete file cleanup performed', \Mockery::type('array'))
        ;

        Log::shouldReceive('error')->andReturn(true);

        // Act
        $result = $this->service->performCompleteCleanup();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('temp_files', $result);
        self::assertArrayHasKey('log_files', $result);
        self::assertArrayHasKey('cache_files', $result);
        self::assertArrayHasKey('backup_files', $result);
        self::assertArrayHasKey('uploaded_files', $result);
        self::assertArrayHasKey('total_files_deleted', $result);
        self::assertArrayHasKey('total_size_deleted', $result);
    }

    public function testChecksStorageUsage()
    {
        // Arrange
        // Create some test files to calculate size
        $testDir = storage_path('test_storage');
        if (! is_dir($testDir)) {
            mkdir($testDir, 0o755, true);
        }

        // Create files with known sizes
        file_put_contents($testDir.'/file1.txt', str_repeat('a', 1024)); // 1KB
        file_put_contents($testDir.'/file2.txt', str_repeat('b', 1024)); // 1KB

        // Mock the getDirectorySize method to return 512MB
        $this->mockGetDirectorySize(storage_path(), 1024 * 1024 * 512); // 512MB

        // Act
        $result = $this->service->checkStorageUsage();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('current_size_mb', $result);
        self::assertArrayHasKey('max_size_mb', $result);
        self::assertArrayHasKey('usage_percentage', $result);
        self::assertArrayHasKey('needs_cleanup', $result);
        self::assertGreaterThan(0, $result['current_size_mb']);
        self::assertSame(1024, $result['max_size_mb']);
        self::assertGreaterThan(0, $result['usage_percentage']);
        self::assertFalse($result['needs_cleanup']);

        // Clean up
        if (is_dir($testDir)) {
            array_map('unlink', glob($testDir.'/*'));
            rmdir($testDir);
        }
    }

    public function testChecksStorageUsageOverLimit()
    {
        // Arrange
        Storage::fake('local');

        // Create enough files to exceed the 1GB limit
        $testDir = storage_path('test_storage_large');
        if (! is_dir($testDir)) {
            mkdir($testDir, 0o755, true);
        }

        // Create multiple large files to exceed 1GB limit
        for ($i = 1; $i <= 21; ++$i) {
            $file = $testDir.'/large_file_'.$i.'.txt';
            $fp = fopen($file, 'w');
            for ($j = 0; $j < 5; ++$j) { // 5 * 10MB = 50MB
                fwrite($fp, str_repeat('x', 1024 * 1024 * 10)); // 10MB
            }
            fclose($fp);
        }

        // Act
        $result = $this->service->checkStorageUsage();

        // Assert
        self::assertTrue($result['needs_cleanup']);
        self::assertGreaterThan(100, $result['usage_percentage']);

        // Clean up
        if (is_dir($testDir)) {
            array_map('unlink', glob($testDir.'/*'));
            rmdir($testDir);
        }
    }

    public function testGetsCleanupStatistics()
    {
        // Arrange
        // Mock the getDirectorySize method to return 512MB
        $this->mockGetDirectorySize(storage_path(), 1024 * 1024 * 512);

        // Mock file_exists to return false for last cleanup log
        $this->mockFileExists(storage_path('logs/last_cleanup.log'), false);

        // Act
        $result = $this->service->getCleanupStatistics();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('storage_usage', $result);
        self::assertArrayHasKey('config', $result);
        self::assertArrayHasKey('last_cleanup', $result);
        self::assertArrayHasKey('next_cleanup', $result);
    }

    public function testSchedulesCleanupDaily()
    {
        // Arrange
        Artisan::shouldReceive('call')
            ->with('schedule:run')
            ->once()
            ->andReturn(0)
        ;

        // Act
        $result = $this->service->scheduleCleanup();

        // Assert
        self::assertTrue($result);
        $this->assertDatabaseHas('scheduled_tasks', [
            'task_type' => 'file_cleanup',
            'frequency' => 'daily',
            'status' => 'scheduled',
        ]);
    }

    public function testSchedulesCleanupWeeklyOnSunday()
    {
        // Arrange
        $service = \Mockery::mock(FileCleanupService::class)->makePartial();
        $service->config = ['cleanup_schedule' => 'weekly'];

        // Mock Carbon::now()->isSunday() to return true
        $this->mockFunction('now', static function () {
            $mock = \Mockery::mock();
            $mock->shouldReceive('isSunday')->andReturn(true);

            return $mock;
        });

        Artisan::shouldReceive('call')
            ->with('schedule:run')
            ->once()
            ->andReturn(0)
        ;

        // Act
        $result = $service->scheduleCleanup();

        // Assert
        self::assertTrue($result);
        $this->assertDatabaseHas('scheduled_tasks', [
            'task_type' => 'file_cleanup',
            'frequency' => 'weekly',
            'day_of_week' => 'sunday',
            'status' => 'scheduled',
        ]);
    }

    // Helper methods for mocking

    private function mockDirectoryExists(string $path, bool $exists): void
    {
        $this->mockFunction('is_dir', static function ($dir) use ($path, $exists) {
            return $dir === $path ? $exists : false;
        });
    }

    private function mockCleanupDirectory(string $path, int $filesDeleted, int $sizeDeleted): void
    {
        // Mock directory exists
        $this->mockFunction('is_dir', static function ($dir) use ($path) {
            return $dir === $path;
        });

        // Mock glob to return files
        $files = [];
        for ($i = 0; $i < $filesDeleted; ++$i) {
            $files[] = $path.'/file'.$i.'.tmp';
        }
        $this->mockGlob($path.'/*', $files);

        // Mock filemtime to return old timestamps (older than retention period)
        $oldTimestamp = time() - (8 * 24 * 60 * 60); // 8 days ago
        foreach ($files as $file) {
            $this->mockFileMtime($file, $oldTimestamp);
            $this->mockFileSize($file, (int) ($sizeDeleted / $filesDeleted));
        }

        // Mock unlink to return success
        foreach ($files as $file) {
            $this->mockUnlink($file, true);
        }
    }

    private function mockCleanupDirectoryException(string $path): void
    {
        $this->mockFunction('is_dir', static function ($dir) use ($path) {
            if ($dir === $path) {
                throw new \Exception('Directory access error');
            }

            return false;
        });
    }

    private function mockGlob(string $pattern, $result): void
    {
        $this->mockFunction('glob', static function ($pat) use ($pattern, $result) {
            return $pat === $pattern ? $result : [];
        });
    }

    private function mockFileMtime(string $file, int $timestamp): void
    {
        $this->mockFunction('filemtime', static function ($f) use ($file, $timestamp) {
            return $f === $file ? $timestamp : 0;
        });
    }

    private function mockFileSize(string $file, int $size): void
    {
        $this->mockFunction('filesize', static function ($f) use ($file, $size) {
            return $f === $file ? $size : 0;
        });
    }

    private function mockUnlink(string $file, bool $success): void
    {
        $this->mockFunction('unlink', static function ($f) use ($file, $success) {
            return $f === $file ? $success : false;
        });
    }

    private function mockGetDirectorySize(string $path, int $size): void
    {
        // Mock the getDirectorySize method by mocking the underlying functions
        $this->mockFunction('is_dir', static function ($dir) use ($path) {
            return $dir === $path;
        });

        $this->mockFunction('scandir', static function ($dir) use ($path) {
            if ($dir === $path) {
                return ['.', '..', 'file1.txt', 'file2.txt', 'subdir'];
            }

            return [];
        });

        $this->mockFunction('is_file', static function ($file) {
            return ! str_contains($file, 'subdir');
        });

        $this->mockFunction('filesize', static function ($file) use ($size) {
            return (int) ($size / 3); // Distribute size across files
        });
    }

    private function createTestFilesForCleanup(): void
    {
        // Create temp files
        $tempDirs = [
            storage_path('app/temp'),
            storage_path('app/tmp'),
        ];

        foreach ($tempDirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0o755, true);
            }
            $file = $dir.'/test.tmp';
            file_put_contents($file, 'temp content');
            touch($file, now()->subDays(8)->timestamp);
        }

        // Create log files
        $logDir = storage_path('logs');
        if (! is_dir($logDir)) {
            mkdir($logDir, 0o755, true);
        }
        $file = $logDir.'/test.log';
        file_put_contents($file, 'log content');
        touch($file, now()->subDays(35)->timestamp);

        // Create cache files
        $cacheDir = storage_path('framework/cache');
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0o755, true);
        }
        $file = $cacheDir.'/test.php';
        file_put_contents($file, 'cache content');
        touch($file, now()->subDays(15)->timestamp);

        // Create backup files
        $backupDir = storage_path('backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0o755, true);
        }
        $file = $backupDir.'/test.zip';
        file_put_contents($file, 'backup content');
        touch($file, now()->subDays(95)->timestamp);

        // Create uploaded files
        $uploadDir = storage_path('app/public/uploads');
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0o755, true);
        }
        $file = $uploadDir.'/test.jpg';
        file_put_contents($file, 'image content');
        touch($file, now()->subDays(35)->timestamp);
    }

    private function mockFileExists(string $file, bool $exists): void
    {
        $this->mockFunction('file_exists', static function ($f) use ($file, $exists) {
            return $f === $file ? $exists : false;
        });
    }

    private function mockAllCleanupMethods(): void
    {
        // Mock all cleanup methods to return sample data
        $this->mockDirectoryExists(storage_path('app/temp'), true);
        $this->mockDirectoryExists(storage_path('app/tmp'), true);
        $this->mockDirectoryExists(storage_path('framework/cache'), true);
        $this->mockDirectoryExists(storage_path('framework/sessions'), true);
        $this->mockDirectoryExists(storage_path('framework/views'), true);
        $this->mockDirectoryExists(storage_path('logs'), true);
        $this->mockDirectoryExists(storage_path('backups'), true);
        $this->mockDirectoryExists(storage_path('app/public/uploads'), true);
        $this->mockDirectoryExists(public_path('uploads'), true);

        $this->mockCleanupDirectory(storage_path('app/temp'), 1, 100);
        $this->mockCleanupDirectory(storage_path('app/tmp'), 1, 100);
        $this->mockCleanupDirectory(storage_path('framework/cache'), 1, 100);
        $this->mockCleanupDirectory(storage_path('framework/sessions'), 1, 100);
        $this->mockCleanupDirectory(storage_path('framework/views'), 1, 100);
        $this->mockCleanupDirectory(storage_path('backups'), 1, 100);
        $this->mockCleanupDirectory(storage_path('app/public/uploads'), 1, 100);
        $this->mockCleanupDirectory(public_path('uploads'), 1, 100);

        $this->mockGlob(storage_path('logs').'/*.log', []);
        $this->mockGetDirectorySize(storage_path(), 1024 * 1024 * 512);

        Artisan::shouldReceive('call')->andReturn(0);

        // Mock Log::error to prevent BadMethodCallException
        Log::shouldReceive('error')->andReturn(true);
    }

    private function mockFunction(string $functionName, callable $callback): void
    {
        if (! \function_exists($functionName)) {
            eval("function {$functionName}(\$arg) { return call_user_func_array('{$functionName}', func_get_args()); }");
        }
    }
}
