<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_product_to_compare(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson(route('api.compare.store', $product));

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'added' => true,
                'count' => 1,
                'items' => [$product->id],
            ]);

        $this->assertSame([$product->id], session('compare', []));
    }

    public function test_cannot_add_more_than_four_products(): void
    {
        $products = Product::factory()->count(5)->create();

        foreach ($products->take(4) as $index => $product) {
            $this->postJson(route('api.compare.store', $product))
                ->assertCreated()
                ->assertJson([
                    'success' => true,
                    'added' => true,
                    'count' => $index + 1,
                ]);
        }

        $this->postJson(route('api.compare.store', $products->get(4)))
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertCount(4, session('compare', []));
    }

    public function test_can_remove_product_from_compare(): void
    {
        $product = Product::factory()->create();

        $this->postJson(route('api.compare.store', $product))
            ->assertCreated();

        $this->deleteJson(route('api.compare.destroy', $product))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 0,
            ]);

        $this->assertSame([], session('compare', []));
    }
}
