<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class AllBrandsSeeder extends Seeder
{
    public function run()
    {
        echo "🚀 ALL BRANDS MASS IMPORT\n";
        echo str_repeat('=', 80)."\n\n";

        $brands = ['apple', 'samsung', 'sony', 'hp', 'dell'];
        $totalImported = 0;

        foreach ($brands as $brandSlug) {
            $file = base_path("auto_import_system/data/{$brandSlug}_data.php");

            if (! file_exists($file)) {
                echo "⚠️  Skipping {$brandSlug} - file not found\n";

                continue;
            }

            $brandName = ucfirst($brandSlug);
            echo "🏷️  {$brandName}...\n";

            $brand = Brand::firstOrCreate(['name' => $brandName], ['slug' => $brandSlug]);
            $data = require $file;

            $imported = 0;
            foreach ($data as $item) {
                try {
                    $category = Category::firstOrCreate(
                        ['name' => $item['category']],
                        ['slug' => \Str::slug($item['category'])]
                    );

                    $priceEGP = $item['price_usd'] * 50;

                    Product::updateOrCreate(
                        ['name' => $item['name'], 'brand_id' => $brand->id],
                        [
                            'slug' => \Str::slug($item['name']),
                            'description' => $item['description'] ?? '',
                            'price' => $priceEGP,
                            'currency' => 'EGP',
                            'image_url' => $item['image'] ?? '',
                            'category_id' => $category->id,
                            'specifications' => isset($item['specs']) ? json_encode($item['specs']) : null,
                            'features' => isset($item['features']) ? json_encode($item['features']) : null,
                        ]
                    );

                    ++$imported;
                } catch (\Exception $e) {
                    // Continue on error
                }
            }

            echo "   ✅ {$imported} products imported\n\n";
            $totalImported += $imported;
        }

        echo str_repeat('=', 80)."\n";
        echo "✅ TOTAL IMPORTED: {$totalImported} products\n";
        echo str_repeat('=', 80)."\n";
    }
}
