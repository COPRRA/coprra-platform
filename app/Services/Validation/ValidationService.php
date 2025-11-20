<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Contracts\ValidationServiceContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidationService implements ValidationServiceContract
{
    /**
     * Validate data against rules.
     */
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate identifier format (common pattern across store adapters).
     */
    public function validateIdentifier(string $identifier, string $pattern): bool
    {
        return (bool) preg_match($pattern, $identifier);
    }

    /**
     * Validate price value.
     */
    public function validatePrice(float $price, float $min = 0.01, float $max = 999999.99): void
    {
        if ($price < $min || $price > $max) {
            throw new \InvalidArgumentException("Price must be between {$min} and {$max}");
        }
    }

    /**
     * Validate array structure.
     */
    public function validateArrayStructure(array $data, array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (! \array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate string length.
     */
    public function validateStringLength(string $value, int $min = 0, int $max = 255): bool
    {
        $length = \strlen($value);

        return $length >= $min && $length <= $max;
    }

    /**
     * Validate email format.
     */
    public function validateEmail(string $email): bool
    {
        return false !== filter_var($email, \FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate URL format.
     */
    public function validateUrl(string $url): bool
    {
        return false !== filter_var($url, \FILTER_VALIDATE_URL);
    }
}
