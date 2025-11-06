<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComparisonController extends Controller
{
    /**
     * Display the product comparison page.
     */
    public function index(Request $request): View
    {
        $comparisonIds = session('comparison', []);

        $products = Product::query()
            ->with(['category', 'brand'])
            ->whereIn('id', $comparisonIds)
            ->get();

        // Get available years and colors for filters
        $availableYears = Product::query()
            ->whereIn('id', $comparisonIds)
            ->whereNotNull('year_of_manufacture')
            ->distinct()
            ->pluck('year_of_manufacture')
            ->sort()
            ->values();

        $availableColors = Product::query()
            ->whereIn('id', $comparisonIds)
            ->whereNotNull('available_colors')
            ->get()
            ->pluck('available_colors')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('compare.index', [
            'products' => $products,
            'availableYears' => $availableYears,
            'availableColors' => $availableColors,
            'maxProducts' => 4,
        ]);
    }

    /**
     * Add a product to comparison.
     */
    public function add(Request $request, Product $product): RedirectResponse
    {
        $comparisonIds = session('comparison', []);

        // Check if already in comparison
        if (in_array($product->id, $comparisonIds)) {
            return redirect()->back()->with('info', __('Product already in comparison'));
        }

        // Check maximum limit (4 products)
        if (count($comparisonIds) >= 4) {
            return redirect()->back()->with('error', __('Maximum 4 products can be compared'));
        }

        $comparisonIds[] = $product->id;
        session(['comparison' => $comparisonIds]);

        return redirect()->back()->with('success', __('Product added to comparison'));
    }

    /**
     * Remove a product from comparison.
     */
    public function remove(Request $request, Product $product): RedirectResponse
    {
        $comparisonIds = session('comparison', []);

        $comparisonIds = array_values(array_filter($comparisonIds, function ($id) use ($product) {
            return $id !== $product->id;
        }));

        session(['comparison' => $comparisonIds]);

        if ($request->ajax() || $request->wantsJson()) {
            return redirect()->route('compare.index')->with('success', __('Product removed from comparison'));
        }

        return redirect()->back()->with('success', __('Product removed from comparison'));
    }

    /**
     * Clear all products from comparison.
     */
    public function clear(Request $request): RedirectResponse
    {
        session()->forget('comparison');

        return redirect()->route('products.index')->with('success', __('Comparison cleared'));
    }
}
