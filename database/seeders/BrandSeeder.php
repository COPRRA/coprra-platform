<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏢 Seeding brands...');

        $brands = $this->getBrandsData();
        $createdCount = 0;

        foreach ($brands as $brandData) {
            $brand = Brand::firstOrCreate(
                ['slug' => $brandData['slug']],
                $brandData
            );

            if ($brand->wasRecentlyCreated) {
                ++$createdCount;
            }
        }

        $this->command->info("Brands seeded successfully! Created {$createdCount} new brands.");
    }

    /**
     * Get the brands data for seeding.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getBrandsData(): array
    {
        return [
            // Technology Brands
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'description' => 'American multinational technology company',
                'logo_url' => 'https://logo.clearbit.com/apple.com',
                'website_url' => 'https://www.apple.com',
                'is_active' => true,
            ],
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'description' => 'South Korean multinational electronics company',
                'logo_url' => 'https://logo.clearbit.com/samsung.com',
                'website_url' => 'https://www.samsung.com',
                'is_active' => true,
            ],
            [
                'name' => 'Google',
                'slug' => 'google',
                'description' => 'American multinational technology company',
                'logo_url' => 'https://logo.clearbit.com/google.com',
                'website_url' => 'https://www.google.com',
                'is_active' => true,
            ],
            [
                'name' => 'Microsoft',
                'slug' => 'microsoft',
                'description' => 'American multinational technology corporation',
                'logo_url' => 'https://logo.clearbit.com/microsoft.com',
                'website_url' => 'https://www.microsoft.com',
                'is_active' => true,
            ],
            [
                'name' => 'Sony',
                'slug' => 'sony',
                'description' => 'Japanese multinational conglomerate corporation',
                'logo_url' => 'https://logo.clearbit.com/sony.com',
                'website_url' => 'https://www.sony.com',
                'is_active' => true,
            ],
            [
                'name' => 'LG',
                'slug' => 'lg',
                'description' => 'South Korean multinational electronics company',
                'logo_url' => 'https://logo.clearbit.com/lg.com',
                'website_url' => 'https://www.lg.com',
                'is_active' => true,
            ],
            [
                'name' => 'HP',
                'slug' => 'hp',
                'description' => 'American multinational information technology company',
                'logo_url' => 'https://logo.clearbit.com/hp.com',
                'website_url' => 'https://www.hp.com',
                'is_active' => true,
            ],
            [
                'name' => 'Dell',
                'slug' => 'dell',
                'description' => 'American multinational computer technology company',
                'logo_url' => 'https://logo.clearbit.com/dell.com',
                'website_url' => 'https://www.dell.com',
                'is_active' => true,
            ],
            [
                'name' => 'Lenovo',
                'slug' => 'lenovo',
                'description' => 'Chinese multinational technology company',
                'logo_url' => 'https://logo.clearbit.com/lenovo.com',
                'website_url' => 'https://www.lenovo.com',
                'is_active' => true,
            ],
            [
                'name' => 'ASUS',
                'slug' => 'asus',
                'description' => 'Taiwanese multinational computer hardware company',
                'logo_url' => 'https://logo.clearbit.com/asus.com',
                'website_url' => 'https://www.asus.com',
                'is_active' => true,
            ],

            // Fashion Brands
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'American multinational corporation engaged in footwear and apparel',
                'logo_url' => 'https://logo.clearbit.com/nike.com',
                'website_url' => 'https://www.nike.com',
                'is_active' => true,
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'German multinational corporation that designs and manufactures shoes',
                'logo_url' => 'https://logo.clearbit.com/adidas.com',
                'website_url' => 'https://www.adidas.com',
                'is_active' => true,
            ],
            [
                'name' => 'Puma',
                'slug' => 'puma',
                'description' => 'German multinational corporation that designs athletic and casual footwear',
                'logo_url' => 'https://logo.clearbit.com/puma.com',
                'website_url' => 'https://www.puma.com',
                'is_active' => true,
            ],
            [
                'name' => 'Under Armour',
                'slug' => 'under-armour',
                'description' => 'American sports equipment company',
                'logo_url' => 'https://logo.clearbit.com/underarmour.com',
                'website_url' => 'https://www.underarmour.com',
                'is_active' => true,
            ],
            [
                'name' => 'Zara',
                'slug' => 'zara',
                'description' => 'Spanish apparel retailer',
                'logo_url' => 'https://logo.clearbit.com/zara.com',
                'website_url' => 'https://www.zara.com',
                'is_active' => true,
            ],
            [
                'name' => 'H&M',
                'slug' => 'hm',
                'description' => 'Swedish multinational clothing-retail company',
                'logo_url' => 'https://logo.clearbit.com/hm.com',
                'website_url' => 'https://www.hm.com',
                'is_active' => true,
            ],

            // Home & Kitchen Brands
            [
                'name' => 'IKEA',
                'slug' => 'ikea',
                'description' => 'Swedish-founded multinational conglomerate that designs and sells furniture',
                'logo_url' => 'https://logo.clearbit.com/ikea.com',
                'website_url' => 'https://www.ikea.com',
                'is_active' => true,
            ],
            [
                'name' => 'KitchenAid',
                'slug' => 'kitchenaid',
                'description' => 'American home appliance brand',
                'logo_url' => 'https://logo.clearbit.com/kitchenaid.com',
                'website_url' => 'https://www.kitchenaid.com',
                'is_active' => true,
            ],
            [
                'name' => 'Cuisinart',
                'slug' => 'cuisinart',
                'description' => 'American home appliance brand',
                'logo_url' => 'https://logo.clearbit.com/cuisinart.com',
                'website_url' => 'https://www.cuisinart.com',
                'is_active' => true,
            ],
            [
                'name' => 'Dyson',
                'slug' => 'dyson',
                'description' => 'British technology company that designs and manufactures household appliances',
                'logo_url' => 'https://logo.clearbit.com/dyson.com',
                'website_url' => 'https://www.dyson.com',
                'is_active' => true,
            ],

            // Automotive Brands
            [
                'name' => 'Toyota',
                'slug' => 'toyota',
                'description' => 'Japanese multinational automotive manufacturer',
                'logo_url' => 'https://logo.clearbit.com/toyota.com',
                'website_url' => 'https://www.toyota.com',
                'is_active' => true,
            ],
            [
                'name' => 'Honda',
                'slug' => 'honda',
                'description' => 'Japanese public multinational conglomerate manufacturer',
                'logo_url' => 'https://logo.clearbit.com/honda.com',
                'website_url' => 'https://www.honda.com',
                'is_active' => true,
            ],
            [
                'name' => 'BMW',
                'slug' => 'bmw',
                'description' => 'German multinational corporation which produces automobiles',
                'logo_url' => 'https://logo.clearbit.com/bmw.com',
                'website_url' => 'https://www.bmw.com',
                'is_active' => true,
            ],
            [
                'name' => 'Mercedes-Benz',
                'slug' => 'mercedes-benz',
                'description' => 'German global automobile marque and a division of Daimler AG',
                'logo_url' => 'https://logo.clearbit.com/mercedes-benz.com',
                'website_url' => 'https://www.mercedes-benz.com',
                'is_active' => true,
            ],

            // Generic/Other Brands
            [
                'name' => 'Generic',
                'slug' => 'generic',
                'description' => 'Generic brand for unbranded products',
                'logo_url' => null,
                'website_url' => null,
                'is_active' => true,
            ],
            [
                'name' => 'No Brand',
                'slug' => 'no-brand',
                'description' => 'Products without specific brand',
                'logo_url' => null,
                'website_url' => null,
                'is_active' => true,
            ],
        ];
    }
}
