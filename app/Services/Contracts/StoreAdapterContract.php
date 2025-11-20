<?php

declare(strict_types=1);

namespace App\Services\Contracts;

/**
 * Interface for store adapters.
 */
interface StoreAdapterContract
{
    /**
     * Get the store identifier.
     */
    public function getStoreIdentifier(): string;

    /**
     * Check if the adapter is available.
     */
    public function isAvailable(): bool;

    /**
     * Fetch product data by identifier.
     *
     * @return array<string, scalar|array|* @method static \App\Models\Brand create(array<string, string|bool|null>|null
     *
     * @psalm-return array{
     *   name: array|scalar,
     *   price: float,
     *   currency: array|scalar,
     *   url: array|scalar,
     *   image_url: array|scalar|null,
     *   availability: array|scalar,
     *   rating: float|null,
     *   reviews_count: int|null,
     *   description: array|scalar|null,
     *   brand: array|scalar|null,
     *   category: array|scalar|null,
     *   metadata: array|scalar
     * }|null
     */
    public function fetchProduct(string $productIdentifier): ?array;

    /**
     * Get product URL.
     */
    public function getProductUrl(string $identifier): string;
}
