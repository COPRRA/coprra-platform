<?php

declare(strict_types=1);

namespace App\Services\Backup\Services;

use Illuminate\Support\Facades\Log;

class BackupDatabaseService
{
    /**
     * Create database backup.
     */
    public function createDatabaseBackup(string $backupPath): bool
    {
        try {
            $dbConfig = config('database.connections.'.config('database.default'));

            switch ($dbConfig['driver']) {
                case 'mysql':
                    return $this->backupMySql($dbConfig, $backupPath);

                case 'pgsql':
                    return $this->backupPostgreSql($dbConfig, $backupPath);

                case 'sqlite':
                    return $this->backupSqlite($dbConfig, $backupPath);

                default:
                    Log::warning('Unsupported database driver for backup', ['driver' => $dbConfig['driver']]);

                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Database backup failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Restore database from backup.
     */
    public function restoreDatabase(string $backupPath): bool
    {
        try {
            if (! file_exists($backupPath)) {
                Log::error('Backup file not found', ['path' => $backupPath]);

                return false;
            }

            $dbConfig = config('database.connections.'.config('database.default'));

            switch ($dbConfig['driver']) {
                case 'mysql':
                    return $this->restoreMySql($dbConfig, $backupPath);

                case 'pgsql':
                    return $this->restorePostgreSql($dbConfig, $backupPath);

                case 'sqlite':
                    return $this->restoreSqlite($dbConfig, $backupPath);

                default:
                    Log::warning('Unsupported database driver for restore', ['driver' => $dbConfig['driver']]);

                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Database restore failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Backup MySQL database.
     */
    private function backupMySql(array $config, string $backupPath): bool
    {
        $command = \sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username'] ?? 'root'),
            escapeshellarg($config['password'] ?? ''),
            escapeshellarg($config['database']),
            escapeshellarg($backupPath)
        );

        exec($command, $output, $returnCode);

        return 0 === $returnCode;
    }

    /**
     * Backup PostgreSQL database.
     */
    private function backupPostgreSql(array $config, string $backupPath): bool
    {
        $command = \sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%s --username=%s --dbname=%s > %s',
            escapeshellarg($config['password'] ?? ''),
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 5432)),
            escapeshellarg($config['username'] ?? 'postgres'),
            escapeshellarg($config['database']),
            escapeshellarg($backupPath)
        );

        exec($command, $output, $returnCode);

        return 0 === $returnCode;
    }

    /**
     * Backup SQLite database.
     */
    private function backupSqlite(array $config, string $backupPath): bool
    {
        $databasePath = $config['database'];

        if (! file_exists($databasePath)) {
            return false;
        }

        return copy($databasePath, $backupPath);
    }

    /**
     * Restore MySQL database.
     */
    private function restoreMySql(array $config, string $backupPath): bool
    {
        $command = \sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username'] ?? 'root'),
            escapeshellarg($config['password'] ?? ''),
            escapeshellarg($config['database']),
            escapeshellarg($backupPath)
        );

        exec($command, $output, $returnCode);

        return 0 === $returnCode;
    }

    /**
     * Restore PostgreSQL database.
     */
    private function restorePostgreSql(array $config, string $backupPath): bool
    {
        $command = \sprintf(
            'PGPASSWORD=%s psql --host=%s --port=%s --username=%s --dbname=%s < %s',
            escapeshellarg($config['password'] ?? ''),
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 5432)),
            escapeshellarg($config['username'] ?? 'postgres'),
            escapeshellarg($config['database']),
            escapeshellarg($backupPath)
        );

        exec($command, $output, $returnCode);

        return 0 === $returnCode;
    }

    /**
     * Restore SQLite database.
     */
    private function restoreSqlite(array $config, string $backupPath): bool
    {
        $databasePath = $config['database'];

        // Create backup of current database
        if (file_exists($databasePath)) {
            copy($databasePath, $databasePath.'.before-restore');
        }

        return copy($backupPath, $databasePath);
    }
}
