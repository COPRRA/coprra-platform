<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for compressing files and directories.
 */
class StorageCompressionService
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? Log::channel('storage');
        $this->config = config('storage.compression', [
            'compression_enabled' => true,
            'compression_age_days' => 30,
            'compression_size_mb' => 100,
        ]);
    }

    /**
     * Compress files in specified directories.
     *
     * @param list<string> $directories
     *
     * @return array{
     *     files_compressed: int,
     *     space_saved_mb: float,
     *     errors: list<string>
     * }
     */
    public function compressFiles(array $directories = []): array
    {
        if (! $this->isCompressionEnabled()) {
            return ['compression_disabled' => true];
        }

        $totalFilesCompressed = 0;
        $totalSpaceSaved = 0;
        $errors = [];

        $directoriesToCompress = empty($directories) ? $this->getDefaultCompressionDirectories() : $directories;

        foreach ($directoriesToCompress as $directory) {
            try {
                $result = $this->compressDirectory($directory);
                $totalFilesCompressed += $result['files_compressed'];
                $totalSpaceSaved += $result['space_saved_mb'];
            } catch (\Exception $e) {
                $errors[] = "Failed to compress {$directory}: ".$e->getMessage();
                $this->logger->error('Compression failed', [
                    'directory' => $directory,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $result = [
            'files_compressed' => $totalFilesCompressed,
            'space_saved_mb' => round($totalSpaceSaved, 2),
            'errors' => $errors,
        ];

        $this->logger->info('File compression completed', $result);

        return $result;
    }

    /**
     * Compress a specific directory.
     *
     * @return array{
     *     files_compressed: int,
     *     space_saved_mb: float
     * }
     */
    public function compressDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
            throw new \InvalidArgumentException("Directory does not exist: {$directory}");
        }

        $files = $this->getCompressibleFiles($directory);
        if (empty($files)) {
            return ['files_compressed' => 0, 'space_saved_mb' => 0];
        }

        $originalSize = $this->calculateTotalSize($files);
        $compressedPath = $this->createCompressedArchive($directory, $files);
        $compressedSize = filesize($compressedPath);

        // Remove original files after successful compression
        $this->removeOriginalFiles($files);

        $spaceSaved = ($originalSize - $compressedSize) / 1024 / 1024;

        return [
            'files_compressed' => \count($files),
            'space_saved_mb' => round($spaceSaved, 2),
        ];
    }

    /**
     * Create a compressed archive from files.
     */
    private function createCompressedArchive(string $directory, array $files): string
    {
        $archiveName = basename($directory).'_compressed_'.date('Y-m-d_H-i-s').'.zip';
        $archivePath = $directory.\DIRECTORY_SEPARATOR.$archiveName;

        $zip = new \ZipArchive();
        if (true !== $zip->open($archivePath, \ZipArchive::CREATE)) {
            throw new \RuntimeException("Cannot create archive: {$archivePath}");
        }

        foreach ($files as $file) {
            $relativePath = str_replace($directory.\DIRECTORY_SEPARATOR, '', $file);
            if (! $zip->addFile($file, $relativePath)) {
                $zip->close();

                throw new \RuntimeException("Failed to add file to archive: {$file}");
            }
        }

        if (! $zip->close()) {
            throw new \RuntimeException("Failed to close archive: {$archivePath}");
        }

        return $archivePath;
    }

    /**
     * Get files that are eligible for compression.
     *
     * @return list<string>
     */
    private function getCompressibleFiles(string $directory): array
    {
        $files = [];
        $cutoffTime = time() - ($this->config['compression_age_days'] * 24 * 60 * 60);
        $maxSize = $this->config['compression_size_mb'] * 1024 * 1024;

        if (! is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                // Skip already compressed files
                if (\in_array(strtolower($file->getExtension()), ['zip', 'gz', 'tar', '7z'], true)) {
                    continue;
                }

                // Check if file is old enough and not too large
                if ($file->getMTime() < $cutoffTime && $file->getSize() < $maxSize) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Calculate total size of files.
     *
     * @param list<string> $files
     */
    private function calculateTotalSize(array $files): int
    {
        $totalSize = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }

        return $totalSize;
    }

    /**
     * Remove original files after compression.
     *
     * @param list<string> $files
     */
    private function removeOriginalFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function isCompressionEnabled(): bool
    {
        return $this->config['compression_enabled'] ?? false;
    }

    /**
     * @return list<string>
     */
    private function getDefaultCompressionDirectories(): array
    {
        return [
            storage_path('logs'),
            storage_path('app/temp'),
            storage_path('framework/cache/data'),
        ];
    }
}
