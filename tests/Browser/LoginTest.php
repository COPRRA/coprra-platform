<?php

declare(strict_types=1);

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class LoginTest extends DuskTestCase
{
    public function testLoginPageLoadsAndHasForm(): void
    {
        $this->browse(static function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Login')
                ->assertPresent('input[name=email]')
                ->assertPresent('input[name=password]')
                ->assertPresent('button[type=submit]')
            ;
        });
    }
}
