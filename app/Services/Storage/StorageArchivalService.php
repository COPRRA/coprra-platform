<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for archiving old files and directories.
 */
class StorageArchivalService
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? Log::channel('storage');
        $this->config = config('storage.archival', [
            'archival_enabled' => true,
            'archival_age_days' => 90,
            'archive_location' => storage_path('app/archives'),
        ]);
    }

    /**
     * Archive old files from specified directories.
     *
     * @param list<string> $directories
     *
     * @return array{
     *     files_archived: int,
     *     archives_created: int,
     *     space_saved_mb: float,
     *     errors: list<string>
     * }
     */
    public function archiveFiles(array $directories = []): array
    {
        if (! $this->isArchivalEnabled()) {
            return ['archival_disabled' => true];
        }

        $this->ensureArchiveDirectoryExists();

        $totalFilesArchived = 0;
        $totalArchivesCreated = 0;
        $totalSpaceSaved = 0;
        $errors = [];

        $directoriesToArchive = empty($directories) ? $this->getDefaultArchivalDirectories() : $directories;

        foreach ($directoriesToArchive as $directory) {
            try {
                $result = $this->archiveDirectory($directory);
                $totalFilesArchived += $result['files_archived'];
                $totalArchivesCreated += $result['archives_created'];
                $totalSpaceSaved += $result['space_saved_mb'];
            } catch (\Exception $e) {
                $errors[] = "Failed to archive {$directory}: ".$e->getMessage();
                $this->logger->error('Archival failed', [
                    'directory' => $directory,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $result = [
            'files_archived' => $totalFilesArchived,
            'archives_created' => $totalArchivesCreated,
            'space_saved_mb' => round($totalSpaceSaved, 2),
            'errors' => $errors,
        ];

        $this->logger->info('File archival completed', $result);

        return $result;
    }

    /**
     * Archive a specific directory.
     *
     * @return array{
     *     files_archived: int,
     *     archives_created: int,
     *     space_saved_mb: float
     * }
     */
    public function archiveDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
            throw new \InvalidArgumentException("Directory does not exist: {$directory}");
        }

        $filesToArchive = $this->getArchivableFiles($directory);
        if (empty($filesToArchive)) {
            return [
                'files_archived' => 0,
                'archives_created' => 0,
                'space_saved_mb' => 0,
            ];
        }

        $directoryName = basename($directory);
        $result = $this->createArchiveAndGetResult($directory, $directoryName);

        // Remove original files after successful archival
        $this->removeArchivedFiles($filesToArchive);

        return $result;
    }

    /**
     * Get files that are eligible for archival.
     *
     * @return list<string>
     */
    private function getArchivableFiles(string $directory): array
    {
        $files = [];
        $cutoffTime = time() - ($this->config['archival_age_days'] * 24 * 60 * 60);

        if (! is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                // Skip already archived files
                if (false !== strpos($file->getPathname(), 'archives')) {
                    continue;
                }

                // Check if file is old enough for archival
                if ($file->getMTime() < $cutoffTime) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * @return array{files_archived: int, archives_created: int, space_saved_mb: float}
     */
    private function createArchiveAndGetResult(string $directory, string $name): array
    {
        $archivePath = $this->config['archive_location'].\DIRECTORY_SEPARATOR
                      .$name.'_'.date('Y-m-d').'.zip';

        $originalSize = $this->getDirectorySize($directory);
        $this->createArchive($directory, $archivePath);
        $archiveSize = filesize($archivePath);

        return [
            'files_archived' => \count($this->getFilesInDirectory($directory)),
            'archives_created' => 1,
            'space_saved_mb' => round(($originalSize - $archiveSize) / 1024 / 1024, 2),
        ];
    }

    private function createArchive(string $directory, string $archivePath): void
    {
        $zip = new \ZipArchive();

        if (true !== $zip->open($archivePath, \ZipArchive::CREATE)) {
            throw new \RuntimeException("Cannot open <{$archivePath}> for writing");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr((string) $filePath, \strlen($directory) + 1);

                if (! $zip->addFile($filePath, $relativePath)) {
                    $zip->close();

                    throw new \RuntimeException("Failed to add file to archive: {$filePath}");
                }
            }
        }

        if (! $zip->close()) {
            throw new \RuntimeException("Failed to close archive: {$archivePath}");
        }
    }

    /**
     * Remove files after successful archival.
     *
     * @param list<string> $files
     */
    private function removeArchivedFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function getFilesInDirectory(string $directory): array
    {
        $files = [];

        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function getDirectorySize(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function ensureArchiveDirectoryExists(): void
    {
        $archiveDir = $this->config['archive_location'];
        if (! is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }
    }

    private function isArchivalEnabled(): bool
    {
        return $this->config['archival_enabled'] ?? false;
    }

    /**
     * @return list<string>
     */
    private function getDefaultArchivalDirectories(): array
    {
        return [
            storage_path('logs'),
            storage_path('backups'),
            storage_path('app/temp'),
        ];
    }
}
