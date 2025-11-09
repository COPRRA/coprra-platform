<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FrameworkBootTest extends TestCase
{
    public function test_application_boots_and_wishlist_routes_exist(): void
    {
        $this->assertNotEmpty(Route::getRoutes());
        $this->assertTrue(Route::has('api.wishlist.index'), 'Expected wishlist API routes to be registered.');
        $this->assertTrue(Route::has('account.wishlist'), 'Expected wishlist web route to be registered.');
    }
}
