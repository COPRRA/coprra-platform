<?php

declare(strict_types=1);

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ExampleTest extends DuskTestCase
{
    #[Test]
    public function exampleBrowserTest(): void
    {
        self::assertTrue(true);
    }
}
