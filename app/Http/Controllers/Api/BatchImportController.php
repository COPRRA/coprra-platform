<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductDescriptionGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BatchImportController extends Controller
{
    /**
     * Batch import products via HTTP API
     * More reliable than SSH for large imports.
     */
    public function batchImport(Request $request)
    {
        // Security check
        $secret = $request->header('X-Import-Secret');
        if ('COPRRA_BATCH_IMPORT_2025' !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate request
        $validated = $request->validate([
            'brand' => 'required|string',
            'products' => 'required|array',
            'products.*.name' => 'required|string',
            'products.*.category' => 'required|string',
            'products.*.description' => 'nullable|string',
            'products.*.price_usd' => 'required|numeric',
            'products.*.image' => 'nullable|url',
            'products.*.specs' => 'nullable|array',
            'products.*.features' => 'nullable|array',
        ]);

        $brandName = $validated['brand'];
        $products = $validated['products'];

        // Get or create brand
        $brand = Brand::firstOrCreate(
            ['name' => $brandName],
            ['slug' => \Str::slug($brandName)]
        );

        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($products as $productData) {
            try {
                // Get or create category
                $category = Category::firstOrCreate(
                    ['name' => $productData['category']],
                    ['slug' => \Str::slug($productData['category'])]
                );

                // Convert USD to EGP (approximate rate: 50)
                $priceEGP = $productData['price_usd'] * 50;

                // Generate enhanced description
                $enhancedDescription = ProductDescriptionGenerator::generate([
                    'name' => $productData['name'],
                    'brand' => $brandName,
                    'category' => $productData['category'],
                    'description' => $productData['description'] ?? '',
                    'specs' => $productData['specs'] ?? [],
                    'features' => $productData['features'] ?? [],
                ]);

                // Create or update product
                $product = Product::updateOrCreate(
                    [
                        'name' => $productData['name'],
                        'brand_id' => $brand->id,
                    ],
                    [
                        'slug' => \Str::slug($productData['name']),
                        'description' => $enhancedDescription,
                        'price' => $priceEGP,
                        'currency' => 'EGP',
                        'image_url' => $productData['image'] ?? null,
                        'category_id' => $category->id,
                        'specifications' => isset($productData['specs']) ? json_encode($productData['specs']) : null,
                        'features' => isset($productData['features']) ? json_encode($productData['features']) : null,
                    ]
                );

                ++$imported;

                // Log success
                Log::channel('scraper')->info("Imported: {$productData['name']} (ID: {$product->id})");
            } catch (\Exception $e) {
                ++$failed;
                $errors[] = [
                    'product' => $productData['name'],
                    'error' => $e->getMessage(),
                ];

                // Log error
                Log::channel('scraper')->error("Failed to import: {$productData['name']} - {$e->getMessage()}");
            }
        }

        return response()->json([
            'success' => true,
            'brand' => $brandName,
            'imported' => $imported,
            'failed' => $failed,
            'total' => \count($products),
            'errors' => $errors,
        ]);
    }
}
