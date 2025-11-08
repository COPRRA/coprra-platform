<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WishlistFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase()
    {
        if (function_exists('putenv')) {
            putenv('CACHE_DRIVER=array');
            putenv('PERMISSION_CACHE_STORE=array');
        }

        if (function_exists('app') && app()->bound('config')) {
            app('config')->set([
                'cache.default' => 'array',
                'cache.stores.array' => ['driver' => 'array'],
                'permission.cache.store' => 'array',
            ]);
        }
    }

    public function test_user_can_add_product_to_wishlist(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.wishlist.store-with-product', ['product' => $product->id]));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);

        self::assertTrue(
            DB::table('wishlists')
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists(),
            'The product should be attached to the user wishlist.'
        );
    }

    public function test_user_can_remove_product_from_wishlist(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        DB::table('wishlists')->insert([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->deleteJson(route('api.wishlist.destroy', ['product' => $product->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 0,
            ]);

        self::assertFalse(
            DB::table('wishlists')
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists(),
            'The product should be detached from the user wishlist.'
        );
    }

    public function test_user_can_view_their_wishlist_page(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        DB::table('wishlists')->insert([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/account/wishlist');

        $response->assertStatus(200)
            ->assertSee($product->name);
    }

    public function test_guest_cannot_add_to_wishlist_and_is_prompted_to_log_in(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson("/api/wishlist/{$product->id}");

        $response->assertStatus(401);
    }
}
