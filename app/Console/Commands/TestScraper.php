<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestScraper extends Command
{
    protected $signature = 'scraper:test';
    protected $description = 'Test scraper on multiple stores';

    public function handle()
    {
        $this->info('🧪 IMPORT TOOL TEST - Multiple Stores');
        $this->line(str_repeat('=', 80));
        $this->info('📅 '.now());
        $this->line(str_repeat('=', 80));
        $this->newLine();

        $testProducts = [
            [
                'url' => 'https://www.amazon.eg/-/en/Samsung-Galaxy-S24-Ultra-Titanium/dp/B0CMDRCX67/',
                'store' => 'Amazon Egypt',
                'expected_brand' => 'Samsung',
                'expected_category' => 'Mobile Phones',
            ],
            [
                'url' => 'https://www.amazon.eg/-/en/Apple-iPhone-15-Pro-Max/dp/B0CHX78R8V/',
                'store' => 'Amazon Egypt',
                'expected_brand' => 'Apple',
                'expected_category' => 'Mobile Phones',
            ],
            [
                'url' => 'https://www.amazon.eg/-/en/Sony-PlayStation-5-Console/dp/B08H95Y452/',
                'store' => 'Amazon Egypt',
                'expected_brand' => 'Sony',
                'expected_category' => 'Gaming',
            ],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($testProducts as $i => $test) {
            $num = $i + 1;
            $this->info("TEST {$num}/3: {$test['store']}");
            $this->line("URL: {$test['url']}");

            try {
                $response = Http::timeout(15)->withUserAgent('Mozilla/5.0')->get($test['url']);

                if (! $response->successful()) {
                    throw new \Exception("HTTP {$response->status()}");
                }

                $html = $response->body();
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new \DOMXPath($dom);

                $titleNodes = $xpath->query('//span[@id="productTitle"]');
                $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : null;

                $priceNodes = $xpath->query('//span[@class="a-price-whole"]');
                $price = 0.0;
                if ($priceNodes->length > 0) {
                    $price = (float) preg_replace('/[^0-9.]/', '', $priceNodes->item(0)->textContent);
                }

                $imgNodes = $xpath->query('//img[@id="landingImage"]/@src');
                $image = $imgNodes->length > 0 ? preg_replace('/\._[^.]+_\./', '.', $imgNodes->item(0)->nodeValue) : '';

                if ($title && $price > 0 && $image) {
                    $this->line('   ✅ Title: '.substr($title, 0, 50).'...');
                    $this->line("   ✅ Price: {$price} EGP");
                    $this->line('   ✅ Image: YES');

                    $category = Category::firstOrCreate(['name' => $test['expected_category']], ['slug' => \Str::slug($test['expected_category'])]);
                    $brand = Brand::firstOrCreate(['name' => $test['expected_brand']], ['slug' => \Str::slug($test['expected_brand'])]);
                    $store = Store::firstOrCreate(['name' => $test['store']], ['url' => 'https://www.amazon.eg', 'slug' => 'amazon-egypt']);

                    $product = Product::updateOrCreate(
                        ['url' => $test['url']],
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
                    $this->info('   ✅ TEST PASSED');
                    ++$passed;
                } else {
                    throw new \Exception('Missing data');
                }
            } catch (\Exception $e) {
                $this->error('   ❌ TEST FAILED: '.$e->getMessage());
                ++$failed;
            }

            $this->newLine();
            sleep(2);
        }

        $this->line(str_repeat('=', 80));
        $this->info('📊 TEST RESULTS');
        $this->line(str_repeat('=', 80));
        $this->info("✅ Passed: {$passed}/3");
        $this->error("❌ Failed: {$failed}/3");
        $this->info('📈 Success Rate: '.number_format(($passed / 3) * 100, 2).'%');
        $this->line(str_repeat('=', 80));

        if (3 === $passed) {
            $this->info('🎉 ALL TESTS PASSED!');
        }

        $this->info('🎯 View: https://coprra.com/admin/products');

        return 3 === $passed ? 0 : 1;
    }
}
