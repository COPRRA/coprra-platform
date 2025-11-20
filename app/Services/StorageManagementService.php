<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Storage\StorageArchivalService;
use App\Services\Storage\StorageCompressionService;
use App\Services\Storage\StorageMonitoringService;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Service for managing storage operations using specialized storage services.
 * Acts as a facade for storage monitoring, cleanup, compression, and archival.
 */
class StorageManagementService
{
    private FileCleanupService $cleanupService;
    private StorageMonitoringService $monitoringService;
    private StorageCompressionService $compressionService;
    private StorageArchivalService $archivalService;
    private LoggerInterface $logger;
    private array $config;

    public function __construct(
        FileCleanupService $cleanupService,
        StorageMonitoringService $monitoringService,
        StorageCompressionService $compressionService,
        StorageArchivalService $archivalService,
        ?LoggerInterface $logger = null
    ) {
        $this->cleanupService = $cleanupService;
        $this->monitoringService = $monitoringService;
        $this->compressionService = $compressionService;
        $this->archivalService = $archivalService;
        $this->logger = $logger ?? Log::channel('storage');
        $this->config = config('storage.management', [
            'cleanup_enabled' => true,
            'auto_cleanup_priority' => ['temp', 'logs', 'cache'],
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
        return $this->monitoringService->monitorStorageUsage();
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
        return $this->monitoringService->getStorageBreakdown();
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
        return $this->monitoringService->getStorageStats($directory);
    }

    /**
     * Auto cleanup if needed based on storage usage.
     *
     * @return array{
     *     cleanup_performed: bool,
     *     reason?: string,
     *     usage?: array<string, mixed>,
     *     cleanup_results?: array<string, mixed>
     * }
     */
    public function autoCleanupIfNeeded(): array
    {
        $usage = $this->monitorStorageUsage();

        if (! $this->isCleanupNeeded($usage)) {
            return [
                'cleanup_performed' => false,
                'reason' => 'No cleanup needed or auto cleanup disabled',
                'usage' => $usage,
            ];
        }

        $cleanupResults = $this->performCleanup($this->config['auto_cleanup_priority']);

        return [
            'cleanup_performed' => true,
            'reason' => 'Storage usage exceeded threshold',
            'usage' => $usage,
            'cleanup_results' => $cleanupResults,
        ];
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
        return $this->compressionService->compressFiles($directories);
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
        return $this->archivalService->archiveFiles($directories);
    }

    /**
     * Perform comprehensive storage optimization.
     *
     * @return array{
     *     cleanup: array<string, mixed>,
     *     compression: array<string, mixed>,
     *     archival: array<string, mixed>,
     *     final_usage: array<string, mixed>
     * }
     */
    public function optimizeStorage(): array
    {
        $this->logger->info('Starting comprehensive storage optimization');

        // Step 1: Cleanup
        $cleanupResults = $this->autoCleanupIfNeeded();

        // Step 2: Compression
        $compressionResults = $this->compressFiles();

        // Step 3: Archival
        $archivalResults = $this->archiveFiles();

        // Step 4: Final usage check
        $finalUsage = $this->monitorStorageUsage();

        $result = [
            'cleanup' => $cleanupResults,
            'compression' => $compressionResults,
            'archival' => $archivalResults,
            'final_usage' => $finalUsage,
        ];

        $this->logger->info('Storage optimization completed', $result);

        return $result;
    }

    /**
     * Update storage limits and configuration.
     *
     * @param array<string, mixed> $limits
     */
    public function updateStorageLimits(array $limits): void
    {
        $this->config = array_merge($this->config, $limits);
        $this->logger->info('Storage limits updated', $limits);
    }

    /**
     * Check if cleanup is needed based on usage.
     *
     * @param array<string, mixed> $usage
     */
    private function isCleanupNeeded(array $usage): bool
    {
        if (! ($this->config['cleanup_enabled'] ?? true)) {
            return false;
        }

        return 'warning' === $usage['status'] || 'critical' === $usage['status'];
    }

    /**
     * Perform cleanup based on priority.
     *
     * @param list<string> $priority
     *
     * @return array<string, mixed>
     */
    private function performCleanup(array $priority): array
    {
        $cleanupResults = [];

        foreach ($priority as $type) {
            $this->executeCleanupType($type, $cleanupResults);

            // Check if storage is now healthy
            $currentUsage = $this->monitorStorageUsage();
            if ('healthy' === $currentUsage['status']) {
                break;
            }
        }

        return $cleanupResults;
    }

    /**
     * Execute specific cleanup type.
     *
     * @param array<string, mixed> $cleanupResults
     */
    private function executeCleanupType(string $type, array &$cleanupResults): void
    {
        $cleanupMethod = 'cleanup'.ucfirst($type).'Files';

        if (method_exists($this->cleanupService, $cleanupMethod)) {
            try {
                $cleanupResults[$type] = $this->cleanupService->{$cleanupMethod}();
            } catch (\Exception $e) {
                $cleanupResults[$type] = ['error' => $e->getMessage()];
                $this->logger->error("Cleanup failed for type: {$type}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
