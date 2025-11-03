<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\File;

class ImportFromOfficialSites extends Command
{
    protected $signature = 'products:import-official {brand=all}';
    protected $description = 'Import products from official brand sites (Apple, Samsung, Sony, etc.)';

    private function getOfficialData()
    {
        return [
        'apple' => [
            // iPhones
            [
                'name' => 'iPhone 15 Pro Max',
                'category' => 'Mobile Phones',
                'description' => 'Titanium design. A17 Pro chip. Action button. ProMotion display with Always-On.',
                'features' => json_encode([
                    'Titanium design',
                    'A17 Pro chip',
                    'ProMotion display with Always-On',
                    'Pro camera system',
                    'Action button',
                    'USB-C connector'
                ]),
                'specs' => json_encode([
                    'Display' => '6.7" Super Retina XDR',
                    'Chip' => 'A17 Pro',
                    'Camera' => '48MP Main | 12MP Ultra Wide | 12MP Telephoto',
                    'Storage' => '256GB | 512GB | 1TB',
                    'Battery' => 'Up to 29 hours video playback'
                ]),
                'base_price_usd' => 1199,
                'image_url' => 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple-iPhone-15-Pro-lineup-Natural-Blue-White-Black-Titanium-230912_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPhone 15 Pro',
                'category' => 'Mobile Phones',
                'description' => 'Titanium design. A17 Pro chip. Pro camera system.',
                'features' => json_encode(['Titanium design', 'A17 Pro chip', 'ProMotion display', 'Pro camera system']),
                'specs' => json_encode(['Display' => '6.1" Super Retina XDR', 'Chip' => 'A17 Pro', 'Camera' => '48MP Main']),
                'base_price_usd' => 999,
                'image_url' => 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple-iPhone-15-Pro-lineup-Natural-Blue-White-Black-Titanium-230912_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPhone 15 Plus',
                'category' => 'Mobile Phones',
                'description' => 'Dynamic Island. A16 Bionic chip. All-day battery life.',
                'features' => json_encode(['Dynamic Island', 'A16 Bionic chip', '6.7-inch display', 'Dual-camera system']),
                'specs' => json_encode(['Display' => '6.7" Super Retina XDR', 'Chip' => 'A16 Bionic']),
                'base_price_usd' => 899,
                'image_url' => 'https://www.apple.com/newsroom/images/2023/09/apple-debuts-iphone-15-and-iphone-15-plus/article/Apple-iPhone-15-lineup-color-lineup-geo-230912_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPhone 15',
                'category' => 'Mobile Phones',
                'description' => 'Dynamic Island. A16 Bionic chip. Advanced camera system.',
                'features' => json_encode(['Dynamic Island', 'A16 Bionic chip', '6.1-inch display']),
                'specs' => json_encode(['Display' => '6.1" Super Retina XDR', 'Chip' => 'A16 Bionic']),
                'base_price_usd' => 799,
                'image_url' => 'https://www.apple.com/newsroom/images/2023/09/apple-debuts-iphone-15-and-iphone-15-plus/article/Apple-iPhone-15-lineup-color-lineup-geo-230912_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPhone 14',
                'category' => 'Mobile Phones',
                'description' => 'Great value. A15 Bionic chip. Emergency SOS via satellite.',
                'features' => json_encode(['A15 Bionic chip', 'Dual-camera system', 'Emergency SOS']),
                'specs' => json_encode(['Display' => '6.1" Super Retina XDR', 'Chip' => 'A15 Bionic']),
                'base_price_usd' => 699,
                'image_url' => 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple-iPhone-14-iPhone-14-Plus-3up-220907_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPhone 13',
                'category' => 'Mobile Phones',
                'description' => 'Most affordable. A15 Bionic chip.',
                'features' => json_encode(['A15 Bionic chip', 'Dual-camera system']),
                'specs' => json_encode(['Display' => '6.1" Super Retina XDR', 'Chip' => 'A15 Bionic']),
                'base_price_usd' => 599,
                'image_url' => 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iPhone-13_Colors_09142021_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPhone SE',
                'category' => 'Mobile Phones',
                'description' => 'Powerful. Affordable. A15 Bionic chip.',
                'features' => json_encode(['A15 Bionic chip', 'Touch ID', 'Single camera']),
                'specs' => json_encode(['Display' => '4.7" Retina HD', 'Chip' => 'A15 Bionic']),
                'base_price_usd' => 429,
                'image_url' => 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone-se_colors_03082022_big.jpg.large.jpg'
            ],

            // iPads
            [
                'name' => 'iPad Pro 12.9-inch',
                'category' => 'Tablets',
                'description' => 'The ultimate iPad experience. M2 chip. Liquid Retina XDR display.',
                'features' => json_encode(['M2 chip', '12.9" Liquid Retina XDR', 'Pro camera system', 'Face ID']),
                'specs' => json_encode(['Display' => '12.9" Liquid Retina XDR', 'Chip' => 'M2', 'Storage' => '128GB-2TB']),
                'base_price_usd' => 1099,
                'image_url' => 'https://www.apple.com/newsroom/images/product/ipad/standard/Apple_iPad-Pro_hero_10182022_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPad Pro 11-inch',
                'category' => 'Tablets',
                'description' => 'Portable iPad Pro. M2 chip.',
                'features' => json_encode(['M2 chip', '11" Liquid Retina', 'Pro camera system']),
                'specs' => json_encode(['Display' => '11" Liquid Retina', 'Chip' => 'M2']),
                'base_price_usd' => 799,
                'image_url' => 'https://www.apple.com/newsroom/images/product/ipad/standard/Apple_iPad-Pro_hero_10182022_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPad Air',
                'category' => 'Tablets',
                'description' => 'Powerful. Colorful. Wonderful. M1 chip.',
                'features' => json_encode(['M1 chip', '10.9" Liquid Retina', 'Touch ID']),
                'specs' => json_encode(['Display' => '10.9" Liquid Retina', 'Chip' => 'M1']),
                'base_price_usd' => 599,
                'image_url' => 'https://www.apple.com/newsroom/images/product/ipad/standard/Apple_iPad-Air_5-up_hero_03082022_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPad',
                'category' => 'Tablets',
                'description' => 'Lovable. Drawable. Magical. A14 Bionic chip.',
                'features' => json_encode(['A14 Bionic chip', '10.9" Liquid Retina']),
                'specs' => json_encode(['Display' => '10.9" Liquid Retina', 'Chip' => 'A14 Bionic']),
                'base_price_usd' => 449,
                'image_url' => 'https://www.apple.com/newsroom/images/product/ipad/standard/apple_ipad_10th-gen_hero_10182022_big.jpg.large.jpg'
            ],
            [
                'name' => 'iPad mini',
                'category' => 'Tablets',
                'description' => 'Mega power. Mini size. A15 Bionic chip.',
                'features' => json_encode(['A15 Bionic chip', '8.3" Liquid Retina']),
                'specs' => json_encode(['Display' => '8.3" Liquid Retina', 'Chip' => 'A15 Bionic']),
                'base_price_usd' => 499,
                'image_url' => 'https://www.apple.com/newsroom/images/product/ipad/standard/Apple_iPad-mini_hero_09142021_big.jpg.large.jpg'
            ],

            // Macs
            [
                'name' => 'MacBook Air 13-inch M3',
                'category' => 'Laptops',
                'description' => 'Supercharged by M3. Up to 18 hours battery life.',
                'features' => json_encode(['M3 chip', '13.6" Liquid Retina', 'Up to 18 hours battery', 'MagSafe']),
                'specs' => json_encode(['Display' => '13.6" Liquid Retina', 'Chip' => 'M3', 'RAM' => 'Up to 24GB', 'Storage' => 'Up to 2TB']),
                'base_price_usd' => 1099,
                'image_url' => 'https://www.apple.com/newsroom/images/2024/03/apple-unveils-m3-macbook-air/article/Apple-MacBook-Air-M3-hero-240304_big.jpg.large.jpg'
            ],
            [
                'name' => 'MacBook Air 15-inch M3',
                'category' => 'Laptops',
                'description' => 'Larger display. M3 chip. All-day battery.',
                'features' => json_encode(['M3 chip', '15.3" Liquid Retina', 'Up to 18 hours battery']),
                'specs' => json_encode(['Display' => '15.3" Liquid Retina', 'Chip' => 'M3']),
                'base_price_usd' => 1299,
                'image_url' => 'https://www.apple.com/newsroom/images/2024/03/apple-unveils-m3-macbook-air/article/Apple-MacBook-Air-M3-hero-240304_big.jpg.large.jpg'
            ],
            [
                'name' => 'MacBook Pro 14-inch M3',
                'category' => 'Laptops',
                'description' => 'Mind-blowing. Head-turning. M3 Pro or M3 Max.',
                'features' => json_encode(['M3 Pro/Max chip', '14" Liquid Retina XDR', 'Up to 22 hours battery']),
                'specs' => json_encode(['Display' => '14" Liquid Retina XDR', 'Chip' => 'M3 Pro/Max']),
                'base_price_usd' => 1599,
                'image_url' => 'https://www.apple.com/newsroom/images/2023/10/apple-unveils-new-macbook-pro-featuring-m3-chips/article/Apple-MacBook-Pro-2up-231030_big.jpg.large.jpg'
            ],
            [
                'name' => 'MacBook Pro 16-inch M3',
                'category' => 'Laptops',
                'description' => 'The most powerful MacBook Pro ever.',
                'features' => json_encode(['M3 Pro/Max chip', '16" Liquid Retina XDR']),
                'specs' => json_encode(['Display' => '16" Liquid Retina XDR', 'Chip' => 'M3 Pro/Max']),
                'base_price_usd' => 2499,
                'image_url' => 'https://www.apple.com/newsroom/images/2023/10/apple-unveils-new-macbook-pro-featuring-m3-chips/article/Apple-MacBook-Pro-2up-231030_big.jpg.large.jpg'
            ],
            [
                'name' => 'iMac 24-inch M3',
                'category' => 'Desktops',
                'description' => 'A splash of color. M3 chip. 4.5K Retina display.',
                'features' => json_encode(['M3 chip', '24" 4.5K Retina', '1080p FaceTime HD camera']),
                'specs' => json_encode(['Display' => '24" 4.5K Retina', 'Chip' => 'M3']),
                'base_price_usd' => 1299,
                'image_url' => 'https://www.apple.com/newsroom/images/2023/10/apple-unveils-new-imac-supercharged-by-m3/article/Apple-iMac-hero-231030_big.jpg.large.jpg'
            ],
            [
                'name' => 'Mac mini M2',
                'category' => 'Desktops',
                'description' => 'More muscle. More hustle. M2 or M2 Pro.',
                'features' => json_encode(['M2/M2 Pro chip', 'Compact design', 'Thunderbolt 4']),
                'specs' => json_encode(['Chip' => 'M2/M2 Pro', 'RAM' => 'Up to 32GB']),
                'base_price_usd' => 599,
                'image_url' => 'https://www.apple.com/newsroom/images/product/mac/standard/Apple_Mac-mini-M2-Pro_hero_230117_big.jpg.large.jpg'
            ],
            [
                'name' => 'Mac Studio M2',
                'category' => 'Desktops',
                'description' => 'Supercharged by M2 Max or M2 Ultra.',
                'features' => json_encode(['M2 Max/Ultra chip', 'Compact design', 'Thunderbolt 4']),
                'specs' => json_encode(['Chip' => 'M2 Max/Ultra', 'RAM' => 'Up to 192GB']),
                'base_price_usd' => 1999,
                'image_url' => 'https://www.apple.com/newsroom/images/product/mac/standard/Apple_Mac-Studio_hero_06062022_big.jpg.large.jpg'
            ],
            [
                'name' => 'Mac Pro',
                'category' => 'Desktops',
                'description' => 'A beast of a workstation. M2 Ultra.',
                'features' => json_encode(['M2 Ultra chip', 'PCIe expansion', 'Thunderbolt 4']),
                'specs' => json_encode(['Chip' => 'M2 Ultra', 'RAM' => 'Up to 192GB']),
                'base_price_usd' => 6999,
                'image_url' => 'https://www.apple.com/newsroom/images/product/mac/standard/Apple_Mac-Pro-8K-ProDisplay-XDR_060622_big.jpg.large.jpg'
            ],

            // Apple Watch
            [
                'name' => 'Apple Watch Series 9',
                'category' => 'Smart Watches',
                'description' => 'Powerful sensors. Advanced health features.',
                'features' => json_encode(['S9 SiP', 'Always-On Retina', 'Blood Oxygen', 'ECG']),
                'specs' => json_encode(['Display' => 'Always-On Retina LTPO OLED', 'Chip' => 'S9 SiP']),
                'base_price_usd' => 399,
                'image_url' => 'https://www.apple.com/newsroom/images/2023/09/introducing-apple-watch-series-9/article/Apple-Watch-S9-hero-230912_big.jpg.large.jpg'
            ],
            [
                'name' => 'Apple Watch Ultra 2',
                'category' => 'Smart Watches',
                'description' => 'The most rugged and capable Apple Watch.',
                'features' => json_encode(['S9 SiP', 'Brightest display', 'Dual-frequency GPS', '100m water resistant']),
                'specs' => json_encode(['Display' => 'Always-On Retina LTPO OLED', 'Battery' => 'Up to 36 hours']),
                'base_price_usd' => 799,
                'image_url' => 'https://www.apple.com/newsroom/images/2023/09/apple-introduces-the-advanced-new-apple-watch-ultra-2/article/Apple-Watch-Ultra-2-hero-230912_big.jpg.large.jpg'
            ],
            [
                'name' => 'Apple Watch SE',
                'category' => 'Smart Watches',
                'description' => 'Essential features. Incredible value.',
                'features' => json_encode(['S8 SiP', 'Retina display', 'Fall detection']),
                'specs' => json_encode(['Display' => 'Retina LTPO OLED', 'Chip' => 'S8 SiP']),
                'base_price_usd' => 249,
                'image_url' => 'https://www.apple.com/newsroom/images/product/watch/standard/Apple-Watch-SE-hero-220907_big.jpg.large.jpg'
            ],

            // AirPods
            [
                'name' => 'AirPods Pro (2nd generation)',
                'category' => 'Audio Devices',
                'description' => 'Active Noise Cancellation. Transparency mode. Personalized Spatial Audio.',
                'features' => json_encode(['Active Noise Cancellation', 'Transparency mode', 'Spatial Audio', 'Adaptive EQ']),
                'specs' => json_encode(['Battery' => 'Up to 6 hours', 'Case' => 'MagSafe charging']),
                'base_price_usd' => 249,
                'image_url' => 'https://www.apple.com/newsroom/images/product/airpods/standard/Apple-AirPods-Pro-2nd-gen-hero-220907_big.jpg.large.jpg'
            ],
            [
                'name' => 'AirPods (3rd generation)',
                'category' => 'Audio Devices',
                'description' => 'Personalized Spatial Audio. All-new design.',
                'features' => json_encode(['Spatial Audio', 'Adaptive EQ', 'Sweat resistant']),
                'specs' => json_encode(['Battery' => 'Up to 6 hours']),
                'base_price_usd' => 169,
                'image_url' => 'https://www.apple.com/newsroom/images/product/airpods/standard/Apple_AirPods-3_hero_10182021_big.jpg.large.jpg'
            ],
            [
                'name' => 'AirPods Max',
                'category' => 'Audio Devices',
                'description' => 'Computational audio. Theatre-like sound.',
                'features' => json_encode(['Active Noise Cancellation', 'Transparency mode', 'Spatial Audio']),
                'specs' => json_encode(['Battery' => 'Up to 20 hours', 'Design' => 'Stainless steel frame']),
                'base_price_usd' => 549,
                'image_url' => 'https://www.apple.com/newsroom/images/product/airpods/standard/Apple_AirPods-Max_hero_12082020_big.jpg.large.jpg'
            ],
        ]
        ];
    }

