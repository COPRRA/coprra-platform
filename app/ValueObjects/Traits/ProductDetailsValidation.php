<?php

declare(strict_types=1);

namespace App\ValueObjects\Traits;

trait ProductDetailsValidation
{
    private function validateSku(string $sku): void
    {
        if (empty(trim($sku))) {
            throw new \InvalidArgumentException('SKU cannot be empty');
        }

        if (\strlen($sku) > 50) {
            throw new \InvalidArgumentException('SKU cannot exceed 50 characters');
        }

        if (! preg_match('/^[A-Z0-9\-_]+$/i', $sku)) {
            throw new \InvalidArgumentException('SKU can only contain letters, numbers, hyphens, and underscores');
        }
    }

    private function validateDimensions(array $dimensions): void
    {
        if (empty($dimensions)) {
            return;
        }

        $requiredKeys = ['length', 'width', 'height'];
        foreach ($requiredKeys as $key) {
            if (! isset($dimensions[$key]) || ! is_numeric($dimensions[$key]) || $dimensions[$key] < 0) {
                throw new \InvalidArgumentException("Dimension '{$key}' must be a non-negative number");
            }
        }

        $unit = $dimensions['unit'] ?? 'cm';
        $allowedUnits = ['mm', 'cm', 'm', 'in', 'ft'];
        if (! \in_array($unit, $allowedUnits, true)) {
            throw new \InvalidArgumentException('Dimension unit must be one of: '.implode(', ', $allowedUnits));
        }
    }

    private function validateWeight(float $weight): void
    {
        if ($weight < 0) {
            throw new \InvalidArgumentException('Weight cannot be negative');
        }
    }

    private function validateWeightUnit(string $weightUnit): void
    {
        $allowedUnits = ['g', 'kg', 'lb', 'oz'];
        if (! \in_array($weightUnit, $allowedUnits, true)) {
            throw new \InvalidArgumentException('Weight unit must be one of: '.implode(', ', $allowedUnits));
        }
    }

    private function validateSpecifications(array $specifications): void
    {
        foreach ($specifications as $key => $value) {
            if (! \is_string($key) || empty(trim($key))) {
                throw new \InvalidArgumentException('Specification keys must be non-empty strings');
            }
        }
    }

    private function validateFeatures(array $features): void
    {
        foreach ($features as $feature) {
            if (! \is_string($feature) || empty(trim($feature))) {
                throw new \InvalidArgumentException('Features must be non-empty strings');
            }
        }
    }
}
