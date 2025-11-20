<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class DimensionSum implements ValidationRule
{
    private readonly int $maxSum;

    public function __construct(int $maxSum = 2000)
    {
        $this->maxSum = $maxSum;
    }

    #[\Override]
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! \is_array($value) || 3 !== \count($value)) {
            $fail('The :attribute must be an array with exactly 3 dimensions.');

            return;
        }

        // Support both indexed and associative arrays (length, width, height)
        $values = $value;
        if (! array_is_list($value)) {
            $values = [
                $value['length'] ?? null,
                $value['width'] ?? null,
                $value['height'] ?? null,
            ];
        }

        // Ensure all values are numeric
        foreach ($values as $val) {
            if (! is_numeric($val)) {
                $fail('All dimension values must be numeric.');

                return;
            }
        }

        $total = array_sum(array_map(static fn ($val): float => (float) $val, $values));

        if ($total > $this->maxSum) {
            $fail("The sum of dimensions cannot exceed {$this->maxSum} cm.");
        }
    }
}
