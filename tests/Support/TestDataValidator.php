<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Assert;

/**
 * Test data validation helper to ensure test data quality and consistency.
 */
class TestDataValidator
{
    /**
     * Validate that a model instance has all required attributes.
     */
    public static function assertModelHasRequiredAttributes(Model $model, array $requiredAttributes): void
    {
        foreach ($requiredAttributes as $attribute) {
            Assert::assertNotNull(
                $model->getAttribute($attribute),
                \sprintf('Model %s is missing required attribute: %s', $model::class, $attribute)
            );
        }
    }

    /**
     * Validate that a model's attributes match expected types.
     */
    public static function assertModelAttributeTypes(Model $model, array $attributeTypes): void
    {
        foreach ($attributeTypes as $attribute => $expectedType) {
            $value = $model->getAttribute($attribute);

            if (null === $value) {
                continue; // Skip null values
            }

            $actualType = \gettype($value);

            Assert::assertTrue(
                self::isTypeMatch($actualType, $expectedType),
                \sprintf("Model %s attribute '%s' expected type '%s', got '%s'", $model::class, $attribute, $expectedType, $actualType)
            );
        }
    }

    /**
     * Validate that test data follows business rules.
     */
    public static function assertBusinessRules(Model $model, array $rules): void
    {
        $data = $model->toArray();
        $validator = Validator::make($data, $rules);

        Assert::assertTrue(
            $validator->passes(),
            \sprintf('Model %s violates business rules: %s', $model::class, implode(', ', $validator->errors()->all()))
        );
    }

    /**
     * Validate that sensitive data is properly handled in tests.
     */
    public static function assertNoSensitiveDataExposed(Model $model, array $sensitiveFields = ['password', 'api_key', 'secret', 'token']): void
    {
        $data = $model->toArray();

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                Assert::assertNotEquals(
                    'password',
                    $data[$field],
                    \sprintf("Model %s contains unhashed password in field '%s'", $model::class, $field)
                );

                Assert::assertNotEquals(
                    'secret',
                    $data[$field],
                    \sprintf("Model %s contains plain text secret in field '%s'", $model::class, $field)
                );
            }
        }
    }

    /**
     * Validate that factory-generated data is realistic.
     */
    public static function assertRealisticTestData(Model $model): void
    {
        $data = $model->toArray();

        // Check email format if present
        if (isset($data['email'])) {
            Assert::assertTrue(
                false !== filter_var($data['email'], \FILTER_VALIDATE_EMAIL),
                \sprintf('Model %s has invalid email format: %s', $model::class, $data['email'])
            );
        }

        // Check price values are positive if present
        if (isset($data['price'])) {
            Assert::assertGreaterThanOrEqual(
                0,
                $data['price'],
                \sprintf('Model %s has negative price: %s', $model::class, $data['price'])
            );
        }

        // Check quantity values are non-negative if present
        if (isset($data['quantity']) || isset($data['stock_quantity'])) {
            $quantity = $data['quantity'] ?? $data['stock_quantity'];
            Assert::assertGreaterThanOrEqual(
                0,
                $quantity,
                \sprintf('Model %s has negative quantity: %s', $model::class, $quantity)
            );
        }
    }

    /**
     * Validate that test data relationships are properly set up.
     */
    public static function assertValidRelationships(Model $model, array $relationships): void
    {
        foreach ($relationships as $relationship => $expectedType) {
            $relation = $model->{$relationship}();

            Assert::assertInstanceOf(
                $expectedType,
                $relation,
                \sprintf("Model %s relationship '%s' should be of type %s", $model::class, $relationship, $expectedType)
            );
        }
    }

    /**
     * Validate that test data doesn't contain common security issues.
     */
    public static function assertSecureTestData(array $data): void
    {
        foreach ($data as $key => $value) {
            if (\is_string($value)) {
                // Check for SQL injection patterns
                Assert::assertStringNotContainsString(
                    "'; DROP TABLE",
                    strtoupper($value),
                    "Test data contains potential SQL injection pattern in field '{$key}'"
                );

                // Check for XSS patterns
                Assert::assertStringNotContainsString(
                    '<script>',
                    strtolower($value),
                    "Test data contains potential XSS pattern in field '{$key}'"
                );

                // Check for path traversal patterns
                Assert::assertStringNotContainsString(
                    '../',
                    $value,
                    "Test data contains potential path traversal pattern in field '{$key}'"
                );
            }
        }
    }

    /**
     * Validate that test data is properly isolated between tests.
     */
    public static function assertDataIsolation(): void
    {
        // This would be called in test setup/teardown to ensure no data leakage
        $tables = ['users', 'products', 'orders', 'cart_items', 'user_purchases'];

        foreach ($tables as $table) {
            $count = \DB::table($table)->count();
            Assert::assertEquals(
                0,
                $count,
                "Table '{$table}' should be empty at start of test for proper isolation"
            );
        }
    }

    /**
     * Check if actual type matches expected type.
     */
    private static function isTypeMatch(string $actualType, string $expectedType): bool
    {
        $typeMap = [
            'int' => ['integer'],
            'float' => ['double'],
            'bool' => ['boolean'],
            'str' => ['string'],
            'array' => ['array'],
            'object' => ['object'],
        ];

        $expectedTypes = $typeMap[$expectedType] ?? [$expectedType];

        return \in_array($actualType, $expectedTypes, true);
    }
}
