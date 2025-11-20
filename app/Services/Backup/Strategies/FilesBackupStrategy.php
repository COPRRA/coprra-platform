<?php

declare(strict_types=1);

namespace App\Services\Backup\Strategies;

use App\Services\Backup\Services\BackupFileSystemService;

final readonly class FilesBackupStrategy implements BackupStrategyInterface
{
    private BackupFileSystemService $fileSystemService;

    #[\Override]
    public function restore(string $backupPath, array $backupInfo): array
    {
        try {
            return $this->fileSystemService->restoreFiles($backupPath, $backupInfo);
        } catch (\Exception $e) {
            throw new \Exception("Files restoration failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @psalm-return 'files'
     */
    #[\Override]
    public function getComponentName(): string
    {
        return 'files';
    }
}
