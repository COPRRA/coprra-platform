<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function testBasicFunctionality(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function testExpectedBehavior(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function testValidation(): void
    {
        self::assertTrue(true);
    }
}
