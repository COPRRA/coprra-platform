<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SimpleLaravelTest extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function testBasicLaravelBootstrap()
    {
        self::assertTrue(true);
    }

    public function testAppInstance()
    {
        self::assertInstanceOf(Application::class, $this->app);
    }

    public function testConfigAccess()
    {
        self::assertSame('testing', config('app.env'));
    }
}
