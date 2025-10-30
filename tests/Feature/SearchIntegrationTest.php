<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SearchIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = app(ProductService::class);
        Cache::flush();
    }

    public function testBasicProductSearch(): void
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Electronics']);
        $brand = Brand::factory()->create(['name' => 'Samsung']);

        $product1 = Product::factory()->create([
            'name' => 'Samsung Galaxy Phone',
            'description' => 'Latest smartphone technology',
            'price' => 999.99,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'iPhone Pro',
            'description' => 'Apple smartphone',
            'price' => 1199.99,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Laptop Computer',
            'description' => 'Gaming laptop',
            'price' => 1499.99,
            'is_active' => false, // Inactive product
        ]);

        // Test basic search functionality
        $results = $this->productService->searchProducts('Samsung');

        self::assertInstanceOf(LengthAwarePaginator::class, $results);
        self::assertSame(1, $results->total());
        self::assertSame($product1->id, $results->items()[0]->id);
        self::assertSame('Samsung Galaxy Phone', $results->items()[0]->name);

        // Verify relationships are loaded
        self::assertTrue($results->items()[0]->relationLoaded('category'));
        self::assertTrue($results->items()[0]->relationLoaded('brand'));
        self::assertSame('Electronics', $results->items()[0]->category->name);
        self::assertSame('Samsung', $results->items()[0]->brand->name);
    }

    public function testSearchWithDescriptionMatching(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'This product has smartphone features',
            'is_active' => true,
        ]);

        $results = $this->productService->searchProducts('smartphone');

        self::assertSame(1, $results->total());
        self::assertSame($product->id, $results->items()[0]->id);
    }

    public function testSearchWithFilters(): void
    {
        $category1 = Category::factory()->create(['name' => 'Electronics']);
        $category2 = Category::factory()->create(['name' => 'Books']);
        $brand1 = Brand::factory()->create(['name' => 'Samsung']);
        $brand2 = Brand::factory()->create(['name' => 'Apple']);

        $product1 = Product::factory()->create([
            'name' => 'Samsung Phone',
            'price' => 500.00,
            'category_id' => $category1->id,
            'brand_id' => $brand1->id,
            'is_active' => true,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Apple Phone',
            'price' => 800.00,
            'category_id' => $category1->id,
            'brand_id' => $brand2->id,
            'is_active' => true,
        ]);

        $product3 = Product::factory()->create([
            'name' => 'Programming Book',
            'price' => 50.00,
            'category_id' => $category2->id,
            'is_active' => true,
        ]);

        // Test category filter
        $results = $this->productService->searchProducts('', ['category_id' => $category1->id]);
        self::assertSame(2, $results->total());

        // Test brand filter
        $results = $this->productService->searchProducts('', ['brand_id' => $brand1->id]);
        self::assertSame(1, $results->total());
        self::assertSame($product1->id, $results->items()[0]->id);

        // Test price range filter
        $results = $this->productService->searchProducts('', [
            'min_price' => 100.00,
            'max_price' => 600.00,
        ]);
        self::assertSame(1, $results->total());
        self::assertSame($product1->id, $results->items()[0]->id);

        // Test combined filters
        $results = $this->productService->searchProducts('Phone', [
            'category_id' => $category1->id,
            'min_price' => 700.00,
        ]);
        self::assertSame(1, $results->total());
        self::assertSame($product2->id, $results->items()[0]->id);
    }

    public function testSearchSorting(): void
    {
        $category = Category::factory()->create();

        $product1 = Product::factory()->create([
            'name' => 'Alpha Product',
            'price' => 300.00,
            'category_id' => $category->id,
            'is_active' => true,
            'created_at' => now()->subDays(2),
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Beta Product',
            'price' => 100.00,
            'category_id' => $category->id,
            'is_active' => true,
            'created_at' => now()->subDay(),
        ]);

        $product3 = Product::factory()->create([
            'name' => 'Gamma Product',
            'price' => 200.00,
            'category_id' => $category->id,
            'is_active' => true,
            'created_at' => now(),
        ]);

        // Test price ascending sort
        $results = $this->productService->searchProducts('Product', ['sort_by' => 'price_asc']);
        $prices = collect($results->items())->pluck('price')->toArray();
        self::assertSame([100.00, 200.00, 300.00], $prices);

        // Test price descending sort
        $results = $this->productService->searchProducts('Product', ['sort_by' => 'price_desc']);
        $prices = collect($results->items())->pluck('price')->toArray();
        self::assertSame([300.00, 200.00, 100.00], $prices);

        // Test name ascending sort
        $results = $this->productService->searchProducts('Product', ['sort_by' => 'name_asc']);
        $names = collect($results->items())->pluck('name')->toArray();
        self::assertSame(['Alpha Product', 'Beta Product', 'Gamma Product'], $names);

        // Test name descending sort
        $results = $this->productService->searchProducts('Product', ['sort_by' => 'name_desc']);
        $names = collect($results->items())->pluck('name')->toArray();
        self::assertSame(['Gamma Product', 'Beta Product', 'Alpha Product'], $names);

        // Test latest sort (default)
        $results = $this->productService->searchProducts('Product', ['sort_by' => 'latest']);
        $ids = collect($results->items())->pluck('id')->toArray();
        self::assertSame([$product3->id, $product2->id, $product1->id], $ids);
    }

    public function testSearchPagination(): void
    {
        $category = Category::factory()->create();

        // Create 25 products
        $products = Product::factory()->count(25)->create([
            'name' => 'Test Product',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // Test default pagination (15 per page)
        $results = $this->productService->searchProducts('Test');
        self::assertSame(25, $results->total());
        self::assertSame(15, $results->perPage());
        self::assertSame(15, $results->count());
        self::assertSame(2, $results->lastPage());

        // Test custom per page
        $results = $this->productService->searchProducts('Test', [], 10);
        self::assertSame(25, $results->total());
        self::assertSame(10, $results->perPage());
        self::assertSame(10, $results->count());
        self::assertSame(3, $results->lastPage());

        // Test maximum per page limit (50)
        $results = $this->productService->searchProducts('Test', [], 100);
        self::assertSame(25, $results->total());
        self::assertSame(50, $results->perPage()); // Should be capped at 50
        self::assertSame(25, $results->count());
    }

    public function testSearchWithSpecialCharacters(): void
    {
        $product = Product::factory()->create([
            'name' => 'Product with % and _ characters',
            'description' => 'Special characters test',
            'is_active' => true,
        ]);

        // Test that special SQL characters are properly escaped
        $results = $this->productService->searchProducts('%');
        self::assertSame(1, $results->total());
        self::assertSame($product->id, $results->items()[0]->id);

        $results = $this->productService->searchProducts('_');
        self::assertSame(1, $results->total());
        self::assertSame($product->id, $results->items()[0]->id);
    }

    public function testSearchValidation(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        // Test invalid category_id
        $this->expectException(ValidationException::class);
        $this->productService->searchProducts('test', ['category_id' => 99999]);
    }

    public function testSearchValidationWithInvalidBrand(): void
    {
        // Test invalid brand_id
        $this->expectException(ValidationException::class);
        $this->productService->searchProducts('test', ['brand_id' => 99999]);
    }

    public function testSearchValidationWithInvalidPriceRange(): void
    {
        // Test invalid price range (max < min)
        $this->expectException(ValidationException::class);
        $this->productService->searchProducts('test', [
            'min_price' => 100,
            'max_price' => 50,
        ]);
    }

    public function testSearchValidationWithInvalidSortBy(): void
    {
        // Test invalid sort_by
        $this->expectException(ValidationException::class);
        $this->productService->searchProducts('test', ['sort_by' => 'invalid_sort']);
    }

    public function testEmptySearchResults(): void
    {
        Product::factory()->create([
            'name' => 'Existing Product',
            'is_active' => true,
        ]);

        $results = $this->productService->searchProducts('NonExistentProduct');

        self::assertInstanceOf(LengthAwarePaginator::class, $results);
        self::assertSame(0, $results->total());
        self::assertEmpty($results->items());
    }

    public function testSearchOnlyActiveProducts(): void
    {
        Product::factory()->create([
            'name' => 'Active Product',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Inactive Product',
            'is_active' => false,
        ]);

        $results = $this->productService->searchProducts('Product');

        self::assertSame(1, $results->total());
        self::assertSame('Active Product', $results->items()[0]->name);
        self::assertTrue($results->items()[0]->is_active);
    }

    public function testSearchCaching(): void
    {
        $product = Product::factory()->create([
            'name' => 'Cached Product',
            'is_active' => true,
        ]);

        // First search - should cache the result
        $results1 = $this->productService->searchProducts('Cached');
        self::assertSame(1, $results1->total());

        // Verify cache key exists
        $cacheKey = 'search:'.md5('cached:'.serialize([]).':15:1');
        self::assertTrue(Cache::has($cacheKey));

        // Second search - should use cached result
        $results2 = $this->productService->searchProducts('Cached');
        self::assertSame(1, $results2->total());
        self::assertSame($results1->items()[0]->id, $results2->items()[0]->id);
    }

    public function testComprehensiveSearchWorkflow(): void
    {
        // Create comprehensive test data
        $electronics = Category::factory()->create(['name' => 'Electronics']);
        $books = Category::factory()->create(['name' => 'Books']);
        $samsung = Brand::factory()->create(['name' => 'Samsung']);
        $apple = Brand::factory()->create(['name' => 'Apple']);

        $phone1 = Product::factory()->create([
            'name' => 'Samsung Galaxy S21',
            'description' => 'Latest Android smartphone',
            'price' => 799.99,
            'category_id' => $electronics->id,
            'brand_id' => $samsung->id,
            'is_active' => true,
        ]);

        $phone2 = Product::factory()->create([
            'name' => 'iPhone 13 Pro',
            'description' => 'Apple flagship smartphone',
            'price' => 999.99,
            'category_id' => $electronics->id,
            'brand_id' => $apple->id,
            'is_active' => true,
        ]);

        $book = Product::factory()->create([
            'name' => 'Programming Guide',
            'description' => 'Learn to code',
            'price' => 49.99,
            'category_id' => $books->id,
            'is_active' => true,
        ]);

        // Test 1: Basic search
        $results = $this->productService->searchProducts('smartphone');
        self::assertSame(2, $results->total());

        // Test 2: Search with category filter
        $results = $this->productService->searchProducts('', ['category_id' => $electronics->id]);
        self::assertSame(2, $results->total());

        // Test 3: Search with brand and price filters
        $results = $this->productService->searchProducts('', [
            'brand_id' => $apple->id,
            'min_price' => 900,
        ]);
        self::assertSame(1, $results->total());
        self::assertSame($phone2->id, $results->items()[0]->id);

        // Test 4: Search with sorting
        $results = $this->productService->searchProducts('phone', ['sort_by' => 'price_asc']);
        self::assertSame(2, $results->total());
        self::assertSame($phone1->id, $results->items()[0]->id); // Lower price first

        // Test 5: Verify data integrity and relationships
        $result = $results->items()[0];
        self::assertSame('Samsung Galaxy S21', $result->name);
        self::assertSame(799.99, $result->price);
        self::assertSame('Electronics', $result->category->name);
        self::assertSame('Samsung', $result->brand->name);
        self::assertTrue($result->is_active);

        // Test 6: Verify pagination metadata
        self::assertSame(1, $results->currentPage());
        self::assertSame(15, $results->perPage());
        self::assertSame(2, $results->count());
        self::assertTrue(false === $results->hasPages()); // Only 2 items, fits in one page
    }
}
