<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Services\ProductService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        try {
            $perPage = (int) $request->query('per_page', 15);
            $query = (string) $request->query('q', '');

            // Resolve optional slug filters to IDs
            $filters = [];
            if ($categorySlug = $request->query('category')) {
                $categoryId = Category::query()->where('slug', (string) $categorySlug)->value('id');
                if ($categoryId) {
                    $filters['category_id'] = (int) $categoryId;
                }
            }

            if ($brandSlug = $request->query('brand')) {
                $brandId = Brand::query()->where('slug', (string) $brandSlug)->value('id');
                if ($brandId) {
                    $filters['brand_id'] = (int) $brandId;
                }
            }

            // If any filter or search query is present, use the search pipeline
            if ($query !== '' || !empty($filters)) {
                $products = $this->productService->searchProducts($query, $filters, $perPage);
            } else {
                $products = $this->productService->getPaginatedProducts($perPage);
            }

            return view('products.index', compact('products'));
        } catch (\Throwable $e) {
            Log::error('Products index failure', [
                'exception' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(string $slug): View
    {
        try {
            $product = $this->productService->getBySlug($slug);
            $relatedProducts = $this->productService->getRelatedProducts($product);

            return view('products.show', compact('product', 'relatedProducts'));
        } catch (\Throwable $e) {
            Log::error('Product show failure', [
                'exception' => $e->getMessage(),
                'slug' => $slug,
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }

    /**
     * Search for products by query string.
     */
    public function search(Request $request): View
    {
        try {
            $query = (string) $request->query('q', '');
            $perPage = (int) $request->query('per_page', 15);

            // Resolve optional slug filters to IDs for search context
            $filters = [];
            if ($categorySlug = $request->query('category')) {
                $categoryId = Category::query()->where('slug', (string) $categorySlug)->value('id');
                if ($categoryId) {
                    $filters['category_id'] = (int) $categoryId;
                }
            }

            if ($brandSlug = $request->query('brand')) {
                $brandId = Brand::query()->where('slug', (string) $brandSlug)->value('id');
                if ($brandId) {
                    $filters['brand_id'] = (int) $brandId;
                }
            }

            $products = $this->productService->searchProducts($query, $filters, $perPage);

            return view('products.index', compact('products'));
        } catch (\Throwable $e) {
            Log::error('Products search failure', [
                'exception' => $e->getMessage(),
                'query' => $request->query(),
                'url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }
}
