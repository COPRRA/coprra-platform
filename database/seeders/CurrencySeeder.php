<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // Strategic E-commerce Currencies (v3.0 - Final Approved List - 27 Currencies)
        $currencies = [
            // English-speaking markets
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.0000, 'is_default' => true, 'sort_order' => 1],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => 0.79, 'is_default' => false, 'sort_order' => 2],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$', 'exchange_rate' => 1.37, 'is_default' => false, 'sort_order' => 3],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'exchange_rate' => 1.52, 'is_default' => false, 'sort_order' => 4],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'exchange_rate' => 83.12, 'is_default' => false, 'sort_order' => 5],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'exchange_rate' => 3.67, 'is_default' => false, 'sort_order' => 6],

            // Chinese markets
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'exchange_rate' => 7.24, 'is_default' => false, 'sort_order' => 7],
            ['code' => 'TWD', 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$', 'exchange_rate' => 31.50, 'is_default' => false, 'sort_order' => 8],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'exchange_rate' => 1.34, 'is_default' => false, 'sort_order' => 9],

            // European markets
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.92, 'is_default' => false, 'sort_order' => 10],

            // Spanish-speaking markets
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => 'MX$', 'exchange_rate' => 17.15, 'is_default' => false, 'sort_order' => 11],
            ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => 'ARS$', 'exchange_rate' => 825.00, 'is_default' => false, 'sort_order' => 12],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => 'COL$', 'exchange_rate' => 3950.00, 'is_default' => false, 'sort_order' => 13],

            // Arabic-speaking markets
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'ر.س', 'exchange_rate' => 3.75, 'is_default' => false, 'sort_order' => 14],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E£', 'exchange_rate' => 49.50, 'is_default' => false, 'sort_order' => 15],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'ر.ق', 'exchange_rate' => 3.64, 'is_default' => false, 'sort_order' => 16],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك', 'exchange_rate' => 0.31, 'is_default' => false, 'sort_order' => 17],
            ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'د.م.', 'exchange_rate' => 10.05, 'is_default' => false, 'sort_order' => 18],

            // Portuguese-speaking markets
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$', 'exchange_rate' => 5.02, 'is_default' => false, 'sort_order' => 19],

            // French/German/Swiss markets
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF', 'exchange_rate' => 0.88, 'is_default' => false, 'sort_order' => 20],

            // Asian markets
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'exchange_rate' => 149.50, 'is_default' => false, 'sort_order' => 21],
            ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽', 'exchange_rate' => 96.50, 'is_default' => false, 'sort_order' => 22],
            ['code' => 'KZT', 'name' => 'Kazakhstani Tenge', 'symbol' => '₸', 'exchange_rate' => 475.00, 'is_default' => false, 'sort_order' => 23],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩', 'exchange_rate' => 1325.00, 'is_default' => false, 'sort_order' => 24],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺', 'exchange_rate' => 34.20, 'is_default' => false, 'sort_order' => 25],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'exchange_rate' => 15750.00, 'is_default' => false, 'sort_order' => 26],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'exchange_rate' => 4.68, 'is_default' => false, 'sort_order' => 27],
        ];

        DB::table('currencies')->insert(array_map(static function ($currency) {
            return array_merge($currency, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, $currencies));
    }
}
