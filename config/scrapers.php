<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Scraper Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external search scrapers including rate limits,
    | timeouts, and retry settings.
    |
    */

    'rate_limits' => [
        'amazon' => [
            'requests_per_minute' => (int) env('SCRAPER_AMAZON_RPM', 5),
            'requests_per_hour' => (int) env('SCRAPER_AMAZON_RPH', 30),
            'requests_per_day' => (int) env('SCRAPER_AMAZON_RPD', 200),
        ],
        'ebay' => [
            'requests_per_minute' => (int) env('SCRAPER_EBAY_RPM', 10),
            'requests_per_hour' => (int) env('SCRAPER_EBAY_RPH', 50),
            'requests_per_day' => (int) env('SCRAPER_EBAY_RPD', 300),
        ],
    ],

    'timeouts' => [
        'connection' => (int) env('SCRAPER_CONNECTION_TIMEOUT', 5),
        'request' => (int) env('SCRAPER_REQUEST_TIMEOUT', 15),
    ],

    'retry' => [
        'max_attempts' => (int) env('SCRAPER_MAX_RETRIES', 3),
        'delay_seconds' => (int) env('SCRAPER_RETRY_DELAY', 2),
    ],

    'cache' => [
        'ttl' => (int) env('SCRAPER_CACHE_TTL', 3600), // 1 hour
    ],

    'delay_between_stores' => (int) env('SCRAPER_DELAY_BETWEEN_STORES', 2),

    'user_agents' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
    ],

    'affiliate_tags' => [
        'amazon' => env('AMAZON_AFFILIATE_TAG', null),
        'ebay' => env('EBAY_AFFILIATE_TAG', null),
    ],
];

