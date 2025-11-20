<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportAppleProducts extends Command
{
    protected $signature = 'products:import-apple {--batch-size=5}';
    protected $description = 'Import Apple products from apple_products_urls.json';

    public function handle()
    {
        $this->info('🚀 COPRRA Apple Products Import');
        $this->info(str_repeat('=', 80));

        // Read JSON file
        $jsonPath = base_path('apple_products_urls.json');

        if (! file_exists($jsonPath)) {
            $this->error('❌ File not found: apple_products_urls.json');

            return 1;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        $allUrls = [];

        // Flatten URLs
        foreach ($data['categories'] as $category => $urls) {
            foreach ($urls as $url) {
                $allUrls[] = ['url' => $url, 'category' => $category];
            }
        }

        $total = \count($allUrls);
        $this->info("📦 Total URLs: {$total}");
        $this->info(str_repeat('=', 80));
        $this->newLine();

        $successful = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($allUrls as $index => $item) {
            $url = $item['url'];
            $categoryHint = $item['category'];

            try {
                $productData = $this->scrapeProduct($url, $categoryHint);

                if ($productData) {
                    $this->uploadProduct($productData);
                    ++$successful;
                } else {
                    ++$failed;
                }

                $bar->advance();

                // Small delay
                sleep(2);
            } catch (\Exception $e) {
                $this->error("\n❌ Error: ".$e->getMessage());
                ++$failed;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info(str_repeat('=', 80));
        $this->info('📊 IMPORT SUMMARY');
        $this->info(str_repeat('=', 80));
        $this->info("✅ Successful: {$successful}");
        $this->info("❌ Failed: {$failed}");
        $successRate = $total > 0 ? ($successful / $total) * 100 : 0;
        $this->info('📈 Success Rate: '.number_format($successRate, 2).'%');
        $this->info(str_repeat('=', 80));

        return 0;
    }

    private function scrapeProduct($url, $categoryHint)
    {
        $response = Http::timeout(30)
            ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->get($url)
        ;

        if (! $response->successful()) {
            return null;
        }

        $html = $response->body();

        // Parse HTML
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        // Extract data
        $title = $this->extractTitle($xpath);
        $price = $this->extractPrice($xpath);
        $image = $this->extractImage($xpath);
        $description = $this->extractDescription($xpath);
        $brand = $this->extractBrand($xpath, $title);
        $category = $this->mapCategory($categoryHint);

        return [
            'url' => $url,
            'title' => $title,
            'price' => $price,
            'currency' => 'EGP',
            'image_url' => $image,
            'description' => $description,
            'brand' => $brand,
            'category' => $category,
        ];
    }

    private function extractTitle($xpath)
    {
        $nodes = $xpath->query('//span[@id="productTitle"]');

        return $nodes->length > 0 ? trim($nodes->item(0)->textContent) : 'Unknown Product';
    }

    private function extractPrice($xpath)
    {
        $nodes = $xpath->query('//span[@class="a-price-whole"]');
        if ($nodes->length > 0) {
            $priceText = trim($nodes->item(0)->textContent);
            $priceText = str_replace(',', '', $priceText);
            if (preg_match('/[\d.]+/', $priceText, $matches)) {
                return (float) $matches[0];
            }
        }

        return 0.0;
    }

    private function extractImage($xpath)
    {
        $nodes = $xpath->query('//img[@id="landingImage"]/@src');
        if ($nodes->length > 0) {
            return $nodes->item(0)->nodeValue;
        }

        return 'https://via.placeholder.com/800x800?text=No+Image';
    }

    private function extractDescription($xpath)
    {
        $nodes = $xpath->query('//div[@id="feature-bullets"]//li');
        $descriptions = [];
        foreach ($nodes as $node) {
            $text = trim($node->textContent);
            if (\strlen($text) > 10) {
                $descriptions[] = $text;
            }
            if (\count($descriptions) >= 5) {
                break;
            }
        }

        return implode(' | ', $descriptions) ?: 'No description';
    }

    private function extractBrand($xpath, $title)
    {
        return 'Apple'; // For this batch
    }

    private function mapCategory($hint)
    {
        $mapping = [
            'iPhones' => 'Mobile Phones',
            'iPads' => 'Tablets',
            'MacBooks' => 'Laptops',
            'Apple Watch' => 'Smart Watches',
            'AirPods' => 'Audio Devices',
        ];

        return $mapping[$hint] ?? 'Electronics';
    }

    private function uploadProduct($data)
    {
        $category = Category::firstOrCreate(
            ['name' => $data['category']],
            ['slug' => \Str::slug($data['category'])]
        );

        $brand = Brand::firstOrCreate(
            ['name' => $data['brand']],
            ['slug' => \Str::slug($data['brand'])]
        );

        $store = Store::firstOrCreate(
            ['name' => 'Amazon Egypt'],
            ['url' => 'https://www.amazon.eg', 'slug' => 'amazon-egypt']
        );

        Product::updateOrCreate(
            ['url' => $data['url']],
            [
                'name' => $data['title'],
                'slug' => \Str::slug($data['title']),
                'price' => $data['price'],
                'currency' => $data['currency'],
                'image_url' => $data['image_url'],
                'description' => $data['description'],
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'store_id' => $store->id,
            ]
        );
    }
}
