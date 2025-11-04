<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        // Strategic Global Languages for E-commerce (v3.0 - Final Approved List)
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => true, 'sort_order' => 1],
            ['code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 2],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 3],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_default' => false, 'sort_order' => 4],
            ['code' => 'hi', 'name' => 'Hindi', 'native_name' => 'हिन्दी', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 5],
            ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 6],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 7],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 8],
            ['code' => 'ja', 'name' => 'Japanese', 'native_name' => '日本語', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 9],
            ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 10],
            ['code' => 'ko', 'name' => 'Korean', 'native_name' => '한국어', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 11],
            ['code' => 'tr', 'name' => 'Turkish', 'native_name' => 'Türkçe', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 12],
            ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 13],
            ['code' => 'id', 'name' => 'Indonesian', 'native_name' => 'Bahasa Indonesia', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 14],
            ['code' => 'nl', 'name' => 'Dutch', 'native_name' => 'Nederlands', 'direction' => 'ltr', 'is_default' => false, 'sort_order' => 15],
        ];

        DB::table('languages')->insert(array_map(static function ($lang) {
            return array_merge($lang, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, $languages));
    }
}
