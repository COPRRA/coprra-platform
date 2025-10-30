<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Minimal Laravel test to check if the TestCase works.
 *
 * @internal
 *
 * @coversNothing
 */
final class MinimalLaravelTest extends TestCase
{
    /**
     * Test that the Laravel application can be created.
     */
    public function testApplicationCanBeCreated(): void
    {
        self::assertNotNull($this->app);
    }

    /**
     * Test that basic Laravel features work.
     */
    public function testBasicLaravelFeatures(): void
    {
        self::assertTrue(true);
        self::assertSame('testing', $this->app->environment());
    }
}
