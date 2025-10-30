<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Contracts\CacheServiceContract;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly CacheServiceContract $cache
    ) {}

    /**
     * Search products.
     *
     * @param array<string, float|int|string> $filters
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function searchProducts(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Don't cache search results as they're likely to be unique per user
        return $this->repository->search($query, $filters, $perPage);
    }
}
