<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class AdditionalCurrenciesSeeder extends Seeder
{
    /**
     * Seed additional currencies needed for the Top 25 Languages dataset.
     */
    public function run(): void
    {
        $currencies = [
            // Missing currencies from the dataset
            ['ZAR', 'South African Rand', 'R', 50],
            ['IQD', 'Iraqi Dinar', 'ع.د', 51],
            ['DZD', 'Algerian Dinar', 'د.ج', 52],
            ['SDG', 'Sudanese Pound', 'ج.س', 53],
            ['YER', 'Yemeni Rial', 'ر.ي', 54],
            ['SYP', 'Syrian Pound', 'ل.س', 55],
            ['JOD', 'Jordanian Dinar', 'د.ا', 56],
            ['TND', 'Tunisian Dinar', 'د.ت', 57],
            ['LYD', 'Libyan Dinar', 'ل.د', 58],
            ['LBP', 'Lebanese Pound', 'ل.ل', 59],
            ['ILS', 'Israeli Shekel', '₪', 60],
            ['OMR', 'Omani Rial', 'ر.ع.', 61],
            ['BHD', 'Bahraini Dinar', 'د.ب', 62],
            ['MRU', 'Mauritanian Ouguiya', 'UM', 63],
            ['DJF', 'Djiboutian Franc', 'Fdj', 64],
            ['KMF', 'Comorian Franc', 'CF', 65],
            ['PEN', 'Peruvian Sol', 'S/', 66],
            ['VES', 'Venezuelan Bolívar', 'Bs.S', 67],
            ['CLP', 'Chilean Peso', '$', 68],
            ['GTQ', 'Guatemalan Quetzal', 'Q', 69],
            ['CUP', 'Cuban Peso', '$', 70],
            ['BOB', 'Bolivian Boliviano', 'Bs', 71],
            ['DOP', 'Dominican Peso', 'RD$', 72],
            ['HNL', 'Honduran Lempira', 'L', 73],
            ['PYG', 'Paraguayan Guaraní', '₲', 74],
            ['NIO', 'Nicaraguan Córdoba', 'C$', 75],
            ['CRC', 'Costa Rican Colón', '₡', 76],
            ['PAB', 'Panamanian Balboa', 'B/.', 77],
            ['UYU', 'Uruguayan Peso', '$U', 78],
            ['XAF', 'Central African CFA Franc', 'FCFA', 79],
            ['HKD', 'Hong Kong Dollar', 'HK$', 80],
            ['MOP', 'Macanese Pataca', 'MOP$', 81],
            ['NPR', 'Nepalese Rupee', 'रू', 82],
            ['FJD', 'Fijian Dollar', 'FJ$', 83],
            ['AOA', 'Angolan Kwanza', 'Kz', 84],
            ['MZN', 'Mozambican Metical', 'MT', 85],
            ['CDF', 'Congolese Franc', 'FC', 86],
            ['XOF', 'West African CFA Franc', 'CFA', 87],
            ['MGA', 'Malagasy Ariary', 'Ar', 88],
            ['RWF', 'Rwandan Franc', 'FRw', 89],
            ['GNF', 'Guinean Franc', 'FG', 90],
            ['HTG', 'Haitian Gourde', 'G', 91],
            ['BIF', 'Burundian Franc', 'FBu', 92],
            ['BYN', 'Belarusian Ruble', 'Br', 93],
            ['KGS', 'Kyrgyzstani Som', 'с', 94],
            ['KPW', 'North Korean Won', '₩', 95],
        ];

        foreach ($currencies as $index => $data) {
            [$code, $name, $symbol, $sortOrder] = $data;

            // Only create if doesn't exist
            Currency::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'symbol' => $symbol,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }

        $this->command->info('✅ Seeded ' . count($currencies) . ' additional currencies');
    }
}
