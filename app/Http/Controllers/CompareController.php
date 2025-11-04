<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    /**
     * Show the comparison page.
     */
    public function index(Request $request): View
    {
        $compareIds = session()->get('compare', []);

        $products = Product::whereIn('id', $compareIds)
            ->with(['category', 'brand'])
            ->get()
        ;

        return view('compare.index', compact('products'));
    }

    /**
     * Add a product to comparison (session-based, works for guests).
     */
    public function add(Product $product): RedirectResponse
    {
        $compare = session()->get('compare', []);

        // Limit to 4 products
        if (\count($compare) >= 4) {
            return back()->with('warning', 'You can only compare up to 4 products at once.');
        }

        if (! \in_array($product->id, $compare, true)) {
            $compare[] = $product->id;
            session()->put('compare', $compare);

            return back()->with('success', 'Product added to comparison list! ('.\count($compare).'/4)');
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
