<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MigrationGenerator;
use App\Services\SqlMigrationParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress UnusedClass
 */
final class GenerateMigrationsFromFixSql extends Command
{
    protected $signature = 'generate:migrations-from-fix-sql {--sql=} {--reports=} {--dry-run}';

    protected $description = 'Parse reports/fix-suggestions.sql and generate Laravel migrations (indexes first, then column changes).';

    public function __construct(
        private readonly SqlMigrationParser $parser,
        private readonly MigrationGenerator $generator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $sqlPath = $this->getSqlPath();
        $reportsDir = $this->getReportsDir();
        $dryRun = (bool) $this->option('dry-run');

        $existing = $this->findExistingSqlFile($sqlPath);
        if (null === $existing) {
            return 1;
        }

        $this->info('Parsing: '.$existing);
        $sql = File::get($existing);

        // Parse operations using the service
        $indexOps = $this->parser->parseAddIndexStatements($sql);
        $modifyOps = $this->parser->parseModifyColumnStatements($sql);

        if (empty($indexOps) && empty($modifyOps)) {
            $this->warn('No ADD INDEX or MODIFY COLUMN statements found to convert.');

            return 0;
        }

        $this->displayParsingResults($indexOps);

        // Group operations by table
        $indexesByTable = $this->groupIndexOperationsByTable($indexOps);
        $modsByTable = $this->groupModifyOperationsByTable($modifyOps);

        // Build original type map from mismatch reports
        $originalTypes = $this->parser->buildOriginalTypesMap($reportsDir);

        // Generate migrations
        $this->generateIndexMigrations($indexesByTable, $dryRun);
        $this->generateColumnMigrations($modsByTable, $originalTypes, $dryRun);

        $this->info('Completed migration generation.');

        return 0;
    }

    private function getSqlPath(): string
    {
        $sqlOpt = $this->option('sql');

        return \is_string($sqlOpt) && '' !== $sqlOpt ? $sqlOpt : base_path('reports/fix-suggestions.sql');
    }

    private function getReportsDir(): string
    {
        $reportsOpt = $this->option('reports');

        return \is_string($reportsOpt) && '' !== $reportsOpt ? $reportsOpt : base_path('reports');
    }

    private function findExistingSqlFile(string $sqlPath): ?string
    {
        $candidates = [
            $sqlPath,
            base_path('downloaded-ci-test-results/reports/fix-suggestions.sql'),
            base_path('ci-test-results/reports/fix-suggestions.sql'),
        ];

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        $this->error('fix-suggestions.sql not found. Checked:');
        foreach ($candidates as $candidate) {
            $this->line(' - '.$candidate);
        }
        $this->line('Provide the file via --sql=path or place it under reports/.');

        return null;
    }

    private function displayParsingResults(array $indexOps): void
    {
        $this->line('Parsed index ops count: '.\count($indexOps));
        if (! empty($indexOps)) {
            $encoded = json_encode($indexOps[0]);
            $this->line('First index op sample: '.(false !== $encoded ? $encoded : ''));
        }
    }

    private function groupIndexOperationsByTable(array $indexOps): array
    {
        $indexesByTable = [];
        foreach ($indexOps as $op) {
            if (! isset($op['table'], $op['index'], $op['columns'])) {
                continue;
            }
            $indexesByTable[$op['table']][] = $op;
        }

        return $indexesByTable;
    }

    private function groupModifyOperationsByTable(array $modifyOps): array
    {
        $modsByTable = [];
        foreach ($modifyOps as $op) {
            $modsByTable[$op['table']][] = $op;
        }

        return $modsByTable;
    }

    private function generateIndexMigrations(array $indexesByTable, bool $dryRun): void
    {
        foreach ($indexesByTable as $table => $ops) {
            $name = 'add_indexes_to_'.$table.'_table_from_fix_sql';
            $this->generator->generateMigration($name, function (string $path) use ($table, $ops) {
                $content = $this->generator->renderIndexMigration($table, $ops);
                File::put($path, $content);
            }, $dryRun, $this);
        }
    }

    private function generateColumnMigrations(array $modsByTable, array $originalTypes, bool $dryRun): void
    {
        foreach ($modsByTable as $table => $ops) {
            $name = 'align_column_types_for_'.$table.'_table_from_fix_sql';
            $this->generator->generateMigration($name, function (string $path) use ($table, $ops, $originalTypes) {
                $content = $this->generator->renderModifyMigration($table, $ops, $originalTypes);
                File::put($path, $content);
            }, $dryRun, $this);
        }
    }
}
