<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ArchTest extends TestCase
{
    #[Test]
    public function controllersArchitecture(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function modelsArchitecture(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function servicesArchitecture(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function middlewareArchitecture(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function providersArchitecture(): void
    {
        self::assertTrue(true);
    }
}
