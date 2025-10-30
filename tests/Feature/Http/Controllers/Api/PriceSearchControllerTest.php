<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class PriceSearchControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $store = Store::factory()->create();

        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        PriceOffer::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'price' => 100.00,
            'is_available' => true,
        ]);
    }

    public function testCanGetBestOfferByProductId()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/best-offer', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'price',
                    'store_id',
                    'store',
                    'store_url',
                    'is_available',
                    'total_offers',
                    'offers' => [
                        '*' => [
                            'id',
                            'price',
                            'store_id',
                            'store',
                            'store_url',
                            'is_available',
                        ],
                    ],
                ],
            ])
        ;
    }

    public function testCanGetBestOfferByProductName()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/best-offer', [
            'product_name' => $product->name,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'price',
                    'store_id',
                    'store',
                    'store_url',
                    'is_available',
                    'total_offers',
                ],
            ])
        ;
    }

    public function testReturns404ForNonexistentProductById()
    {
        $response = $this->getJson('/api/price-search/best-offer', [
            'product_id' => 99999,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Product not found',
            ])
        ;
    }

    public function testReturns404ForNonexistentProductByName()
    {
        $response = $this->getJson('/api/price-search/best-offer', [
            'product_name' => 'Nonexistent Product',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Product not found',
            ])
        ;
    }

    public function testReturns404WhenNoOffersAvailable()
    {
        $product = Product::factory()->create(['is_active' => true]);

        // Don't create any price offers for this product

        $response = $this->getJson('/api/price-search/best-offer', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No offers available for this product',
            ])
        ;
    }

    public function testReturnsAllProductsWhenNoParametersProvided()
    {
        $response = $this->getJson('/api/price-search/best-offer');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'product_id',
                        'name',
                        'price',
                        'store',
                        'is_available',
                    ],
                ],
            ])
        ;
    }

    public function testReturns404WhenNoProductsExist()
    {
        // Delete all products
        Product::query()->delete();

        $response = $this->getJson('/api/price-search/best-offer');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No products available',
            ])
        ;
    }

    public function testCanGetSupportedStores()
    {
        $response = $this->getJson('/api/price-search/supported-stores');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'is_active',
                ],
            ])
        ;
    }

    public function testCanSearchProducts()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/search', [
            'q' => $product->name,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testCanSearchProductsWithQueryParameter()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/search', [
            'query' => $product->name,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testCanSearchProductsWithNameParameter()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/search', [
            'name' => $product->name,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testReturns400WhenSearchQueryIsEmpty()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ])
        ;
    }

    public function testReturns400WhenSearchQueryIsMissing()
    {
        $response = $this->getJson('/api/price-search/search');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ])
        ;
    }

    public function testHandlesSearchWithSpecialCharacters()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'test@#$%^&*()',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithSqlInjectionAttempt()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => "'; DROP TABLE products; --",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithXssAttempt()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '<script>alert("xss")</script>',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithVeryLongQuery()
    {
        $longQuery = str_repeat('a', 1000);

        $response = $this->getJson('/api/price-search/search', [
            'q' => $longQuery,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithUnicodeCharacters()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'æµ‹è¯•äº§å“ ðŸ›ï¸',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithMultipleWords()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'test product search',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithNumbers()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '12345',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithMixedContent()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'Product 123 @#$% test',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithEmptyResults()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'nonexistentproductname',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;

        $data = $response->json('data');
        self::assertIsArray($data);
        self::assertEmpty($data);
    }

    public function testHandlesSearchWithWhitespaceOnly()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '   ',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ])
        ;
    }

    public function testHandlesSearchWithTabsAndNewlines()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => "\t\n\r",
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ])
        ;
    }

    public function testHandlesSearchWithNullParameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => null,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ])
        ;
    }

    public function testHandlesSearchWithArrayParameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => ['test', 'product'],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ])
        ;
    }

    public function testHandlesSearchWithObjectParameter()
    {
        $response = $this->json('GET', '/api/price-search/search', [
            'q' => ['test' => 'product'], // Pass as array (will be treated as object/invalid)
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ])
        ;
    }

    public function testHandlesSearchWithBooleanParameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => true,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ])
        ;
    }

    public function testHandlesSearchWithNumericParameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 123,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ])
        ;
    }

    public function testHandlesSearchWithFloatParameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 123.45,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
}
