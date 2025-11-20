<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Service responsible for parsing SQL statements and extracting migration operations.
 */
final class SqlMigrationParser
{
    /**
     * Parse ADD INDEX statements from SQL content.
     *
     * @return array<int, array{table:string,index:string,columns:array<int,string>}>
     */
    public function parseAddIndexStatements(string $sql): array
    {
        $pattern = '/ALTER\s+TABLE\s+`(?<table>[^`]+)`\s+ADD\s+INDEX\s+`(?<index>[^`]+)`\s*\((?<columns>[^\)]+)\)\s*;/i';
        $matches = [];
        preg_match_all($pattern, $sql, $matches, \PREG_SET_ORDER);

        $ops = [];
        foreach ($matches as $m) {
            $colsRaw = $m['columns'];
            $cols = [];
            foreach (explode(',', $colsRaw) as $c) {
                $c = trim($c);
                $c = trim($c, '` ');
                if ('' !== $c) {
                    $cols[] = $c;
                }
            }
            $ops[] = [
                'table' => $m['table'],
                'index' => $m['index'],
                'columns' => $cols,
            ];
        }

        return $ops;
    }

    /**
     * Parse MODIFY COLUMN statements from SQL content.
     *
     * @return array<int, array{table:string,column:string,definition:string,charset:?string,collate:?string}>
     */
    public function parseModifyColumnStatements(string $sql): array
    {
        $pattern = '/ALTER\s+TABLE\s+`(?<table>[^`]+)`\s+MODIFY\s+`(?<column>[^`]+)`\s+(?<definition>[^;]+?)(?:\s*;|$)/i';
        $matches = [];
        preg_match_all($pattern, $sql, $matches, \PREG_SET_ORDER);

        $ops = [];
        foreach ($matches as $m) {
            $def = trim($m['definition']);
            $charset = null;
            $collate = null;

            if (preg_match('/character\s+set\s+(?<cs>\w+)/i', $def, $cm)) {
                $charset = $cm['cs'];
            }
            if (preg_match('/collate\s+(?<co>\w+)/i', $def, $clm)) {
                $collate = $clm['co'];
            }

            $ops[] = [
                'table' => $m['table'],
                'column' => $m['column'],
                'definition' => $def,
                'charset' => $charset,
                'collate' => $collate,
            ];
        }

        return $ops;
    }

    /**
     * Build a map of original column types from reports for down() migrations.
     * Format: [table][column] => originalDefinition.
     *
     * @return array<string, array<string, string>>
     */
    public function buildOriginalTypesMap(string $reportsDir): array
    {
        $map = [];
        $candidates = [
            $reportsDir.'/mysql-fk-type-mismatches.txt',
            $reportsDir.'/mysql-fk-unsigned-mismatches.txt',
        ];

        foreach ($candidates as $path) {
            if (! File::exists($path)) {
                continue;
            }

            $lines = preg_split("/\r?\n/", File::get($path)) ?: [];
            foreach ($lines as $line) {
                $this->parseReportLine($line, $map);
            }
        }

        return $map;
    }

    /**
     * Parse a single report line and extract table/column/type information.
     *
     * @param array<string, array<string, string>> $map
     */
    private function parseReportLine(string $line, array &$map): void
    {
        // Expect tab or pipe-delimited entries; try to detect child table/column/type
        // Heuristic: child_table, child_column, child_coltype present in line
        if (
            preg_match('/child_table\s*[:=]\s*(?<ct>[\w`]+)/i', $line, $mCt)
            && preg_match('/child_column\s*[:=]\s*(?<cc>[\w`]+)/i', $line, $mCc)
            && preg_match('/child_coltype\s*[:=]\s*(?<cty>[^,|]+)$/i', $line, $mTy)
        ) {
            $table = trim(str_replace('`', '', $mCt['ct']));
            $col = trim(str_replace('`', '', $mCc['cc']));
            $type = trim($mTy['cty']);
            $map[$table][$col] = $type;

            return;
        }

        // Generic CSV-like format: table,column,child_type,parent_type
        $parts = array_map('trim', preg_split('/\s*[\t|,]\s*/', $line) ?: []);
        if (\count($parts) >= 3) {
            $table = trim(str_replace('`', '', $parts[0]));
            $col = trim(str_replace('`', '', $parts[1]));
            $type = $parts[2];
            if ('' !== $table && '' !== $col && '' !== $type) {
                $map[$table][$col] = $type;
            }
        }
    }
}
