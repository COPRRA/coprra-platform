<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testCanListProducts()
    {
        $products = Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'description',
                        'created_at',
                        'updated_at',
                        'category',
                        'brand',
                        'stores',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment([
                'id' => $products->first()->id,
                'name' => $products->first()->name,
            ])
        ;
    }

    public function testCanShowSpecificProduct()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'description',
                    'created_at',
                    'updated_at',
                    'category',
                    'brand',
                    'stores',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                ],
            ])
        ;
    }

    public function testReturns404ForNonexistentProduct()
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404);
    }

    public function testCanSearchProducts()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $response = $this->getJson('/api/products', [
            'search' => 'Test',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                    ],
                ],
            ])
        ;
    }

    public function testCanFilterProductsByCategory()
    {
        $product = Product::factory()->create();

        $response = $this->getJson('/api/products', [
            'category_id' => $product->category_id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                    ],
                ],
            ])
        ;
    }

    public function testCanSortProducts()
    {
        $response = $this->getJson('/api/products', [
            'sort' => 'price_asc',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                    ],
                ],
            ])
        ;
    }

    public function testCanPaginateProducts()
    {
        Product::factory()->count(15)->create();

        $response = $this->getJson('/api/products', [
            'per_page' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ])
        ;
    }

    public function testHandlesInvalidPaginationParameters()
    {
        $response = $this->getJson('/api/products?per_page=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page'])
        ;
    }

    public function testHandlesInvalidSortParameters()
    {
        $response = $this->getJson('/api/products?sort=invalid_sort');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }
}
