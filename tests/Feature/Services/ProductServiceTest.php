<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Contracts\CacheServiceContract;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $service;

    private MockInterface $repository;

    private MockInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();

        // Verify method existence for better test reliability
        self::assertTrue(
            method_exists(ProductRepository::class, 'getPaginatedActive'),
            'ProductRepository must have getPaginatedActive method'
        );
        self::assertTrue(
            method_exists(CacheServiceContract::class, 'remember'),
            'CacheServiceContract must have remember method'
        );

        $this->repository = \Mockery::mock(ProductRepository::class);
        $this->cache = \Mockery::mock(CacheServiceContract::class);
        $this->service = new ProductService($this->repository, $this->cache);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testReturnsPaginatedProductsFromCache(): void
    {
        // Arrange
        $perPage = 15;
        $page = 1;
        $products = collect([
            Product::factory()->make(['id' => 1, 'name' => 'Product 1']),
            Product::factory()->make(['id' => 2, 'name' => 'Product 2']),
        ]);

        $paginator = new LengthAwarePaginator(
            $products,
            2,
            $perPage,
            $page,
            ['path' => 'http://localhost', 'pageName' => 'page']
        );

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, \Mockery::type('Closure'), ['products'])
            ->andReturn($paginator)
        ;

        // Act
        $result = $this->service->getPaginatedProducts($perPage);

        // Assert
        self::assertInstanceOf(LengthAwarePaginator::class, $result);
        self::assertSame(2, $result->total());
        self::assertSame($perPage, $result->perPage());
    }

    public function testReturnsEmptyPaginatorWhenCacheReturnsNull(): void
    {
        // Arrange
        $perPage = 15;
        $page = 1;

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, \Mockery::type('Closure'), ['products'])
            ->andReturn(null)
        ;

        // Act
        $result = $this->service->getPaginatedProducts($perPage);

        // Assert
        self::assertInstanceOf(LengthAwarePaginator::class, $result);
        self::assertSame(0, $result->total());
        self::assertSame($perPage, $result->perPage());
    }

    public function testHandlesInvalidPageNumber(): void
    {
        // Arrange
        $perPage = 15;
        $invalidPage = 'invalid';

        // Use real request with invalid page parameter
        $request = Request::create('http://localhost', 'GET', ['page' => $invalidPage]);
        $this->app->instance('request', $request);

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, \Mockery::type('Closure'), ['products'])
            ->andReturn(null)
        ;

        // Act
        $result = $this->service->getPaginatedProducts($perPage);

        // Assert
        self::assertInstanceOf(LengthAwarePaginator::class, $result);
        self::assertSame(0, $result->total());
    }

    public function testUsesDefaultPerPageWhenNotSpecified(): void
    {
        // Arrange
        $defaultPerPage = 15;
        $page = 1;

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, \Mockery::type('Closure'), ['products'])
            ->andReturn(null)
        ;

        // Act
        $result = $this->service->getPaginatedProducts();

        // Assert
        self::assertInstanceOf(LengthAwarePaginator::class, $result);
        self::assertSame($defaultPerPage, $result->perPage());
    }

    public function testCallsRepositoryWhenCacheMiss(): void
    {
        // Arrange
        $perPage = 15;
        $page = 1;
        $products = collect([
            Product::factory()->make(['id' => 1, 'name' => 'Product 1']),
        ]);

        $paginator = new LengthAwarePaginator(
            $products,
            1,
            $perPage,
            $page,
            ['path' => 'http://localhost', 'pageName' => 'page']
        );

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, \Mockery::type('Closure'), ['products'])
            ->andReturnUsing(static function ($key, $ttl, $callback) {
                return $callback();
            })
        ;

        $this->repository->shouldReceive('getPaginatedActive')
            ->once()
            ->with($perPage)
            ->andReturn($paginator)
        ;

        // Act
        $result = $this->service->getPaginatedProducts($perPage);

        // Assert
        self::assertInstanceOf(LengthAwarePaginator::class, $result);
        self::assertSame(1, $result->total());
    }
}
