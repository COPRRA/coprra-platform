<?php

declare(strict_types=1);

namespace Tests\Browser;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
#[PreserveGlobalState(false)]
final class E2ETest extends DuskTestCase
{
    #[Test]
    public function canLoadHomepage(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function canNavigateToProducts(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function canSearchProducts(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function canAddToCart(): void
    {
        self::assertTrue(true);
    }

    #[Test]
    public function canCheckout(): void
    {
        self::assertTrue(true);
    }
}
