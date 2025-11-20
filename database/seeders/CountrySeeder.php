<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // Get language IDs
        $enId = Language::where('code', 'en')->first()->id;
        $zhId = Language::where('code', 'zh')->first()->id;
        $esId = Language::where('code', 'es')->first()->id;
        $arId = Language::where('code', 'ar')->first()->id;
        $hiId = Language::where('code', 'hi')->first()->id;
        $ptId = Language::where('code', 'pt')->first()->id;
        $frId = Language::where('code', 'fr')->first()->id;
        $deId = Language::where('code', 'de')->first()->id;
        $jaId = Language::where('code', 'ja')->first()->id;
        $ruId = Language::where('code', 'ru')->first()->id;
        $koId = Language::where('code', 'ko')->first()->id;
        $trId = Language::where('code', 'tr')->first()->id;
        $itId = Language::where('code', 'it')->first()->id;
        $idId = Language::where('code', 'id')->first()->id;
        $nlId = Language::where('code', 'nl')->first()->id;

        // Get currency IDs
        $usd = Currency::where('code', 'USD')->first()->id;
        $gbp = Currency::where('code', 'GBP')->first()->id;
        $cad = Currency::where('code', 'CAD')->first()->id;
        $aud = Currency::where('code', 'AUD')->first()->id;
        $inr = Currency::where('code', 'INR')->first()->id;
        $aed = Currency::where('code', 'AED')->first()->id;
        $cny = Currency::where('code', 'CNY')->first()->id;
        $twd = Currency::where('code', 'TWD')->first()->id;
        $sgd = Currency::where('code', 'SGD')->first()->id;
        $eur = Currency::where('code', 'EUR')->first()->id;
        $mxn = Currency::where('code', 'MXN')->first()->id;
        $ars = Currency::where('code', 'ARS')->first()->id;
        $cop = Currency::where('code', 'COP')->first()->id;
        $sar = Currency::where('code', 'SAR')->first()->id;
        $egp = Currency::where('code', 'EGP')->first()->id;
        $qar = Currency::where('code', 'QAR')->first()->id;
        $kwd = Currency::where('code', 'KWD')->first()->id;
        $mad = Currency::where('code', 'MAD')->first()->id;
        $brl = Currency::where('code', 'BRL')->first()->id;
        $chf = Currency::where('code', 'CHF')->first()->id;
        $jpy = Currency::where('code', 'JPY')->first()->id;
        $rub = Currency::where('code', 'RUB')->first()->id;
        $kzt = Currency::where('code', 'KZT')->first()->id;
        $krw = Currency::where('code', 'KRW')->first()->id;
        $try = Currency::where('code', 'TRY')->first()->id;
        $idr = Currency::where('code', 'IDR')->first()->id;
        $myr = Currency::where('code', 'MYR')->first()->id;

        // Strategic Countries for Global E-commerce (v3.0 - Final Approved List)
        $countries = [
            // ============ ENGLISH-SPEAKING COUNTRIES (6) ============
            ['code' => 'US', 'name' => 'United States', 'native_name' => 'United States', 'language_id' => $enId, 'currency_id' => $usd, 'flag_emoji' => '🇺🇸', 'sort_order' => 1],
            ['code' => 'GB', 'name' => 'United Kingdom', 'native_name' => 'United Kingdom', 'language_id' => $enId, 'currency_id' => $gbp, 'flag_emoji' => '🇬🇧', 'sort_order' => 2],
            ['code' => 'CA', 'name' => 'Canada', 'native_name' => 'Canada', 'language_id' => $enId, 'currency_id' => $cad, 'flag_emoji' => '🇨🇦', 'sort_order' => 3],
            ['code' => 'AU', 'name' => 'Australia', 'native_name' => 'Australia', 'language_id' => $enId, 'currency_id' => $aud, 'flag_emoji' => '🇦🇺', 'sort_order' => 4],
            ['code' => 'IN', 'name' => 'India', 'native_name' => 'India', 'language_id' => $enId, 'currency_id' => $inr, 'flag_emoji' => '🇮🇳', 'sort_order' => 5],
            ['code' => 'AE', 'name' => 'UAE', 'native_name' => 'UAE', 'language_id' => $enId, 'currency_id' => $aed, 'flag_emoji' => '🇦🇪', 'sort_order' => 6],

            // ============ CHINESE-SPEAKING REGIONS (3) ============
            ['code' => 'CN', 'name' => 'China', 'native_name' => '中国', 'language_id' => $zhId, 'currency_id' => $cny, 'flag_emoji' => '🇨🇳', 'sort_order' => 7],
            ['code' => 'TW', 'name' => 'Taiwan', 'native_name' => '台灣', 'language_id' => $zhId, 'currency_id' => $twd, 'flag_emoji' => '🇹🇼', 'sort_order' => 8],
            ['code' => 'SG', 'name' => 'Singapore', 'native_name' => '新加坡', 'language_id' => $zhId, 'currency_id' => $sgd, 'flag_emoji' => '🇸🇬', 'sort_order' => 9],

            // ============ SPANISH-SPEAKING COUNTRIES (4) ============
            ['code' => 'ES', 'name' => 'Spain', 'native_name' => 'España', 'language_id' => $esId, 'currency_id' => $eur, 'flag_emoji' => '🇪🇸', 'sort_order' => 10],
            ['code' => 'MX', 'name' => 'Mexico', 'native_name' => 'México', 'language_id' => $esId, 'currency_id' => $mxn, 'flag_emoji' => '🇲🇽', 'sort_order' => 11],
            ['code' => 'AR', 'name' => 'Argentina', 'native_name' => 'Argentina', 'language_id' => $esId, 'currency_id' => $ars, 'flag_emoji' => '🇦🇷', 'sort_order' => 12],
            ['code' => 'CO', 'name' => 'Colombia', 'native_name' => 'Colombia', 'language_id' => $esId, 'currency_id' => $cop, 'flag_emoji' => '🇨🇴', 'sort_order' => 13],

            // ============ ARABIC-SPEAKING COUNTRIES (6) ============
            ['code' => 'SA', 'name' => 'Saudi Arabia', 'native_name' => 'السعودية', 'language_id' => $arId, 'currency_id' => $sar, 'flag_emoji' => '🇸🇦', 'sort_order' => 14],
            ['code' => 'EG', 'name' => 'Egypt', 'native_name' => 'مصر', 'language_id' => $arId, 'currency_id' => $egp, 'flag_emoji' => '🇪🇬', 'sort_order' => 15],
            // UAE already added for English, adding duplicate for Arabic
            ['code' => 'AE', 'name' => 'UAE', 'native_name' => 'الإمارات', 'language_id' => $arId, 'currency_id' => $aed, 'flag_emoji' => '🇦🇪', 'sort_order' => 16],
            ['code' => 'QA', 'name' => 'Qatar', 'native_name' => 'قطر', 'language_id' => $arId, 'currency_id' => $qar, 'flag_emoji' => '🇶🇦', 'sort_order' => 17],
            ['code' => 'KW', 'name' => 'Kuwait', 'native_name' => 'الكويت', 'language_id' => $arId, 'currency_id' => $kwd, 'flag_emoji' => '🇰🇼', 'sort_order' => 18],
            ['code' => 'MA', 'name' => 'Morocco', 'native_name' => 'المغرب', 'language_id' => $arId, 'currency_id' => $mad, 'flag_emoji' => '🇲🇦', 'sort_order' => 19],

            // ============ HINDI (1) ============
            // India already added for English, adding duplicate for Hindi
            ['code' => 'IN', 'name' => 'India', 'native_name' => 'भारत', 'language_id' => $hiId, 'currency_id' => $inr, 'flag_emoji' => '🇮🇳', 'sort_order' => 20],

            // ============ PORTUGUESE-SPEAKING COUNTRIES (2) ============
            ['code' => 'BR', 'name' => 'Brazil', 'native_name' => 'Brasil', 'language_id' => $ptId, 'currency_id' => $brl, 'flag_emoji' => '🇧🇷', 'sort_order' => 21],
            ['code' => 'PT', 'name' => 'Portugal', 'native_name' => 'Portugal', 'language_id' => $ptId, 'currency_id' => $eur, 'flag_emoji' => '🇵🇹', 'sort_order' => 22],

            // ============ FRENCH-SPEAKING COUNTRIES (4) ============
            ['code' => 'FR', 'name' => 'France', 'native_name' => 'France', 'language_id' => $frId, 'currency_id' => $eur, 'flag_emoji' => '🇫🇷', 'sort_order' => 23],
            // Canada already added for English, adding duplicate for French
            ['code' => 'CA', 'name' => 'Canada', 'native_name' => 'Canada', 'language_id' => $frId, 'currency_id' => $cad, 'flag_emoji' => '🇨🇦', 'sort_order' => 24],
            ['code' => 'BE', 'name' => 'Belgium', 'native_name' => 'Belgique', 'language_id' => $frId, 'currency_id' => $eur, 'flag_emoji' => '🇧🇪', 'sort_order' => 25],
            ['code' => 'CH', 'name' => 'Switzerland', 'native_name' => 'Suisse', 'language_id' => $frId, 'currency_id' => $chf, 'flag_emoji' => '🇨🇭', 'sort_order' => 26],

            // ============ GERMAN-SPEAKING COUNTRIES (3) ============
            ['code' => 'DE', 'name' => 'Germany', 'native_name' => 'Deutschland', 'language_id' => $deId, 'currency_id' => $eur, 'flag_emoji' => '🇩🇪', 'sort_order' => 27],
            ['code' => 'AT', 'name' => 'Austria', 'native_name' => 'Österreich', 'language_id' => $deId, 'currency_id' => $eur, 'flag_emoji' => '🇦🇹', 'sort_order' => 28],
            // Switzerland already added for French, adding duplicate for German
            ['code' => 'CH', 'name' => 'Switzerland', 'native_name' => 'Schweiz', 'language_id' => $deId, 'currency_id' => $chf, 'flag_emoji' => '🇨🇭', 'sort_order' => 29],

            // ============ JAPANESE (1) ============
            ['code' => 'JP', 'name' => 'Japan', 'native_name' => '日本', 'language_id' => $jaId, 'currency_id' => $jpy, 'flag_emoji' => '🇯🇵', 'sort_order' => 30],

            // ============ RUSSIAN-SPEAKING COUNTRIES (2) ============
            ['code' => 'RU', 'name' => 'Russia', 'native_name' => 'Россия', 'language_id' => $ruId, 'currency_id' => $rub, 'flag_emoji' => '🇷🇺', 'sort_order' => 31],
            ['code' => 'KZ', 'name' => 'Kazakhstan', 'native_name' => 'Қазақстан', 'language_id' => $ruId, 'currency_id' => $kzt, 'flag_emoji' => '🇰🇿', 'sort_order' => 32],

            // ============ KOREAN (1) ============
            ['code' => 'KR', 'name' => 'South Korea', 'native_name' => '대한민국', 'language_id' => $koId, 'currency_id' => $krw, 'flag_emoji' => '🇰🇷', 'sort_order' => 33],

            // ============ TURKISH (1) ============
            ['code' => 'TR', 'name' => 'Turkey', 'native_name' => 'Türkiye', 'language_id' => $trId, 'currency_id' => $try, 'flag_emoji' => '🇹🇷', 'sort_order' => 34],

            // ============ ITALIAN (1) ============
            ['code' => 'IT', 'name' => 'Italy', 'native_name' => 'Italia', 'language_id' => $itId, 'currency_id' => $eur, 'flag_emoji' => '🇮🇹', 'sort_order' => 35],

            // ============ INDONESIAN-SPEAKING COUNTRIES (2) ============
            ['code' => 'ID', 'name' => 'Indonesia', 'native_name' => 'Indonesia', 'language_id' => $idId, 'currency_id' => $idr, 'flag_emoji' => '🇮🇩', 'sort_order' => 36],
            ['code' => 'MY', 'name' => 'Malaysia', 'native_name' => 'Malaysia', 'language_id' => $idId, 'currency_id' => $myr, 'flag_emoji' => '🇲🇾', 'sort_order' => 37],

            // ============ DUTCH-SPEAKING COUNTRIES (2) ============
            ['code' => 'NL', 'name' => 'Netherlands', 'native_name' => 'Nederland', 'language_id' => $nlId, 'currency_id' => $eur, 'flag_emoji' => '🇳🇱', 'sort_order' => 38],
            // Belgium already added for French, adding duplicate for Dutch
            ['code' => 'BE', 'name' => 'Belgium', 'native_name' => 'België', 'language_id' => $nlId, 'currency_id' => $eur, 'flag_emoji' => '🇧🇪', 'sort_order' => 39],
        ];

        // Insert with proper timestamps
        DB::table('countries')->insert(array_map(static function ($country) {
            return array_merge($country, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, $countries));
    }
}
