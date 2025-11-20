<?php

declare(strict_types=1);

namespace App\Services\Backup\Strategies;

use App\Services\Backup\Services\BackupDatabaseService;

final readonly class DatabaseBackupStrategy implements BackupStrategyInterface
{
    private BackupDatabaseService $databaseService;

    #[\Override]
    public function restore(string $backupPath, array $backupInfo): array
    {
        try {
            return $this->databaseService->restoreDatabase($backupPath, $backupInfo);
        } catch (\Exception $e) {
            throw new \Exception("Database restoration failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @psalm-return 'database'
     */
    #[\Override]
    public function getComponentName(): string
    {
        return 'database';
    }
}
