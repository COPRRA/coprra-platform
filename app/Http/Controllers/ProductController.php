<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Services\AffiliateLinkService;
use App\Services\PriceFetchingService;
use App\Services\ProductService;
use App\Services\RecommendationService;
use App\Services\SEOService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly SEOService $seoService,
        private readonly PriceFetchingService $priceFetchingService,
        private readonly RecommendationService $recommendationService
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
            if ($query !== '' || ! empty($filters)) {
                $products = $this->productService->searchProducts($query, $filters, $perPage);
            } else {
                $products = $this->productService->getPaginatedProducts($perPage);
            }

            $wishlistProductIds = auth()->check()
                ? auth()->user()->wishlist()->pluck('products.id')->all()
                : [];

            return view('products.index', [
                'products' => $products,
                'wishlistProductIds' => $wishlistProductIds,
                'maxProducts' => 4, // Maximum products for comparison
            ]);
        } catch (\Throwable $e) {
            Log::error('Products index failure', [
                'exception' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * Display the specified product.
     */
    public function show(string $slug): View
    {
        try {
            $product = $this->productService->getBySlug($slug);

            // Return 404 if product not found
            if ($product === null) {
                abort(404);
            }

            $relatedProducts = $this->productService->getRelatedProducts($product);

            $isWishlisted = auth()->check()
                ? auth()->user()->wishlist()->where('products.id', $product->id)->exists()
                : false;

            // Load reviews with user relationship for display
            $reviews = $product->reviews()->with('user')->where('is_approved', true)->latest()->take(10)->get();
            $averageRating = $product->getAverageRating();
            $reviewsCount = $product->reviews()->where('is_approved', true)->count();

            $seoMeta = $this->seoService->generateMetaData($product, 'Product');
            $productSchema = $this->seoService->generateProductSchema($product);

            // Get AI-powered product recommendations
            $recommendations = [];
            try {
                $recommendations = $this->recommendationService->getSimilarProducts($product, 4);
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch product recommendations', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                // Fallback to related products if recommendations fail
                $recommendations = $relatedProducts->take(4)->all();
            }

            return view('products.show', [
                'product' => $product,
                'relatedProducts' => $relatedProducts,
                'recommendations' => $recommendations,
                'isWishlisted' => $isWishlisted,
                'reviews' => $reviews,
                'averageRating' => $averageRating,
                'reviewsCount' => $reviewsCount,
                'seoMeta' => $seoMeta,
                'productSchema' => $productSchema,
            ]);
        } catch (\Throwable $e) {
            Log::error('Product show failure', [
                'exception' => $e->getMessage(),
                'slug' => $slug,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'An unexpected error occurred. Please try again later.');
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

            $wishlistProductIds = auth()->check()
                ? auth()->user()->wishlist()->pluck('products.id')->all()
                : [];

            return view('products.index', [
                'products' => $products,
                'wishlistProductIds' => $wishlistProductIds,
            ]);
        } catch (\Throwable $e) {
            Log::error('Products search failure', [
                'exception' => $e->getMessage(),
                'query' => $request->query(),
                'url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * Display the product offers page with live prices from partners.
     */
    public function showOffers(string $slug): View
    {
        try {
            $product = $this->productService->getBySlug($slug);

            // Return 404 if product not found
            if ($product === null) {
                abort(404);
            }

            // Get user's selected country
            $country = session('locale_country') 
                ?? request()->cookie('locale_country') 
                ?? 'US'; // Default to US

            // Fetch live offers from available stores
            $offers = $this->priceFetchingService->getLiveOffers($product, $country);

            // Sort offers by price (cheapest first)
            usort($offers, static function (array $a, array $b): int {
                $priceA = $a['price'] ?? 0.0;
                $priceB = $b['price'] ?? 0.0;
                
                return $priceA <=> $priceB;
            });

            $seoMeta = $this->seoService->generateMetaData($product, 'Product Offers');

            return view('products.offers', [
                'product' => $product,
                'offers' => $offers,
                'country' => $country,
                'seoMeta' => $seoMeta,
            ]);
        } catch (\Throwable $e) {
            Log::error('Product offers page failure', [
                'exception' => $e->getMessage(),
                'slug' => $slug,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->with('error', 'An unexpected error occurred while loading offers. Please try again later.');
        }
    }
}
