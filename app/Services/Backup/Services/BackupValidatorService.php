<?php

declare(strict_types=1);

namespace App\Services\Backup\Services;

final class BackupValidatorService
{
    /**
     * Validate backup parameters.
     *
     * @throws \Exception
     */
    public function validateBackupParameters(string $type, array $options): void
    {
        $this->validateBackupType($type);
        $this->validateBackupOptions($options);
    }

    /**
     * Validate restore parameters.
     *
     * @throws \Exception
     */
    public function validateRestoreParameters(string $backupPath, array $options): void
    {
        $this->validateBackupPath($backupPath);
        $this->validateRestoreOptions($options);
    }

    /**
     * Validate backup type.
     *
     * @throws \Exception
     */
    private function validateBackupType(string $type): void
    {
        $validTypes = ['full', 'database', 'files'];

        if (! \in_array($type, $validTypes, true)) {
            throw new \Exception("Invalid backup type: {$type}. Valid types: ".implode(', ', $validTypes));
        }
    }

    /**
     * Validate backup options.
     *
     * @throws \Exception
     */
    private function validateBackupOptions(array $options): void
    {
        if (isset($options['directories']) && ! \is_array($options['directories'])) {
            throw new \Exception('Backup directories must be an array');
        }

        if (isset($options['tables']) && ! \is_array($options['tables'])) {
            throw new \Exception('Backup tables must be an array');
        }

        if (isset($options['compress']) && ! \is_bool($options['compress'])) {
            throw new \Exception('Backup compress option must be a boolean');
        }
    }

    /**
     * Validate backup path.
     *
     * @throws \Exception
     */
    private function validateBackupPath(string $backupPath): void
    {
        if (! file_exists($backupPath)) {
            throw new \Exception("Backup file does not exist: {$backupPath}");
        }

        if (! is_readable($backupPath)) {
            throw new \Exception("Backup file is not readable: {$backupPath}");
        }
    }

    /**
     * Validate restore options.
     *
     * @throws \Exception
     */
    private function validateRestoreOptions(array $options): void
    {
        if (isset($options['components']) && ! \is_array($options['components'])) {
            throw new \Exception('Restore components must be an array');
        }

        if (isset($options['overwrite']) && ! \is_bool($options['overwrite'])) {
            throw new \Exception('Restore overwrite option must be a boolean');
        }
    }
}
