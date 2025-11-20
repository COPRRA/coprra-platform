<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for monitoring storage usage and providing insights.
 */
class StorageMonitoringService
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? Log::channel('storage');
        $this->config = config('storage.monitoring', [
            'warning_threshold' => 80,
            'critical_threshold' => 95,
        ]);
    }

    /**
     * Monitor storage usage and return status.
     *
     * @return array{
     *     total_space_gb: float,
     *     used_space_gb: float,
     *     free_space_gb: float,
     *     usage_percentage: float,
     *     status: string,
     *     recommendations: list<string>
     * }
     */
    public function monitorStorageUsage(): array
    {
        $storagePath = storage_path();
        $totalSpace = disk_total_space($storagePath);
        $freeSpace = disk_free_space($storagePath);

        if (false === $totalSpace || false === $freeSpace) {
            throw new \RuntimeException('Unable to retrieve storage information');
        }

        $usedSpace = $totalSpace - $freeSpace;
        $usagePercentage = ($usedSpace / $totalSpace) * 100;

        $result = [
            'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
            'used_space_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
            'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
            'usage_percentage' => round($usagePercentage, 2),
            'status' => $this->getStorageStatus($usagePercentage),
            'recommendations' => $this->generateRecommendations($usagePercentage),
        ];

        $this->logger->info('Storage usage monitored', $result);

        return $result;
    }

    /**
     * Get detailed breakdown of storage usage by directory.
     *
     * @return array<string, array{
     *     path: string,
     *     size_mb: float,
     *     percentage: float,
     *     file_count: int
     * }>
     */
    public function getStorageBreakdown(): array
    {
        $directories = $this->getBreakdownDirectories();
        $breakdown = [];
        $totalSize = 0;

        foreach ($directories as $name => $path) {
            $size = $this->getDirectorySize($path);
            $totalSize += $size;
            $breakdown[$name] = [
                'path' => $path,
                'size_mb' => round($size / 1024 / 1024, 2),
                'percentage' => 0, // Will be calculated after total is known
                'file_count' => \count($this->getFilesInDirectory($path)),
            ];
        }

        // Calculate percentages
        foreach ($breakdown as $name => &$data) {
            $data['percentage'] = $totalSize > 0
                ? round(($data['size_mb'] * 1024 * 1024 / $totalSize) * 100, 2)
                : 0;
        }

        return $breakdown;
    }

    /**
     * Get storage statistics for a specific directory.
     *
     * @return array{
     *     total_files: int,
     *     oldest_file: string|null,
     *     newest_file: string|null
     * }
     */
    public function getStorageStats(string $directory): array
    {
        $files = $this->getFilesInDirectory($directory);
        $totalFiles = \count($files);
        $oldestFile = null;
        $newestFile = null;

        foreach ($files as $file) {
            $fileTime = filemtime($file);
            if (false === $fileTime) {
                continue;
            }

            $this->updateMinMaxFileTime($fileTime, $oldestFile, $newestFile);
        }

        return [
            'total_files' => $totalFiles,
            'oldest_file' => $oldestFile ? date('Y-m-d H:i:s', $oldestFile) : null,
            'newest_file' => $newestFile ? date('Y-m-d H:i:s', $newestFile) : null,
        ];
    }

    private function getStorageStatus(float $usagePercentage): string
    {
        $criticalThreshold = $this->config['critical_threshold'] ?? 95;
        if ($usagePercentage >= $criticalThreshold) {
            return 'critical';
        }

        if ($usagePercentage >= ($this->config['warning_threshold'] ?? 80)) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * @return list<string>
     */
    private function generateRecommendations(float $usagePercentage): array
    {
        $recommendations = [];

        if ($usagePercentage >= 90) {
            $recommendations[] = 'Critical: Immediate cleanup required';
            $recommendations[] = 'Consider archiving old files';
            $recommendations[] = 'Review and delete unnecessary backups';
        } elseif ($usagePercentage >= 80) {
            $recommendations[] = 'Warning: Monitor storage closely';
            $recommendations[] = 'Schedule regular cleanup';
            $recommendations[] = 'Consider compression for old files';
        } elseif ($usagePercentage >= 70) {
            $recommendations[] = 'Consider implementing automated cleanup';
            $recommendations[] = 'Review storage policies';
        }

        return $recommendations;
    }

    private function getBreakdownDirectories(): array
    {
        return [
            'logs' => storage_path('logs'),
            'cache' => storage_path('framework/cache'),
            'sessions' => storage_path('framework/sessions'),
            'views' => storage_path('framework/views'),
            'temp' => storage_path('app/temp'),
            'backups' => storage_path('backups'),
            'uploads' => storage_path('app/public/uploads'),
            'other' => storage_path('app'),
        ];
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

    /**
     * @param-out int $oldestFile
     * @param-out int $newestFile
     */
    private function updateMinMaxFileTime(int $fileTime, ?int &$oldestFile, ?int &$newestFile): void
    {
        if (null === $oldestFile || $fileTime < $oldestFile) {
            $oldestFile = $fileTime;
        }

        if (null === $newestFile || $fileTime > $newestFile) {
            $newestFile = $fileTime;
        }
    }
}
