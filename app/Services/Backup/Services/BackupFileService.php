<?php

declare(strict_types=1);

namespace App\Services\Backup\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupFileService
{
    /**
     * Create files backup.
     */
    public function createFilesBackup(string $backupPath, array $directories = []): bool
    {
        try {
            $zip = new \ZipArchive();

            if (true !== $zip->open($backupPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                Log::error('Cannot create backup zip file', ['path' => $backupPath]);

                return false;
            }

            if (empty($directories)) {
                $directories = $this->getDefaultBackupDirectories();
            }

            foreach ($directories as $directory) {
                $this->addDirectoryToZip($zip, $directory);
            }

            $zip->close();

            return file_exists($backupPath);
        } catch (\Exception $e) {
            Log::error('Files backup failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Clean old backup files.
     */
    public function cleanOldBackups(string $directory, int $keepDays = 30): void
    {
        try {
            $cutoffTime = time() - ($keepDays * 86400);

            $files = Storage::disk('local')->files($directory);

            foreach ($files as $file) {
                $fileTime = Storage::disk('local')->lastModified($file);

                if ($fileTime < $cutoffTime) {
                    Storage::disk('local')->delete($file);
                    Log::info('Deleted old backup file', ['file' => $file]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to clean old backups', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get backup size.
     */
    public function getBackupSize(string $path): int
    {
        if (! file_exists($path)) {
            return 0;
        }

        return filesize($path) ?: 0;
    }

    /**
     * Validate backup file.
     */
    public function validateBackup(string $path): bool
    {
        if (! file_exists($path)) {
            return false;
        }

        // Check if it's a valid zip file
        if (str_ends_with($path, '.zip')) {
            $zip = new \ZipArchive();
            $result = $zip->open($path, \ZipArchive::CHECKCONS);

            if (true === $result) {
                $zip->close();

                return true;
            }

            return false;
        }

        // For SQL files, just check if file is not empty
        return filesize($path) > 0;
    }

    /**
     * Get default directories to backup.
     */
    private function getDefaultBackupDirectories(): array
    {
        return [
            base_path('app'),
            base_path('config'),
            base_path('database'),
            base_path('resources'),
            base_path('routes'),
        ];
    }

    /**
     * Add directory to zip archive.
     */
    private function addDirectoryToZip(\ZipArchive $zip, string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = str_replace(base_path().\DIRECTORY_SEPARATOR, '', $filePath);

            // Skip vendor and node_modules
            if (str_contains($relativePath, 'vendor') || str_contains($relativePath, 'node_modules')) {
                continue;
            }

            $zip->addFile($filePath, $relativePath);
        }
    }
}
