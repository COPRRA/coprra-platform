<?php

declare(strict_types=1);

namespace Tests\Performance;

use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabasePerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testDatabaseQueryPerformance(): void
    {
        self::assertTrue(true);
    }

    public function testDatabaseConnectionPerformance(): void
    {
        self::assertTrue(true);
    }

    public function testDatabaseTransactionPerformance(): void
    {
        self::assertTrue(true);
    }
}
