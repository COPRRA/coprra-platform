<?php

declare(strict_types=1);

namespace App\Services\Backup\Strategies;

use App\Services\Backup\Services\BackupConfigurationService;

final readonly class ConfigurationBackupStrategy implements BackupStrategyInterface
{
    private BackupConfigurationService $configurationService;

    /**
     * Backup configuration files.
     */
    public function backup(string $backupDir, string $backupName): array
    {
        try {
            return $this->configurationService->backupConfiguration($backupDir);
        } catch (\Exception $e) {
            throw new \Exception("Configuration backup failed: {$e->getMessage()}", 0, $e);
        }
    }

    #[\Override]
    public function restore(string $backupPath, array $backupInfo): array
    {
        try {
            return $this->configurationService->restoreConfiguration($backupPath, $backupInfo);
        } catch (\Exception $e) {
            throw new \Exception("Configuration restoration failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @psalm-return 'config'
     */
    #[\Override]
    public function getComponentName(): string
    {
        return 'config';
    }

    /**
     * Check if this strategy can handle the backup info.
     */
    public function canHandle(array $backupInfo): bool
    {
        return isset($backupInfo['files'])
               && isset($backupInfo['size'], $backupInfo['status']);
    }
}
