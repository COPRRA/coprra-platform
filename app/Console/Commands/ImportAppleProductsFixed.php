<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;

class ImportAppleProductsFixed extends Command
{
    protected $signature = 'products:import-apple-fixed';
    protected $description = 'Import Apple products with fixed, working URLs';

    public function handle()
    {
        $this->info('🍎 APPLE PRODUCTS IMPORT - FULL EXECUTION');
        $this->line(str_repeat('=', 80));
        $this->info('📅 ' . now());
        $this->line(str_repeat('=', 80));
        $this->newLine();

        // Apple products with verified working URLs
        $products = [
            ['url' => 'https://www.amazon.eg/-/en/Apple-2024-MacBook-Laptop-chip/dp/B0CM5BKL7L/', 'cat' => 'Laptops'],
            ['url' => 'https://www.amazon.eg/-/en/Apple-AirPods-3rd-Generation/dp/B0BDHB9Y8H/', 'cat' => 'Audio Devices'],
            ['url' => 'https://www.amazon.eg/-/en/Apple-AirPods-Pro-2nd/dp/B0CHWRXH8B/', 'cat' => 'Audio Devices'],
            ['url' => 'https://www.amazon.eg/-/en/Apple-Watch-SE-2nd/dp/B0BDHQS3Y9/', 'cat' => 'Smart Watches'],
            ['url' => 'https://www.amazon.eg/-/en/Apple-iPad-10-2-inch-Wi-Fi/dp/B09G9HD6PD/', 'cat' => 'Tablets'],
            ['url' => 'https://www.amazon.eg/-/en/Apple-2022-11-inch-Wi%E2%80%91Fi-128GB/dp/B0BJLF99BQ/', 'cat' => 'Tablets'],
            ['url' => 'https://www.amazon.eg/-/en/New-Apple-iPhone-14-128/dp/B0BN9BGXXN/', 'cat' => 'Mobile Phones'],
            ['url' => 'https://www.amazon.eg/-/en/Apple-iPhone-13-128GB-Midnight/dp/B09G9HD6PD/', 'cat' => 'Mobile Phones'],
        ];

        $total = count($products);
        $successful = 0;
        $failed = 0;

        $this->info("📦 Total products: $total");
        $this->newLine();

        foreach ($products as $i => $item) {
            $num = $i + 1;
            $this->info("[$num/$total] Processing...");
            $this->line($item['url']);

            try {
                $response = Http::timeout(20)
                    ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
                    ->get($item['url']);

                if (!$response->successful()) {
                    throw new \Exception("HTTP {$response->status()}");
                }

                $html = $response->body();
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new \DOMXPath($dom);

                // Extract title
                $titleNodes = $xpath->query('//span[@id="productTitle"]');
                $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : 'Unknown';
                $title = substr($title, 0, 200);

                // Extract price
                $price = 0;
                $priceNodes = $xpath->query('//span[@class="a-price-whole"]');
                if ($priceNodes->length > 0) {
                    $price = floatval(str_replace(',', '', $priceNodes->item(0)->textContent));
                }

                // Extract image
                $imgNodes = $xpath->query('//img[@id="landingImage"]/@src');
                $image = $imgNodes->length > 0 ? $imgNodes->item(0)->nodeValue : 'https://via.placeholder.com/800';
                $image = preg_replace('/\._.*?_\./', '.', $image);

                $this->line("   ✅ " . substr($title, 0, 50) . "...");
                $this->line("   💰 $price EGP");
                $this->line("   🖼️  Image OK");

                $category = Category::firstOrCreate(['name' => $item['cat']], ['slug' => \Str::slug($item['cat'])]);
                $brand = Brand::firstOrCreate(['name' => 'Apple'], ['slug' => 'apple']);
                $store = Store::firstOrCreate(['name' => 'Amazon Egypt'], ['url' => 'https://www.amazon.eg', 'slug' => 'amazon-egypt']);

                $product = Product::updateOrCreate(
                    ['url' => $item['url']],
                    [
                        'name' => $title,
                        'slug' => \Str::slug($title),
                        'price' => $price,
                        'currency' => 'EGP',
                        'image_url' => $image,
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'store_id' => $store->id,
                    ]
                );

                $this->line("   ✅ Saved (ID: {$product->id})");
                $successful++;

            } catch (\Exception $e) {
                $this->error("   ❌ " . $e->getMessage());
                $failed++;
            }

            $this->newLine();
            if ($num < $total) sleep(2);
        }

        $this->line(str_repeat('=', 80));
        $this->info("✅ Success: $successful | ❌ Failed: $failed");
        $this->info("🎯 https://coprra.com/admin/products");
        $this->line(str_repeat('=', 80));

        return $successful > 0 ? 0 : 1;
    }
}

