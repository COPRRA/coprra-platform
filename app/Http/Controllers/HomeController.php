<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the application home page.
     */
    public function index(): Response|View
    {
        // Cache featured products for 60 minutes
        $featuredProducts = Cache::remember('home_featured_products', 3600, function () {
            // Get featured products (is_featured = true) or latest products
            $products = Product::query()
                ->where('is_active', true)
                ->where('is_featured', true)
                ->with(['category', 'brand'])
                ->limit(8)
                ->get();

            // If no featured products, get latest products instead
            if ($products->isEmpty()) {
                $products = Product::query()
                    ->where('is_active', true)
                    ->with(['category', 'brand'])
                    ->latest()
                    ->limit(8)
                    ->get();
            }

            return $products;
        });

        // Cache top categories for 60 minutes
        $categories = Cache::remember('home_top_categories', 3600, function () {
            return Category::query()
                ->where('is_active', true)
                ->withCount('products')
                ->having('products_count', '>', 0)
                ->orderBy('products_count', 'desc')
                ->limit(6)
                ->get();
        });

        // Cache top brands for 60 minutes
        $brands = Cache::remember('home_top_brands', 3600, function () {
            return Brand::query()
                ->where('is_active', true)
                ->withCount('products')
                ->having('products_count', '>', 0)
                ->orderBy('products_count', 'desc')
                ->limit(6)
                ->get();
        });

        return view('home', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }
}
