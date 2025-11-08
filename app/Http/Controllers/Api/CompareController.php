<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    private const SESSION_KEY = 'compare';
    private const MAX_ITEMS = 4;

    public function index(Request $request): JsonResponse
    {
        $ids = $this->getCompareIds($request);

        return response()->json([
            'success' => true,
            'items' => $ids,
            'count' => \count($ids),
            'limit' => self::MAX_ITEMS,
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $ids = $this->getCompareIds($request);

        if (\in_array($product->id, $ids, true)) {
            return response()->json([
                'success' => true,
                'message' => __('This product is already in your comparison list.'),
                'count' => \count($ids),
                'items' => $ids,
                'added' => false,
            ]);
        }

        if (\count($ids) >= self::MAX_ITEMS) {
            return response()->json([
                'success' => false,
                'message' => __('Comparison limit reached. You can only compare up to :count products.', ['count' => self::MAX_ITEMS]),
                'count' => \count($ids),
                'items' => $ids,
            ], 409);
        }

        $ids[] = $product->id;
        $request->session()->put(self::SESSION_KEY, $ids);

        return response()->json([
            'success' => true,
            'message' => __('Product added to comparison.'),
            'count' => \count($ids),
            'items' => $ids,
            'added' => true,
            'product' => $this->transformProduct($product),
        ], 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $ids = $this->getCompareIds($request);
        $initial = \count($ids);
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id !== $product->id));

        if (\count($ids) === $initial) {
            return response()->json([
                'success' => false,
                'message' => __('Product was not in your comparison list.'),
                'count' => $initial,
                'items' => $ids,
            ], 404);
        }

        $request->session()->put(self::SESSION_KEY, $ids);

        return response()->json([
            'success' => true,
            'message' => __('Product removed from comparison.'),
            'count' => \count($ids),
            'items' => $ids,
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return response()->json([
            'success' => true,
            'message' => __('Comparison list cleared.'),
            'count' => 0,
            'items' => [],
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function getCompareIds(Request $request): array
    {
        /** @var array<int, int>|null $ids */
        $ids = $request->session()->get(self::SESSION_KEY);

        if (!\is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->image,
            'price' => $product->price,
            'brand' => $product->brand?->name,
        ];
    }
}
