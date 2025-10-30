<?php

declare(strict_types=1);

namespace Tests\Unit\DataAccuracy;

use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DataValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function testIntegerValidation(): void
    {
        $data = ['value' => '123'];
        $rules = ['value' => 'integer'];
        $validator = Validator::make($data, $rules);
        self::assertTrue($validator->passes());

        $data = ['value' => 'abc'];
        $validator = Validator::make($data, $rules);
        self::assertFalse($validator->passes());
    }

    #[Test]
    public function testDateValidation(): void
    {
        $data = ['date' => '2023-12-25'];
        $rules = ['date' => 'date'];
        $validator = Validator::make($data, $rules);
        self::assertTrue($validator->passes());

        $data = ['date' => 'invalid-date'];
        $validator = Validator::make($data, $rules);
        self::assertFalse($validator->passes());
    }

    #[Test]
    public function testUrlValidation(): void
    {
        $data = ['url' => 'https://example.com'];
        $rules = ['url' => 'url'];
        $validator = Validator::make($data, $rules);
        self::assertTrue($validator->passes());

        $data = ['url' => 'not-a-url'];
        $validator = Validator::make($data, $rules);
        self::assertFalse($validator->passes());
    }
}
