<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    private const MAX_ITEMS = 4;

    /**
     * Show the comparison page.
     */
    public function index(Request $request): View
    {
        /** @var array<int, int> $compareIds */
        $compareIds = $request->session()->get('compare', []);

        $products = Product::query()
            ->whereIn('id', $compareIds)
            ->with(['category', 'brand'])
            ->get()
            ->sortBy(static function (Product $product) use ($compareIds): int {
                $position = array_search($product->id, $compareIds, true);

                return $position === false ? PHP_INT_MAX : $position;
            })
            ->values();

        $attributeLabels = [
            'image' => __('Image'),
            'name' => __('Name'),
            'brand' => __('Brand'),
            'price' => __('Official Price (السعر الرسمي)'),
            'category' => __('Category'),
            'year' => __('Year of Manufacture'),
            'colors' => __('Available Colors'),
            'description' => __('Description'),
        ];

        $wishlistProductIds = auth()->check()
            ? auth()->user()->wishlist()->pluck('products.id')->all()
            : [];

        return view('compare.index', [
            'products' => $products,
            'attributeLabels' => $attributeLabels,
            'maxProducts' => self::MAX_ITEMS,
            'wishlistProductIds' => $wishlistProductIds,
        ]);
    }

    /**
     * Add a product to comparison (session-based, works for guests).
     */
    public function add(Product $product): RedirectResponse
    {
        $compare = session()->get('compare', []);

        // Limit to 4 products
        if (\count($compare) >= self::MAX_ITEMS) {
            return back()->with('warning', __('Comparison limit reached. You can only compare up to :count products.', [
                'count' => self::MAX_ITEMS,
            ]));
        }

        if (! \in_array($product->id, $compare, true)) {
            $compare[] = $product->id;
            session()->put('compare', $compare);

            return back()->with('success', __('Product added to comparison list! (:current/:max)', [
                'current' => \count($compare),
                'max' => self::MAX_ITEMS,
            ]));
        }

        return back()->with('info', 'Product is already in your comparison list.');
    }

    /**
     * Remove a product from comparison.
     */
    public function remove(Product $product): RedirectResponse
    {
        $compare = session()->get('compare', []);

        if (($key = array_search($product->id, $compare, true)) !== false) {
            unset($compare[$key]);
            $compare = array_values($compare); // Re-index array
            session()->put('compare', $compare);

            return back()->with('success', 'Product removed from comparison.');
        }

        return back()->with('error', 'Product not found in comparison list.');
    }

    /**
     * Clear all products from comparison.
     */
    public function clear(): RedirectResponse
    {
        session()->forget('compare');

        return back()->with('success', 'Comparison list cleared.');
    }
}
