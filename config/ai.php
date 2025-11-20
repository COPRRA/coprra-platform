<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configure AI services, API keys, and cost management settings.
    |
    */

    // API Configuration
    'api_key' => env('AI_API_KEY', ''),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'timeout' => env('AI_TIMEOUT', 60),
    'max_retries' => env('AI_MAX_RETRIES', 3),
    'retry_delay' => env('AI_RETRY_DELAY', 1000),

    // Disable external API calls in testing
    'disable_external_calls' => env('AI_DISABLE_EXTERNAL_CALLS', false),

    // Cost Management
    'daily_budget' => (float) env('AI_DAILY_BUDGET', 5.00), // $5 per day
    'monthly_budget' => (float) env('AI_MONTHLY_BUDGET', 100.00), // $100 per month
    'auto_stop_on_budget_exceed' => env('AI_AUTO_STOP', true),
    'cost_alert_threshold' => (float) env('AI_COST_ALERT_THRESHOLD', 3.00), // Alert at $3

    // Rate Limiting
    'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 10),
    'rate_limit_per_hour' => (int) env('AI_RATE_LIMIT_PER_HOUR', 100),
    'rate_limit_per_day' => (int) env('AI_RATE_LIMIT_PER_DAY', 1000),

    // Model Configuration
    'default_model' => env('AI_DEFAULT_MODEL', 'gpt-3.5-turbo'), // Use cheaper model by default
    'max_tokens' => (int) env('AI_MAX_TOKENS', 2000),
    'temperature' => (float) env('AI_TEMPERATURE', 0.7),

    // Caching
    'cache_enabled' => env('AI_CACHE_ENABLED', true),
    'cache_ttl' => (int) env('AI_CACHE_TTL', 604800), // 7 days in seconds

    // Performance
    'use_queue' => env('AI_USE_QUEUE', true), // Process AI requests in background
    'queue_name' => env('AI_QUEUE_NAME', 'ai'),
    'timeout_warning_threshold' => (int) env('AI_TIMEOUT_WARNING', 30), // Warn if >30s

    // Monitoring
    'log_requests' => env('AI_LOG_REQUESTS', true),
    'track_costs' => env('AI_TRACK_COSTS', true),
    'alert_email' => env('AI_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),
];
