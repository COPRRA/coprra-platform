<?php

declare(strict_types=1);

namespace Tests\AI;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SmokeTest extends TestCase
{
    #[Test]
    public function itRuns(): void
    {
        self::assertTrue(true);
    }
}
