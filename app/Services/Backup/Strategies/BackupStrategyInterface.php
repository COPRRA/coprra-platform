<?php

declare(strict_types=1);

namespace App\Services\Backup\Strategies;

interface BackupStrategyInterface
{
    /**
     * Restore from backup.
     *
     * @param string               $backupPath The path to the backup directory
     * @param array<string, mixed> $backupInfo Information about the backup component
     *
     * @return array<string, mixed>
     */
    public function restore(string $backupPath, array $backupInfo): array;

    /**
     * Get the component name this strategy handles.
     */
    public function getComponentName(): string;
}
