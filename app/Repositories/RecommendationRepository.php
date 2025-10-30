<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class RecommendationRepository
{
    /**
     * Get similar products based on category.
     *
     * @return Collection<int, Product>
     */
    public function getSimilarProducts(Product $product, int $limit = 5): Collection
    {
        return Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
        ;
    }

    /**
     * Get products frequently bought together.
     *
     * @return Collection<int, Product>
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 5): Collection
    {
        $productIds = $this->getFrequentlyBoughtProductIds($product, $limit);

        return Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
        ;
    }

    /**
     * Get product IDs that are frequently bought together with the given product.
     *
     * @return array<int, int>
     */
    public function getFrequentlyBoughtProductIds(Product $product, int $limit): array
    {
        return OrderItem::whereHas('order', static function ($query) use ($product): void {
            $query->whereHas('items', static function ($q) use ($product): void {
                $q->where('product_id', $product->id);
            });
        })
            ->where('product_id', '!=', $product->id)
            ->select('product_id')
            ->selectRaw('COUNT(*) as frequency')
            ->groupBy('product_id')
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->pluck('product_id')
            ->toArray()
        ;
    }

    /**
     * Get user's purchase history with products.
     *
     * @return Collection<int, OrderItem>
     */
    public function getUserPurchaseHistory(User $user): Collection
    {
        return OrderItem::whereHas('order', static function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->with('product')
            ->get()
        ;
    }

    /**
     * Get purchased product IDs for a user.
     *
     * @return array<int, int>
     */
    public function getPurchasedProductIds(User $user): array
    {
        return OrderItem::whereHas('order', static function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->distinct()
            ->pluck('product_id')
            ->toArray()
        ;
    }

    /**
     * Get trending products based on recent orders.
     *
     * @return Collection<int, Product>
     */
    public function getTrendingProducts(int $limit = 10, int $days = 30): Collection
    {
        $cutoffDate = now()->subDays($days);

        $trendingProductIds = OrderItem::whereHas('order', static function ($query) use ($cutoffDate): void {
            $query->where('created_at', '>=', $cutoffDate);
        })
            ->select('product_id')
            ->selectRaw('COUNT(*) as order_count')
            ->groupBy('product_id')
            ->orderBy('order_count', 'desc')
            ->limit($limit)
            ->pluck('product_id')
            ->toArray()
        ;

        return Product::whereIn('id', $trendingProductIds)
            ->where('is_active', true)
            ->get()
        ;
    }

    /**
     * Get products by category with rating filter.
     *
     * @return Collection<int, Product>
     */
    public function getProductsByCategory(int $categoryId, float $minRating = 0.0, int $limit = 10): Collection
    {
        return Product::where('category_id', $categoryId)
            ->where('is_active', true)
            ->where('rating', '>=', $minRating)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
        ;
    }

    /**
     * Get products by brand with rating filter.
     *
     * @return Collection<int, Product>
     */
    public function getProductsByBrand(int $brandId, float $minRating = 0.0, int $limit = 10): Collection
    {
        return Product::where('brand_id', $brandId)
            ->where('is_active', true)
            ->where('rating', '>=', $minRating)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get()
        ;
    }

    /**
     * Get products within price range.
     *
     * @return Collection<int, Product>
     */
    public function getProductsInPriceRange(float $minPrice, float $maxPrice, int $limit = 10): Collection
    {
        return Product::where('is_active', true)
            ->whereBetween('price', [$minPrice, $maxPrice])
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }
}
