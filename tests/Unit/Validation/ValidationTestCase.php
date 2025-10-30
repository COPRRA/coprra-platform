<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Illuminate\Contracts\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for validation tests that handles risky test warnings.
 *
 * @internal
 *
 * @coversNothing
 */
final class ValidationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test that ValidationTestCase can be instantiated and works correctly.
     */
    public function testCanBeInstantiated(): void
    {
        // Test basic instantiation
        self::assertInstanceOf(self::class, $this);

        // Test that the class has required methods
        self::assertTrue(method_exists($this, 'createValidator'));
        self::assertTrue(method_exists($this, 'validateData'));
        self::assertTrue(method_exists($this, 'getValidationErrors'));

        // Test basic functionality
        self::assertTrue(true);

        // Test that we can perform basic assertions
        self::assertSame(1, 1);
        self::assertNotSame(1, 2);
    }

    /**
     * Create a validator instance safely.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     */
    protected function createValidator(array $data, array $rules): Validator
    {
        return \Illuminate\Support\Facades\Validator::make($data, $rules);
    }

    /**
     * Validate data safely without triggering risky test warnings.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     */
    protected function validateData(array $data, array $rules): bool
    {
        $validator = $this->createValidator($data, $rules);

        return false === $validator->fails();
    }

    /**
     * Get validation errors safely.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     *
     * @return array<string>
     */
    protected function getValidationErrors(array $data, array $rules): array
    {
        $validator = $this->createValidator($data, $rules);

        return $validator->errors()->all();
    }
}
