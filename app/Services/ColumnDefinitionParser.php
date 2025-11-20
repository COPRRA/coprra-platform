<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Service responsible for parsing MySQL column definitions into Laravel Schema Builder calls.
 */
final class ColumnDefinitionParser
{
    /**
     * Parse a MySQL column definition into a Laravel Schema Builder call where possible.
     *
     * @return array{schema: ?string}
     */
    public function parseColumnDefinition(string $def): array
    {
        $def = trim($def);

        // Extract type(length[,scale]) and flags
        if (! preg_match('/^(?<type>\w+)(?:\((?<len>[\d,]+)\))?(?<rest>.*)$/i', $def, $m)) {
            return ['schema' => null];
        }

        $type = strtolower($m['type']);
        $len = '' !== $m['len'] ? $m['len'] : '';
        $rest = strtolower($m['rest']);

        $unsigned = Str::contains($rest, 'unsigned');
        $nullable = Str::contains($rest, 'null') && ! Str::contains($rest, 'not null');
        $default = $this->extractDefault($rest);

        $generated = $this->generateSchemaCall($type, $len, $unsigned);

        if (null === $generated) {
            return ['schema' => null];
        }

        return ['schema' => $this->applyModifiers($generated, $nullable, $default)];
    }

    /**
     * Extract default value from column definition.
     */
    private function extractDefault(string $rest): ?string
    {
        if (preg_match('/default\s+([^\s]+)/i', $rest, $dm)) {
            return $dm[1];
        }

        return null;
    }

    /**
     * Generate the base schema call for a given column type.
     */
    private function generateSchemaCall(string $type, string $len, bool $unsigned): ?string
    {
        $colPlaceholder = '__COL__';

        return match ($type) {
            'bigint' => $unsigned
                ? "\$table->unsignedBigInteger('{$colPlaceholder}')->change();"
                : "\$table->bigInteger('{$colPlaceholder}')->change();",
            'int', 'integer' => $unsigned
                ? "\$table->unsignedInteger('{$colPlaceholder}')->change();"
                : "\$table->integer('{$colPlaceholder}')->change();",
            'smallint' => $unsigned
                ? "\$table->unsignedSmallInteger('{$colPlaceholder}')->change();"
                : "\$table->smallInteger('{$colPlaceholder}')->change();",
            'mediumint' => $unsigned
                ? null // Laravel has no unsigned mediumint
                : "\$table->mediumInteger('{$colPlaceholder}')->change();",
            'tinyint' => $unsigned
                ? "\$table->unsignedTinyInteger('{$colPlaceholder}')->change();"
                : "\$table->tinyInteger('{$colPlaceholder}')->change();",
            'varchar' => $this->generateStringCall($colPlaceholder, $len, 'string'),
            'char' => $this->generateStringCall($colPlaceholder, $len, 'char'),
            'text' => "\$table->text('{$colPlaceholder}')->change();",
            'mediumtext' => "\$table->mediumText('{$colPlaceholder}')->change();",
            'longtext' => "\$table->longText('{$colPlaceholder}')->change();",
            'decimal' => $this->generateDecimalCall($colPlaceholder, $len),
            'datetime' => "\$table->dateTime('{$colPlaceholder}')->change();",
            'timestamp' => "\$table->timestamp('{$colPlaceholder}')->change();",
            'date' => "\$table->date('{$colPlaceholder}')->change();",
            default => null,
        };
    }

    /**
     * Generate string/char column call with length.
     */
    private function generateStringCall(string $colPlaceholder, string $len, string $method): string
    {
        $length = (int) ('' !== $len ? explode(',', $len)[0] : 255);

        return "\$table->{$method}('{$colPlaceholder}', {$length})->change();";
    }

    /**
     * Generate decimal column call with precision and scale.
     */
    private function generateDecimalCall(string $colPlaceholder, string $len): string
    {
        $parts = '' !== $len ? explode(',', $len) : [10, 0];
        $precision = (int) $parts[0];
        $scale = (int) ($parts[1] ?? 0);

        // Laravel decimal() has no unsigned variant
        return "\$table->decimal('{$colPlaceholder}', {$precision}, {$scale})->change();";
    }

    /**
     * Apply nullable and default modifiers to the schema call.
     */
    private function applyModifiers(string $call, bool $nullable, ?string $default): string
    {
        if ($nullable) {
            $call = str_replace(')->change();', ')->nullable()->change();', $call);
        }

        if (null !== $default) {
            // naive default mapping; quotes preserved if present
            $call = str_replace(')->change();', ")->default({$default})->change();", $call);
        }

        return $call;
    }
}
