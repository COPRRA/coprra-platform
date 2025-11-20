<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class PriceOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Seeding price offers...');

        // Get products that can have offers
        $products = Product::where('is_active', true)->get();

        if ($products->isEmpty()) {
            $this->command->warn('⚠️ No products found. Please run ProductSeeder first.');

            return;
        }

        $offers = $this->getPriceOffersData($products);
        $createdCount = 0;

        foreach ($offers as $offerData) {
            $offer = PriceOffer::firstOrCreate(
                [
                    'product_id' => $offerData['product_id'],
                    'store_id' => $offerData['store_id'],
                ],
                $offerData
            );

            if ($offer->wasRecentlyCreated) {
                ++$createdCount;
            }
        }

        $this->command->info("✅ Created {$createdCount} new price offers");
    }

    /**
     * Get the price offers data for seeding.
     *
     * @param Collection $products
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPriceOffersData($products): array
    {
        $offers = [];

        // Get available stores
        $stores = Store::where('is_active', true)->get();

        if ($stores->isEmpty()) {
            return [];
        }

        // Create price offers for each product from different stores
        foreach ($products as $product) {
            // Create 2-3 offers per product from different stores
            $storeCount = min(3, $stores->count());
            $selectedStores = $stores->random($storeCount);

            foreach ($selectedStores as $index => $store) {
                $basePrice = $product->price;
                $priceVariation = 1 + (($index - 1) * 0.1); // -10%, 0%, +10% price variation
                $offerPrice = round($basePrice * $priceVariation, 2);

                $offers[] = [
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'price' => $offerPrice,
                    'currency' => 'USD',
                    'product_url' => "https://{$store->domain}/products/{$product->slug}",
                    'affiliate_url' => "https://{$store->domain}/affiliate/{$product->slug}",
                    'in_stock' => rand(0, 10) > 1, // 90% chance of being in stock
                    'stock_quantity' => rand(5, 100),
                    'condition' => 'new',
                    'rating' => round(rand(35, 50) / 10, 1), // 3.5 to 5.0 rating
                    'reviews_count' => rand(10, 500),
                    'image_url' => $product->image,
                    'is_available' => true,
                    'original_price' => $basePrice,
                ];
            }
        }

        return $offers;
    }
}
