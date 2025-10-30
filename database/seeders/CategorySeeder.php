<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding categories...');

        $categories = $this->getCategoriesData();
        $createdCount = 0;

        foreach ($categories as $categoryData) {
            $category = Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );

            if ($category->wasRecentlyCreated) {
                ++$createdCount;
            }
        }

        $this->command->info("Categories seeded successfully! Created {$createdCount} new categories.");
    }

    /**
     * Get the categories data for seeding.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCategoriesData(): array
    {
        return [
            // Main Categories (Level 0)
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and gadgets',
                'parent_id' => null,
                'level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Fashion',
                'slug' => 'fashion',
                'description' => 'Clothing, shoes, and accessories',
                'parent_id' => null,
                'level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home improvement and garden supplies',
                'parent_id' => null,
                'level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Sports equipment and outdoor gear',
                'parent_id' => null,
                'level' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Books & Media',
                'slug' => 'books-media',
                'description' => 'Books, movies, music, and games',
                'parent_id' => null,
                'level' => 0,
                'is_active' => true,
            ],

            // Level 1 Categories (Electronics subcategories)
            [
                'name' => 'Smartphones',
                'slug' => 'smartphones',
                'description' => 'Mobile phones and smartphones',
                'parent_id' => 1, // Electronics ID
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Laptops',
                'slug' => 'laptops',
                'description' => 'Portable computers and laptops',
                'parent_id' => 1, // Electronics ID
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Tablets',
                'slug' => 'tablets',
                'description' => 'Tablet computers and e-readers',
                'parent_id' => 1, // Electronics ID
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Audio & Video',
                'slug' => 'audio-video',
                'description' => 'Audio and video equipment',
                'parent_id' => 1, // Electronics ID
                'level' => 1,
                'is_active' => true,
            ],

            // Level 1 Categories (Fashion subcategories)
            [
                'name' => 'Men\'s Fashion',
                'slug' => 'mens-fashion',
                'description' => 'Men\'s clothing and accessories',
                'parent_id' => 2, // Fashion ID
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Women\'s Fashion',
                'slug' => 'womens-fashion',
                'description' => 'Women\'s clothing and accessories',
                'parent_id' => 2, // Fashion ID
                'level' => 1,
                'is_active' => true,
            ],

            // Level 1 Categories (Home & Garden subcategories)
            [
                'name' => 'Furniture',
                'slug' => 'furniture',
                'description' => 'Home furniture',
                'parent_id' => 3, // Home & Garden ID
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Kitchen & Dining',
                'slug' => 'kitchen-dining',
                'description' => 'Kitchen and dining products',
                'parent_id' => 3, // Home & Garden ID
                'level' => 1,
                'is_active' => true,
            ],

            // Level 1 Categories (Sports & Outdoors subcategories)
            [
                'name' => 'Fitness',
                'slug' => 'fitness',
                'description' => 'Fitness equipment and accessories',
                'parent_id' => 4, // Sports & Outdoors ID
                'level' => 1,
                'is_active' => true,
            ],

            // Level 1 Categories (Books & Media subcategories)
            [
                'name' => 'Books',
                'slug' => 'books',
                'description' => 'Physical and digital books',
                'parent_id' => 5, // Books & Media ID
                'level' => 1,
                'is_active' => true,
            ],

            // Level 2 Categories (Smartphones subcategories)
            [
                'name' => 'Android Phones',
                'slug' => 'android-phones',
                'description' => 'Android smartphones',
                'parent_id' => 6, // Smartphones ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'iPhones',
                'slug' => 'iphones',
                'description' => 'Apple iPhones',
                'parent_id' => 6, // Smartphones ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Feature Phones',
                'slug' => 'feature-phones',
                'description' => 'Basic mobile phones',
                'parent_id' => 6, // Smartphones ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Laptops subcategories)
            [
                'name' => 'Gaming Laptops',
                'slug' => 'gaming-laptops',
                'description' => 'High-performance gaming laptops',
                'parent_id' => 7, // Laptops ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Business Laptops',
                'slug' => 'business-laptops',
                'description' => 'Professional business laptops',
                'parent_id' => 7, // Laptops ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Ultrabooks',
                'slug' => 'ultrabooks',
                'description' => 'Thin and light laptops',
                'parent_id' => 7, // Laptops ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Tablets subcategories)
            [
                'name' => 'iPad',
                'slug' => 'ipad',
                'description' => 'Apple iPads',
                'parent_id' => 8, // Tablets ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Android Tablets',
                'slug' => 'android-tablets',
                'description' => 'Android tablets',
                'parent_id' => 8, // Tablets ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'E-Readers',
                'slug' => 'e-readers',
                'description' => 'Electronic book readers',
                'parent_id' => 8, // Tablets ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Audio & Video subcategories)
            [
                'name' => 'Headphones',
                'slug' => 'headphones',
                'description' => 'Headphones and earphones',
                'parent_id' => 9, // Audio & Video ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Speakers',
                'slug' => 'speakers',
                'description' => 'Audio speakers',
                'parent_id' => 9, // Audio & Video ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'TVs',
                'slug' => 'tvs',
                'description' => 'Television sets',
                'parent_id' => 9, // Audio & Video ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Men's Fashion subcategories)
            [
                'name' => 'Men\'s Shirts',
                'slug' => 'mens-shirts',
                'description' => 'Men\'s shirts and tops',
                'parent_id' => 10, // Men's Fashion ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Men\'s Pants',
                'slug' => 'mens-pants',
                'description' => 'Men\'s trousers and jeans',
                'parent_id' => 10, // Men's Fashion ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Men\'s Shoes',
                'slug' => 'mens-shoes',
                'description' => 'Men\'s footwear',
                'parent_id' => 10, // Men's Fashion ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Women's Fashion subcategories)
            [
                'name' => 'Women\'s Dresses',
                'slug' => 'womens-dresses',
                'description' => 'Women\'s dresses',
                'parent_id' => 11, // Women's Fashion ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Women\'s Tops',
                'slug' => 'womens-tops',
                'description' => 'Women\'s shirts and blouses',
                'parent_id' => 11, // Women's Fashion ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Women\'s Shoes',
                'slug' => 'womens-shoes',
                'description' => 'Women\'s footwear',
                'parent_id' => 11, // Women's Fashion ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Furniture subcategories)
            [
                'name' => 'Living Room',
                'slug' => 'living-room-furniture',
                'description' => 'Living room furniture',
                'parent_id' => 12, // Furniture ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Bedroom',
                'slug' => 'bedroom-furniture',
                'description' => 'Bedroom furniture',
                'parent_id' => 12, // Furniture ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Office',
                'slug' => 'office-furniture',
                'description' => 'Office furniture',
                'parent_id' => 12, // Furniture ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Kitchen & Dining subcategories)
            [
                'name' => 'Cookware',
                'slug' => 'cookware',
                'description' => 'Pots, pans, and cooking utensils',
                'parent_id' => 13, // Kitchen & Dining ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Small Appliances',
                'slug' => 'small-appliances',
                'description' => 'Kitchen appliances',
                'parent_id' => 13, // Kitchen & Dining ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Dinnerware',
                'slug' => 'dinnerware',
                'description' => 'Plates, bowls, and dining accessories',
                'parent_id' => 13, // Kitchen & Dining ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Fitness subcategories)
            [
                'name' => 'Cardio Equipment',
                'slug' => 'cardio-equipment',
                'description' => 'Treadmills, bikes, and cardio machines',
                'parent_id' => 14, // Fitness ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Strength Training',
                'slug' => 'strength-training',
                'description' => 'Weights and strength equipment',
                'parent_id' => 14, // Fitness ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Yoga & Pilates',
                'slug' => 'yoga-pilates',
                'description' => 'Yoga mats and pilates equipment',
                'parent_id' => 14, // Fitness ID
                'level' => 2,
                'is_active' => true,
            ],

            // Level 2 Categories (Books subcategories)
            [
                'name' => 'Fiction',
                'slug' => 'fiction-books',
                'description' => 'Fiction books and novels',
                'parent_id' => 15, // Books ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Non-Fiction',
                'slug' => 'non-fiction-books',
                'description' => 'Non-fiction and educational books',
                'parent_id' => 15, // Books ID
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Children\'s Books',
                'slug' => 'childrens-books',
                'description' => 'Books for children',
                'parent_id' => 15, // Books ID
                'level' => 2,
                'is_active' => true,
            ],
        ];
    }
}
