<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SimpleUserTest extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Test basic functionality without database.
     */
    public function testBasicFunctionality(): void
    {
        self::assertTrue(true);
        self::assertSame(2, 1 + 1);
        self::assertNotEmpty('Hello World');
    }

    /**
     * Test Laravel app is working.
     */
    public function testLaravelAppWorks(): void
    {
        self::assertNotNull(app());
        self::assertSame('testing', app()->environment());
    }

    /**
     * Test config is accessible.
     */
    public function testConfigAccessible(): void
    {
        self::assertNotNull(config('app.name'));
        self::assertSame('testing', config('app.env'));
    }
}
