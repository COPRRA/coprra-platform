<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itCanDisplayHomePage(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertSee('Welcome');
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itDisplaysFeaturedProductsOnHomePage(): void
    {
        // Create featured products
        $featuredProducts = Product::factory()->count(3)->create([
            'is_featured' => true,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('featuredProducts');

        $viewData = $response->viewData('featuredProducts');
        self::assertCount(3, $viewData);

        foreach ($featuredProducts as $product) {
            $response->assertSee($product->name);
        }
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itDisplaysLatestProductsOnHomePage(): void
    {
        // Create latest products
        $latestProducts = Product::factory()->count(5)->create([
            'is_active' => true,
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('latestProducts');

        $viewData = $response->viewData('latestProducts');
        self::assertCount(5, $viewData);

        foreach ($latestProducts as $product) {
            $response->assertSee($product->name);
        }
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itHandlesHomePageWithNoProducts(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('featuredProducts');
        $response->assertViewHas('latestProducts');

        $featuredProducts = $response->viewData('featuredProducts');
        $latestProducts = $response->viewData('latestProducts');

        self::assertEmpty($featuredProducts);
        self::assertEmpty($latestProducts);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itDisplaysHomePageForAuthenticatedUser(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertSee($user->name);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function itDisplaysHomePageForGuestUser(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertSee('Login');
        $response->assertSee('Register');
    }
}
