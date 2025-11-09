<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class Phase1CoreFlowsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function products_index_returns_200(): void
    {
        $response = $this->get('/products');
        $response->assertStatus(200);
    }

    /** @test */
    public function categories_index_returns_200(): void
    {
        $response = $this->get('/categories');
        $response->assertStatus(200);
    }

    /** @test */
    public function brands_index_is_public_and_returns_200(): void
    {
        $response = $this->get('/brands');
        $response->assertStatus(200);
    }

    /** @test */
    public function login_page_returns_200(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /** @test */
    public function wishlist_redirects_to_login_when_unauthenticated(): void
    {
        $response = $this->get('/account/wishlist');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
