<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;

final class DbIntegrityCheck extends Command
{
    protected $signature = 'db:integrity';

    protected $description = 'Check database integrity and foreign keys for the current driver';

    private readonly DatabaseManager $database;

    private readonly ConfigRepository $config;

    public function __construct(DatabaseManager $database, ConfigRepository $config)
    {
        parent::__construct();
        $this->database = $database;
        $this->config = $config;
    }

    public function handle(): int
    {
        $driver = (string) $this->config->get('database.default', 'mysql');
        $this->info('🔍 Running DB integrity check (driver: '.$driver.')');

        if ('sqlite' === $driver) {
            return $this->checkSqlite();
        }

        if ('mysql' === $driver) {
            $this->line('  For MySQL, run: php artisan db:analyze');

            return Command::SUCCESS;
        }

        $this->warn('  Unsupported driver: '.$driver);

        return Command::SUCCESS;
    }

    private function checkSqlite(): int
    {
        try {
            $result = $this->database->select('PRAGMA integrity_check');
            $status = '';
            if (isset($result[0])) {
                $row = (array) $result[0];
                $status = (string) ($row['integrity_check'] ?? $row['integrity'] ?? '');
            }
            $this->line('  Integrity check: '.$status);
        } catch (\Throwable $exception) {
            $this->error('  Integrity check failed: '.$exception->getMessage());

            return Command::FAILURE;
        }

        try {
            $fkStatus = $this->database->select('PRAGMA foreign_keys');
            $fkEnabled = 0;
            if (isset($fkStatus[0])) {
                $row = (array) $fkStatus[0];
                $fkEnabled = (int) ($row['foreign_keys'] ?? 0);
            }
            $this->line('  Foreign keys: '.($fkEnabled ? 'ON' : 'OFF'));

            $fkIssues = $this->database->select('PRAGMA foreign_key_check');
            if (empty($fkIssues)) {
                $this->line('  Foreign key check: OK');
            } else {
                $this->warn('  Foreign key issues detected:');
                foreach ($fkIssues as $issue) {
                    $this->line('    - '.json_encode((array) $issue));
                }
            }
        } catch (\Throwable $exception) {
            $this->warn('  Foreign key checks not available: '.$exception->getMessage());
        }

        return Command::SUCCESS;
    }
}
