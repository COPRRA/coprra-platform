<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Application;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CreatesApplicationTest extends TestCase
{
    public function testApplicationCanBeCreated(): void
    {
        self::assertNotNull($this->app);
        self::assertInstanceOf(Application::class, $this->app);
    }
}
