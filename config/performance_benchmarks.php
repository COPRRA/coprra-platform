<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Benchmarks Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains performance benchmarks and thresholds for the
    | COPRRA application. These values are used by performance tests
    | to ensure the application meets performance requirements.
    |
    */

    'response_time_thresholds' => [
        // API endpoint response time thresholds (in milliseconds)
        'authentication' => [
            'login' => 300,
            'register' => 500,
            'logout' => 200,
            'refresh_token' => 250,
        ],
        'products' => [
            'list' => 500,
            'detail' => 300,
            'search' => 1000,
            'filter' => 800,
        ],
        'pricing' => [
            'price_search' => 800,
            'best_offer' => 600,
            'price_comparison' => 1000,
        ],
        'ai_services' => [
            'analyze' => 3000,
            'classification' => 2000,
            'image_analysis' => 4000,
            'recommendations' => 2500,
            'batch_processing' => 5000,
        ],
        'system' => [
            'health_check' => 100,
            'system_info' => 200,
            'status' => 150,
        ],
    ],

    'database_performance' => [
        // Database query performance thresholds
        'max_queries_per_request' => [
            'simple_endpoints' => 5,
            'complex_endpoints' => 15,
            'ai_endpoints' => 25,
            'bulk_operations' => 50,
        ],
        'query_time_thresholds' => [
            'simple_query' => 50,      // 50ms
            'complex_query' => 200,    // 200ms
            'bulk_query' => 1000,      // 1 second
        ],
        'n_plus_one_detection' => [
            'enabled' => true,
            'max_allowed_queries' => 10,
            'strict_mode' => false,
        ],
    ],

    'memory_usage' => [
        // Memory usage thresholds (in MB)
        'per_request' => [
            'simple_endpoints' => 32,
            'complex_endpoints' => 64,
            'ai_endpoints' => 128,
            'bulk_operations' => 256,
        ],
        'peak_memory' => [
            'warning_threshold' => 128,
            'critical_threshold' => 256,
        ],
        'memory_leak_detection' => [
            'enabled' => true,
            'max_increase_per_request' => 1, // 1MB
            'gc_threshold' => 50, // Force GC after 50MB
        ],
    ],

    'cache_performance' => [
        // Cache performance metrics
        'operation_time_thresholds' => [
            'cache_read' => 10,   // 10ms
            'cache_write' => 20,  // 20ms
            'cache_delete' => 15, // 15ms
        ],
        'hit_ratio_thresholds' => [
            'minimum_acceptable' => 0.7,  // 70%
            'target_ratio' => 0.85,       // 85%
            'excellent_ratio' => 0.95,    // 95%
        ],
        'cache_size_limits' => [
            'redis_max_memory' => '512mb',
            'file_cache_max_size' => '1gb',
        ],
    ],

    'concurrent_users' => [
        // Concurrent user performance thresholds
        'load_testing' => [
            'light_load' => 10,
            'medium_load' => 50,
            'heavy_load' => 100,
            'stress_load' => 200,
        ],
        'response_time_under_load' => [
            'light_load_max_response' => 600,   // 600ms
            'medium_load_max_response' => 1000, // 1 second
            'heavy_load_max_response' => 2000,  // 2 seconds
        ],
        'error_rate_thresholds' => [
            'acceptable_error_rate' => 0.01,  // 1%
            'warning_error_rate' => 0.05,     // 5%
            'critical_error_rate' => 0.10,    // 10%
        ],
    ],

    'baseline_measurements' => [
        // Baseline performance measurements (updated periodically)
        'last_updated' => '2024-01-01',
        'environment' => 'development',
        'measurements' => [
            'average_response_times' => [
                'api/auth/login' => 180,
                'api/products' => 320,
                'api/products/{id}' => 150,
                'api/search' => 650,
                'api/price-search' => 480,
                'api/best-offer' => 380,
                'api/categories' => 200,
                'api/brands' => 180,
                'api/system/health' => 45,
                'api/system/info' => 120,
            ],
            'average_memory_usage' => [
                'simple_requests' => 18,  // MB
                'complex_requests' => 35, // MB
                'ai_requests' => 85,      // MB
            ],
            'average_query_counts' => [
                'simple_endpoints' => 3,
                'complex_endpoints' => 8,
                'ai_endpoints' => 15,
            ],
        ],
    ],

    'monitoring' => [
        // Performance monitoring configuration
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'log_slow_requests' => true,
        'slow_request_threshold' => 1000, // 1 second
        'log_memory_usage' => true,
        'log_query_counts' => true,
        'alert_thresholds' => [
            'response_time_multiplier' => 2.0, // Alert if 2x baseline
            'memory_usage_multiplier' => 1.5,  // Alert if 1.5x baseline
            'query_count_multiplier' => 2.0,   // Alert if 2x baseline
        ],
    ],

    'regression_detection' => [
        // Performance regression detection settings
        'enabled' => true,
        'comparison_window' => 7, // days
        'regression_thresholds' => [
            'response_time_increase' => 0.20,  // 20% increase
            'memory_usage_increase' => 0.25,   // 25% increase
            'query_count_increase' => 0.30,    // 30% increase
        ],
        'baseline_update_frequency' => 'weekly',
        'auto_update_baselines' => false,
    ],

    'reporting' => [
        // Performance reporting configuration
        'generate_reports' => true,
        'report_frequency' => 'daily',
        'report_formats' => ['json', 'html'],
        'include_trends' => true,
        'include_comparisons' => true,
        'export_to_file' => true,
        'export_path' => storage_path('performance_reports'),
    ],
];
