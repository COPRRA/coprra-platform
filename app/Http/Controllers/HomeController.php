<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the application home page.
     */
    public function index(): Response|View
    {
        // Get featured products (is_featured = true) or latest products
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with(['category', 'brand'])
            ->limit(8)
            ->get();

        // If no featured products, get latest products instead
        if ($featuredProducts->isEmpty()) {
            $featuredProducts = Product::query()
                ->where('is_active', true)
                ->with(['category', 'brand'])
                ->latest()
                ->limit(8)
                ->get();
        }

        // Get top categories with product counts
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->limit(6)
            ->get();

        // Get top brands with product counts
        $brands = Brand::query()
            ->where('is_active', true)
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->limit(6)
            ->get();

        return view('home', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }
}
