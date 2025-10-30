<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SimpleTest extends TestCase
{
    #[Test]
    public function itWorks(): void
    {
        self::assertTrue(true);
    }
}