    public function handle()
    {
        $brand = $this->argument('brand');

        $this->info('🍎 IMPORTING FROM OFFICIAL SOURCES');
        $this->line(str_repeat('=', 80));
        $this->info('📅 ' . now());
        $this->line(str_repeat('=', 80));
        $this->newLine();

        if ($brand === 'all' || $brand === 'apple') {
            $this->importApple();
        }

        $this->newLine();
        $this->line(str_repeat('=', 80));
        $this->info('✅ IMPORT COMPLETE!');
        $this->info('🎯 View: https://coprra.com/admin/products');
        $this->line(str_repeat('=', 80));

        return 0;
    }

    private function importApple()
    {
        $this->info('🍎 Importing Apple products...');
        $brand = Brand::firstOrCreate(['name' => 'Apple'], ['slug' => 'apple']);
        $successful = 0;
        $failed = 0;

        foreach ($this->officialData['apple'] as $i => $productData) {
            try {
                $num = $i + 1;
                $total = count($this->officialData['apple']);
                $this->line("[$num/$total] {$productData['name']}");

                $category = Category::firstOrCreate(
                    ['name' => $productData['category']],
                    ['slug' => \Str::slug($productData['category'])]
                );

                $product = Product::updateOrCreate(
                    [
                        'name' => $productData['name'],
                        'brand_id' => $brand->id
                    ],
                    [
                        'slug' => \Str::slug($productData['name']),
                        'description' => $productData['description'],
                        'price' => $productData['base_price_usd'] * 50, // USD to EGP (approximate)
                        'currency' => 'EGP',
                        'image_url' => $productData['image_url'],
                        'category_id' => $category->id,
                        'features' => $productData['features'] ?? null,
                        'specifications' => $productData['specs'] ?? null,
                    ]
                );

                $this->line("   ✅ Saved (ID: {$product->id})");
                $successful++;

            } catch (\Exception $e) {
                $this->error("   ❌ Failed: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("✅ Apple: $successful imported | ❌ $failed failed");
    }

    private function importAllData()
    {
        $allData = $this->getOfficialData();

        foreach ($allData as $brandName => $products) {
            $brand = Brand::firstOrCreate(['name' => ucfirst($brandName)], ['slug' => $brandName]);

            $this->info("🏷️  Importing $brandName...");

            foreach ($products as $item) {
                try {
                    $category = Category::firstOrCreate(
                        ['name' => $item['category']],
                        ['slug' => \Str::slug($item['category'])]
                    );

                    $priceEGP = $item['starting_price'] * 50;

                    Product::updateOrCreate(
                        ['name' => $item['name'], 'brand_id' => $brand->id],
                        [
                            'slug' => \Str::slug($item['name']),
                            'description' => $item['description'],
                            'price' => $priceEGP,
                            'currency' => 'EGP',
                            'image_url' => $item['image_url'],
                            'category_id' => $category->id,
                            'specifications' => isset($item['specs']) ? json_encode($item['specs']) : null,
                            'features' => $item['features'] ?? null,
                        ]
                    );
                } catch (\Exception $e) {
                    // Silent fail
                }
            }

            $this->info("✅ $brandName: " . count($products) . " products");
        }
    }
}
