<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;

$languages = Language::all()->keyBy('code');
$currencies = Currency::all()->keyBy('code');

// Map: Country Code => [Name, Native Name, Flag, Primary Language Code, Currency Code]
$countries = [
    'US' => ['United States', 'United States', '🇺🇸', 'en', 'USD'],
    'GB' => ['United Kingdom', 'United Kingdom', '🇬🇧', 'en', 'GBP'],
    'IN' => ['India', 'India', '🇮🇳', 'en', 'INR'],
    'CA' => ['Canada', 'Canada', '🇨🇦', 'en', 'CAD'],
    'AU' => ['Australia', 'Australia', '🇦🇺', 'en', 'AUD'],
    'NZ' => ['New Zealand', 'New Zealand', '🇳🇿', 'en', 'AUD'],
    'SG' => ['Singapore', 'Singapore', '🇸🇬', 'en', 'SGD'],
    'ZA' => ['South Africa', 'South Africa', '🇿🇦', 'en', 'ZAR'],
    'SA' => ['Saudi Arabia', 'Saudi Arabia', '🇸🇦', 'ar', 'SAR'],
    'EG' => ['Egypt', 'Egypt', '🇪🇬', 'ar', 'EGP'],
    'AE' => ['UAE', 'UAE', '🇦🇪', 'ar', 'AED'],
    'IQ' => ['Iraq', 'Iraq', '🇮🇶', 'ar', 'IQD'],
    'DZ' => ['Algeria', 'Algeria', '🇩🇿', 'ar', 'DZD'],
    'MA' => ['Morocco', 'Morocco', '🇲🇦', 'ar', 'MAD'],
    'ES' => ['Spain', 'Spain', '🇪🇸', 'es', 'EUR'],
    'MX' => ['Mexico', 'Mexico', '🇲🇽', 'es', 'MXN'],
    'CO' => ['Colombia', 'Colombia', '🇨🇴', 'es', 'COP'],
    'AR' => ['Argentina', 'Argentina', '🇦🇷', 'es', 'ARS'],
    'PE' => ['Peru', 'Peru', '🇵🇪', 'es', 'PEN'],
    'VE' => ['Venezuela', 'Venezuela', '🇻🇪', 'es', 'VES'],
    'CL' => ['Chile', 'Chile', '🇨🇱', 'es', 'CLP'],
    'CN' => ['China', 'China', '🇨🇳', 'zh', 'CNY'],
    'TW' => ['Taiwan', 'Taiwan', '🇹🇼', 'zh', 'TWD'],
    'HK' => ['Hong Kong', 'Hong Kong', '🇭🇰', 'zh', 'HKD'],
    'BR' => ['Brazil', 'Brazil', '🇧🇷', 'pt', 'BRL'],
    'PT' => ['Portugal', 'Portugal', '🇵🇹', 'pt', 'EUR'],
    'FR' => ['France', 'France', '🇫🇷', 'fr', 'EUR'],
    'BE' => ['Belgium', 'Belgium', '🇧🇪', 'fr', 'EUR'],
    'CH' => ['Switzerland', 'Switzerland', '🇨🇭', 'de', 'CHF'],
    'DE' => ['Germany', 'Germany', '🇩🇪', 'de', 'EUR'],
    'AT' => ['Austria', 'Austria', '🇦🇹', 'de', 'EUR'],
    'JP' => ['Japan', 'Japan', '🇯🇵', 'ja', 'JPY'],
    'RU' => ['Russia', 'Russia', '🇷🇺', 'ru', 'RUB'],
    'KZ' => ['Kazakhstan', 'Kazakhstan', '🇰🇿', 'ru', 'KZT'],
    'KR' => ['South Korea', 'South Korea', '🇰🇷', 'ko', 'KRW'],
    'TR' => ['Turkey', 'Turkey', '🇹🇷', 'tr', 'TRY'],
    'IT' => ['Italy', 'Italy', '🇮🇹', 'it', 'EUR'],
    'ID' => ['Indonesia', 'Indonesia', '🇮🇩', 'id', 'IDR'],
    'MY' => ['Malaysia', 'Malaysia', '🇲🇾', 'id', 'MYR'],
    'NL' => ['Netherlands', 'Netherlands', '🇳🇱', 'nl', 'EUR'],
];

$sortOrder = 0;
$created = 0;

foreach ($countries as $code => $data) {
    [$name, $nativeName, $flag, $langCode, $currCode] = $data;

    $language = $languages->get($langCode);
    $currency = $currencies->get($currCode);

    if (!$language || !$currency) {
        echo "⚠️  Skipping {$code}: missing language or currency\n";
        continue;
    }

    Country::updateOrCreate(
        ['code' => $code],
        [
            'name' => $name,
            'native_name' => $nativeName,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
            'flag_emoji' => $flag,
            'is_active' => true,
            'sort_order' => ++$sortOrder,
        ]
    );
    $created++;
}

echo "✅ Seeded {$created} countries with hierarchical relationships\n";
