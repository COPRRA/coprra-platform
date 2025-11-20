<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MassProductSeeder extends Seeder
{
    public function run()
    {
        echo "🚀 MASS PRODUCT IMPORT - Seeder Method\n";
        echo str_repeat('=', 80)."\n\n";

        $this->importApple();
        $this->importSamsung();

        echo "\n".str_repeat('=', 80)."\n";
        echo "✅ ALL BRANDS IMPORTED!\n";
        echo str_repeat('=', 80)."\n";
    }

    private function importApple()
    {
        echo "🍎 Importing Apple...\n";

        $brand = Brand::firstOrCreate(['name' => 'Apple'], ['slug' => 'apple']);
        $data = require base_path('auto_import_system/data/apple_data.php');

        $this->importProducts($brand, $data);

        echo '✅ Apple: '.\count($data)." products\n\n";
    }

    private function importSamsung()
    {
        echo "📱 Importing Samsung...\n";

        $brand = Brand::firstOrCreate(['name' => 'Samsung'], ['slug' => 'samsung']);
        $data = require base_path('auto_import_system/data/samsung_data.php');

        $this->importProducts($brand, $data);

        echo '✅ Samsung: '.\count($data)." products\n\n";
    }

    private function importProducts($brand, $productsData)
    {
        foreach ($productsData as $i => $item) {
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
                        'description' => $item['description'],
                        'price' => $priceEGP,
                        'currency' => 'EGP',
                        'image_url' => $item['image'],
                        'category_id' => $category->id,
                        'specifications' => isset($item['specs']) ? json_encode($item['specs']) : null,
                        'features' => isset($item['features']) ? json_encode($item['features']) : null,
                    ]
                );

                if (($i + 1) % 10 === 0 || ($i + 1) === \count($productsData)) {
                    echo '   ✅ ['.($i + 1).'/'.\count($productsData).'] '.substr($item['name'], 0, 40)."...\n";
                }
            } catch (\Exception $e) {
                echo '   ❌ Failed: '.substr($item['name'], 0, 30).' - '.$e->getMessage()."\n";
            }
        }
    }
}
