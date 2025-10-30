<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = \Mockery::mock(ProductService::class);
        $this->app->instance(ProductService::class, $this->productService);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testDisplaysIndexPageWithProducts(): void
    {
        // Arrange
        $items = collect([Product::factory()->make()]);
        $products = new LengthAwarePaginator($items, 1, 15);
        $this->productService->shouldReceive('getPaginatedProducts')
            ->once()
            ->andReturn($products)
        ;

        // Act
        $response = $this->get(route('products.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('products.index');
        $response->assertViewHas('products', $products);
    }

    public function testDisplaysSearchResultsWhenSearchParametersPresent(): void
    {
        // Arrange
        $query = 'laptop';
        $filters = ['category' => 'electronics'];
        $items = collect([Product::factory()->make()]);
        $products = new LengthAwarePaginator($items, 1, 15);
        $this->productService->shouldReceive('searchProducts')
            ->with($query, \Mockery::on(static function ($arg) {
                return isset($arg['category_id']) && 'electronics' === $arg['category_id'];
            }))
            ->andReturn($products)
        ;

        // Act
        $response = $this->get(route('products.index', ['search' => $query, 'category' => 'electronics']));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('products.index');
        $response->assertViewHas('products', $products);
    }

    public function testDisplaysProductShowPage(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $relatedProducts = new Collection([Product::factory()->make()]);
        $this->productService->shouldReceive('getBySlug')
            ->with($product->slug)
            ->andReturn($product)
        ;
        $this->productService->shouldReceive('getRelatedProducts')
            ->with($product)
            ->andReturn($relatedProducts)
        ;

        // Act
        $response = $this->get(route('products.show', $product->slug));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('products.show');
        $response->assertViewHas('product', $product);
        $response->assertViewHas('relatedProducts', $relatedProducts);
    }

    public function testReturns404ForNonexistentProduct(): void
    {
        // Arrange
        $this->productService->shouldReceive('getBySlug')
            ->with('nonexistent')
            ->andReturn(null)
        ;

        // Act
        $response = $this->get(route('products.show', 'nonexistent'));

        // Assert
        $response->assertStatus(404);
    }

    public function testHandlesSearchWithSortAndOrder(): void
    {
        // Arrange
        $query = 'phone';
        $items = collect([Product::factory()->make()]);
        $products = new LengthAwarePaginator($items, 1, 15);
        $this->productService->shouldReceive('searchProducts')
            ->with($query, \Mockery::on(static function ($arg) {
                return isset($arg['sort_by']) && 'price_asc' === $arg['sort_by'];
            }))
            ->andReturn($products)
        ;

        // Act
        $response = $this->get(route('products.index', [
            'search' => $query,
            'sort' => 'price',
            'order' => 'asc',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('products', $products);
    }
}
