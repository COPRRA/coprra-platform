<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Affiliate Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for affiliate links and partner stores.
    |
    */

    'stores' => [
        'amazon' => [
            'name' => 'Amazon',
            'logo' => 'images/stores/amazon.png',
            'base_url' => 'https://www.amazon.com',
            'affiliate_tag' => env('AMAZON_AFFILIATE_TAG', 'coprra-20'),
            'tag_parameter' => 'tag',
            'available_countries' => ['US', 'GB', 'CA', 'DE', 'FR', 'IT', 'ES', 'JP', 'AU', 'IN'],
        ],
        'ebay' => [
            'name' => 'eBay',
            'logo' => 'images/stores/ebay.png',
            'base_url' => 'https://www.ebay.com',
            'affiliate_tag' => env('EBAY_AFFILIATE_TAG', 'coprra'),
            'tag_parameter' => 'ref',
            'available_countries' => ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IT', 'ES'],
        ],
        'noon' => [
            'name' => 'Noon',
            'logo' => 'images/stores/noon.png',
            'base_url' => 'https://www.noon.com',
            'affiliate_tag' => env('NOON_AFFILIATE_TAG', 'coprra'),
            'tag_parameter' => 'ref',
            'available_countries' => ['AE', 'SA', 'EG'],
        ],
        'jumia' => [
            'name' => 'Jumia',
            'logo' => 'images/stores/jumia.png',
            'base_url' => 'https://www.jumia.com',
            'affiliate_tag' => env('JUMIA_AFFILIATE_TAG', 'coprra'),
            'tag_parameter' => 'ref',
            'available_countries' => ['EG', 'SA', 'AE', 'NG', 'KE'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Country to Store Mapping
    |--------------------------------------------------------------------------
    |
    | Maps country codes to available stores in that country.
    |
    */
    'country_stores' => [
        'US' => ['amazon', 'ebay'],
        'GB' => ['amazon', 'ebay'],
        'CA' => ['amazon', 'ebay'],
        'AU' => ['amazon', 'ebay'],
        'DE' => ['amazon', 'ebay'],
        'FR' => ['amazon', 'ebay'],
        'IT' => ['amazon', 'ebay'],
        'ES' => ['amazon', 'ebay'],
        'JP' => ['amazon'],
        'IN' => ['amazon'],
        'AE' => ['noon', 'amazon'],
        'SA' => ['noon', 'amazon'],
        'EG' => ['noon', 'jumia', 'amazon'],
        'NG' => ['jumia'],
        'KE' => ['jumia'],
    ],
];

