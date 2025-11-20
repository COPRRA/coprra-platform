<?php

declare(strict_types=1);

namespace App\Contracts;

interface ValidationServiceContract
{
    /**
     * Validate data against rules.
     */
    public function validate(array $data, array $rules, array $messages = []): array;

    /**
     * Validate identifier format.
     */
    public function validateIdentifier(string $identifier, string $pattern): bool;

    /**
     * Validate price value.
     */
    public function validatePrice(float $price, float $min = 0.01, float $max = 999999.99): void;

    /**
     * Validate array structure.
     */
    public function validateArrayStructure(array $data, array $requiredKeys): bool;

    /**
     * Validate string length.
     */
    public function validateStringLength(string $value, int $min = 0, int $max = 255): bool;

    /**
     * Validate email format.
     */
    public function validateEmail(string $email): bool;

    /**
     * Validate URL format.
     */
    public function validateUrl(string $url): bool;
}
