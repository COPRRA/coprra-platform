<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📦 Seeding products...');

        // Get required data
        $categories = Category::all();
        $brands = Brand::all();
        $stores = Store::where('is_active', true)->get();

        if ($categories->isEmpty() || $brands->isEmpty() || $stores->isEmpty()) {
            $this->command->warn('⚠️ Categories, brands, or stores not found. Please run their seeders first.');

            return;
        }

        $products = $this->getProductsData($categories, $brands, $stores);
        $createdCount = 0;

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(
                ['slug' => $productData['slug']],
                $productData
            );

            if ($product->wasRecentlyCreated) {
                ++$createdCount;
            }
        }

        $this->command->info("✅ Created {$createdCount} new products");
    }

    /**
     * Get the products data for seeding.
     *
     * @param Collection $categories
     * @param Collection $brands
     * @param Collection $stores
     *
     * @return array<int, array<string, mixed>>
     */
    private function getProductsData($categories, $brands, $stores): array
    {
        // Get specific categories and brands
        $electronicsCategory = $categories->where('slug', 'electronics')->first();
        $fashionCategory = $categories->where('slug', 'fashion')->first();
        $homeCategory = $categories->where('slug', 'home-garden')->first();
        $sportsCategory = $categories->where('slug', 'sports-fitness')->first();
        $booksCategory = $categories->where('slug', 'books-media')->first();

        $appleBrand = $brands->where('slug', 'apple')->first();
        $samsungBrand = $brands->where('slug', 'samsung')->first();
        $nikeBrand = $brands->where('slug', 'nike')->first();
        $genericBrand = $brands->where('slug', 'generic')->first();

        $mainStore = $stores->where('slug', 'coprra-main-store')->first();
        $techStore = $stores->where('slug', 'techworld-electronics')->first();
        $fashionStore = $stores->where('slug', 'fashion-forward')->first();

        return [
            // Electronics Products
            [
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'description' => 'Latest iPhone 15 Pro with A17 Pro chip and titanium design',
                'price' => 999.00,
                'stock_quantity' => 50,
                'is_active' => true,
                'category_id' => $electronicsCategory?->id ?? 1,
                'brand_id' => $appleBrand?->id ?? 1,
                'store_id' => $techStore?->id ?? 1,
                'image' => 'https://via.placeholder.com/400x400/007bff/ffffff?text=iPhone+15+Pro',
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'slug' => 'samsung-galaxy-s24-ultra',
                'description' => 'Samsung Galaxy S24 Ultra with S Pen and advanced AI features',
                'price' => 1199.00,
                'stock_quantity' => 30,
                'is_active' => true,
                'category_id' => $electronicsCategory?->id ?? 1,
                'brand_id' => $samsungBrand?->id ?? 2,
                'store_id' => $techStore?->id ?? 1,
                'image' => 'https://via.placeholder.com/400x400/28a745/ffffff?text=Galaxy+S24+Ultra',
            ],
            [
                'name' => 'MacBook Pro 14-inch',
                'slug' => 'macbook-pro-14-inch',
                'description' => 'MacBook Pro 14-inch with M3 chip for professional workflows',
                'price' => 1999.00,
                'stock_quantity' => 25,
                'is_active' => true,
                'category_id' => $electronicsCategory?->id ?? 1,
                'brand_id' => $appleBrand?->id ?? 1,
                'store_id' => $techStore?->id ?? 1,
                'image' => 'https://via.placeholder.com/400x400/6c757d/ffffff?text=MacBook+Pro+14',
            ],
            [
                'name' => 'Nike Air Max 270',
                'slug' => 'nike-air-max-270',
                'description' => 'Comfortable running shoes with Air Max technology',
                'price' => 150.00,
                'stock_quantity' => 100,
                'is_active' => true,
                'category_id' => $sportsCategory?->id ?? 1,
                'brand_id' => $nikeBrand?->id ?? 1,
                'store_id' => $mainStore?->id ?? 1,
                'image' => 'https://via.placeholder.com/400x400/dc3545/ffffff?text=Nike+Air+Max+270',
            ],
            [
                'name' => 'Classic T-Shirt',
                'slug' => 'classic-t-shirt',
                'description' => 'Comfortable cotton t-shirt for everyday wear',
                'price' => 25.00,
                'stock_quantity' => 200,
                'is_active' => true,
                'category_id' => $fashionCategory?->id ?? 1,
                'brand_id' => $genericBrand?->id ?? 1,
                'store_id' => $fashionStore?->id ?? 1,
                'image' => 'https://via.placeholder.com/400x400/17a2b8/ffffff?text=Classic+T-Shirt',
            ],
            [
                'name' => 'Coffee Maker',
                'slug' => 'coffee-maker',
                'description' => 'Automatic coffee maker for perfect morning brew',
                'price' => 89.99,
                'stock_quantity' => 75,
                'is_active' => true,
                'category_id' => $homeCategory?->id ?? 1,
                'brand_id' => $genericBrand?->id ?? 1,
                'store_id' => $mainStore?->id ?? 1,
                'image' => 'https://via.placeholder.com/400x400/ffc107/000000?text=Coffee+Maker',
            ],
            [
                'name' => 'Programming Book',
                'slug' => 'programming-book',
                'description' => 'Learn programming with this comprehensive guide',
                'price' => 45.00,
                'stock_quantity' => 50,
                'is_active' => true,
                'category_id' => $booksCategory?->id ?? 1,
                'brand_id' => $genericBrand?->id ?? 1,
                'store_id' => $mainStore?->id ?? 1,
                'image' => 'https://via.placeholder.com/400x400/6f42c1/ffffff?text=Programming+Book',
            ],
        ];
    }
}
