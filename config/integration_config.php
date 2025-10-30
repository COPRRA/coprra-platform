<?php

declare(strict_types=1);

/**
 * Integration Configuration - Comprehensive Testing Framework Integration Settings.
 *
 * This configuration file defines all settings for component integration,
 * workflow orchestration, testing coordination, and framework optimization
 * across the entire COPRRA testing ecosystem.
 *
 * @version 2.0.0
 *
 * @since 1.0.0
 */

return [
    // Core Integration Settings
    'integration' => [
        'framework_version' => '2.0.0',
        'integration_pattern' => 'pipeline', // sequential, parallel, pipeline, event_driven, microservice
        'execution_mode' => 'hybrid', // sequential, parallel, hybrid
        'coordination_strategy' => 'orchestrated', // orchestrated, choreographed, hybrid
        'communication_protocol' => 'message_bus', // direct, message_bus, event_stream, api
        'data_exchange_format' => 'json', // json, xml, yaml, binary
        'timeout_seconds' => 3600,
        'max_retry_attempts' => 3,
        'enable_real_time_monitoring' => true,
        'enable_predictive_analytics' => true,
        'enable_auto_optimization' => true,
    ],

    // Component Configuration
    'components' => [
        'UnitTestAutomator' => [
            'enabled' => true,
            'priority' => 1,
            'execution_order' => 1,
            'dependencies' => [],
            'parallel_capable' => true,
            'resource_requirements' => [
                'memory_mb' => 512,
                'cpu_cores' => 1,
                'disk_space_mb' => 100,
            ],
            'configuration' => [
                'test_discovery_pattern' => '*Test.php',
                'test_execution_timeout' => 300,
                'enable_code_coverage' => true,
                'coverage_threshold' => 80.0,
                'enable_mutation_testing' => false,
                'enable_property_based_testing' => true,
            ],
            'integration_points' => [
                'outputs_to' => ['IntegrationTestRunner', 'CoverageAnalyzer'],
                'receives_from' => [],
                'shared_data' => ['test_results', 'coverage_data', 'test_metadata'],
            ],
        ],

        'IntegrationTestRunner' => [
            'enabled' => true,
            'priority' => 2,
            'execution_order' => 2,
            'dependencies' => ['UnitTestAutomator'],
            'parallel_capable' => true,
            'resource_requirements' => [
                'memory_mb' => 1024,
                'cpu_cores' => 2,
                'disk_space_mb' => 200,
            ],
            'configuration' => [
                'test_discovery_pattern' => '*IntegrationTest.php',
                'test_execution_timeout' => 600,
                'enable_database_testing' => true,
                'enable_api_testing' => true,
                'enable_service_testing' => true,
                'enable_contract_testing' => true,
            ],
            'integration_points' => [
                'outputs_to' => ['FeatureTestManager', 'PerformanceBenchmarker', 'CoverageAnalyzer'],
                'receives_from' => ['UnitTestAutomator'],
                'shared_data' => ['integration_results', 'service_health', 'api_contracts'],
            ],
        ],

        'FeatureTestManager' => [
            'enabled' => true,
            'priority' => 3,
            'execution_order' => 3,
            'dependencies' => ['UnitTestAutomator', 'IntegrationTestRunner'],
            'parallel_capable' => true,
            'resource_requirements' => [
                'memory_mb' => 2048,
                'cpu_cores' => 2,
                'disk_space_mb' => 500,
            ],
            'configuration' => [
                'test_discovery_pattern' => '*.feature',
                'test_execution_timeout' => 900,
                'enable_bdd_testing' => true,
                'enable_acceptance_testing' => true,
                'enable_user_journey_testing' => true,
                'enable_regression_testing' => true,
            ],
            'integration_points' => [
                'outputs_to' => ['BrowserTestController', 'AITestOrchestrator', 'CoverageAnalyzer'],
                'receives_from' => ['UnitTestAutomator', 'IntegrationTestRunner'],
                'shared_data' => ['feature_results', 'user_scenarios', 'acceptance_criteria'],
            ],
        ],

        'SecurityTestValidator' => [
            'enabled' => true,
            'priority' => 4,
            'execution_order' => 4,
            'dependencies' => ['UnitTestAutomator'],
            'parallel_capable' => true,
            'resource_requirements' => [
                'memory_mb' => 1536,
                'cpu_cores' => 2,
                'disk_space_mb' => 300,
            ],
            'configuration' => [
                'vulnerability_scanning' => true,
                'penetration_testing' => true,
                'compliance_validation' => true,
                'security_code_analysis' => true,
                'dependency_vulnerability_check' => true,
                'owasp_top_10_validation' => true,
            ],
            'integration_points' => [
                'outputs_to' => ['CoverageAnalyzer'],
                'receives_from' => ['UnitTestAutomator'],
                'shared_data' => ['security_results', 'vulnerability_reports', 'compliance_status'],
            ],
        ],

        'PerformanceBenchmarker' => [
            'enabled' => true,
            'priority' => 5,
            'execution_order' => 5,
            'dependencies' => ['UnitTestAutomator', 'IntegrationTestRunner'],
            'parallel_capable' => true,
            'resource_requirements' => [
                'memory_mb' => 2048,
                'cpu_cores' => 4,
                'disk_space_mb' => 1000,
            ],
            'configuration' => [
                'load_testing' => true,
                'stress_testing' => true,
                'spike_testing' => true,
                'endurance_testing' => true,
                'scalability_testing' => true,
                'resource_utilization_monitoring' => true,
            ],
            'integration_points' => [
                'outputs_to' => ['CoverageAnalyzer'],
                'receives_from' => ['UnitTestAutomator', 'IntegrationTestRunner'],
                'shared_data' => ['performance_metrics', 'load_test_results', 'resource_usage'],
            ],
        ],

        'BrowserTestController' => [
            'enabled' => true,
            'priority' => 6,
            'execution_order' => 6,
            'dependencies' => ['UnitTestAutomator', 'FeatureTestManager'],
            'parallel_capable' => true,
            'resource_requirements' => [
                'memory_mb' => 3072,
                'cpu_cores' => 2,
                'disk_space_mb' => 2000,
            ],
            'configuration' => [
                'cross_browser_testing' => true,
                'responsive_testing' => true,
                'visual_regression_testing' => true,
                'accessibility_testing' => true,
                'mobile_testing' => true,
                'performance_testing' => true,
            ],
            'integration_points' => [
                'outputs_to' => ['CoverageAnalyzer'],
                'receives_from' => ['UnitTestAutomator', 'FeatureTestManager'],
                'shared_data' => ['browser_test_results', 'visual_diffs', 'accessibility_reports'],
            ],
        ],

        'AITestOrchestrator' => [
            'enabled' => true,
            'priority' => 7,
            'execution_order' => 7,
            'dependencies' => ['UnitTestAutomator', 'IntegrationTestRunner', 'FeatureTestManager'],
            'parallel_capable' => true,
            'resource_requirements' => [
                'memory_mb' => 4096,
                'cpu_cores' => 4,
                'disk_space_mb' => 1500,
            ],
            'configuration' => [
                'intelligent_test_generation' => true,
                'predictive_failure_analysis' => true,
                'adaptive_test_optimization' => true,
                'pattern_recognition' => true,
                'natural_language_processing' => true,
                'machine_learning_insights' => true,
            ],
            'integration_points' => [
                'outputs_to' => ['CoverageAnalyzer'],
                'receives_from' => ['UnitTestAutomator', 'IntegrationTestRunner', 'FeatureTestManager'],
                'shared_data' => ['ai_generated_tests', 'predictions', 'optimization_suggestions'],
            ],
        ],

        'CoverageAnalyzer' => [
            'enabled' => true,
            'priority' => 8,
            'execution_order' => 8,
            'dependencies' => ['UnitTestAutomator', 'IntegrationTestRunner', 'FeatureTestManager', 'SecurityTestValidator', 'PerformanceBenchmarker', 'BrowserTestController'],
            'parallel_capable' => false, // Aggregates data from all other components
            'resource_requirements' => [
                'memory_mb' => 2048,
                'cpu_cores' => 2,
                'disk_space_mb' => 500,
            ],
            'configuration' => [
                'line_coverage' => true,
                'branch_coverage' => true,
                'function_coverage' => true,
                'class_coverage' => true,
                'integration_coverage' => true,
                'end_to_end_coverage' => true,
            ],
            'integration_points' => [
                'outputs_to' => [],
                'receives_from' => ['UnitTestAutomator', 'IntegrationTestRunner', 'FeatureTestManager', 'SecurityTestValidator', 'PerformanceBenchmarker', 'BrowserTestController', 'AITestOrchestrator'],
                'shared_data' => ['comprehensive_coverage', 'coverage_reports', 'gap_analysis'],
            ],
        ],
    ],

    // Workflow Definitions
    'workflows' => [
        'comprehensive' => [
            'name' => 'Comprehensive Testing Workflow',
            'description' => 'Complete testing pipeline with all components',
            'stages' => [
                'unit' => ['component' => 'UnitTestAutomator', 'parallel' => false],
                'integration' => ['component' => 'IntegrationTestRunner', 'parallel' => false],
                'feature' => ['component' => 'FeatureTestManager', 'parallel' => false],
                'security' => ['component' => 'SecurityTestValidator', 'parallel' => true],
                'performance' => ['component' => 'PerformanceBenchmarker', 'parallel' => true],
                'browser' => ['component' => 'BrowserTestController', 'parallel' => true],
                'ai' => ['component' => 'AITestOrchestrator', 'parallel' => false],
                'coverage' => ['component' => 'CoverageAnalyzer', 'parallel' => false],
            ],
            'execution_strategy' => 'sequential_with_parallel_groups',
            'timeout_minutes' => 60,
            'failure_strategy' => 'continue_on_non_critical',
            'critical_components' => ['UnitTestAutomator', 'IntegrationTestRunner', 'CoverageAnalyzer'],
        ],

        'fast_feedback' => [
            'name' => 'Fast Feedback Workflow',
            'description' => 'Quick validation for rapid development cycles',
            'stages' => [
                'unit' => ['component' => 'UnitTestAutomator', 'parallel' => true],
                'integration' => ['component' => 'IntegrationTestRunner', 'parallel' => true],
                'coverage' => ['component' => 'CoverageAnalyzer', 'parallel' => false],
            ],
            'execution_strategy' => 'parallel_with_sequential_aggregation',
            'timeout_minutes' => 15,
            'failure_strategy' => 'fail_fast',
            'critical_components' => ['UnitTestAutomator', 'IntegrationTestRunner'],
        ],

        'security_focused' => [
            'name' => 'Security-Focused Workflow',
            'description' => 'Security validation and compliance testing',
            'stages' => [
                'unit' => ['component' => 'UnitTestAutomator', 'parallel' => false],
                'security' => ['component' => 'SecurityTestValidator', 'parallel' => false],
                'feature' => ['component' => 'FeatureTestManager', 'parallel' => false],
                'coverage' => ['component' => 'CoverageAnalyzer', 'parallel' => false],
            ],
            'execution_strategy' => 'sequential',
            'timeout_minutes' => 30,
            'failure_strategy' => 'fail_on_security_issues',
            'critical_components' => ['SecurityTestValidator'],
        ],

        'performance_focused' => [
            'name' => 'Performance-Focused Workflow',
            'description' => 'Performance testing and optimization',
            'stages' => [
                'unit' => ['component' => 'UnitTestAutomator', 'parallel' => false],
                'integration' => ['component' => 'IntegrationTestRunner', 'parallel' => false],
                'performance' => ['component' => 'PerformanceBenchmarker', 'parallel' => false],
                'browser' => ['component' => 'BrowserTestController', 'parallel' => true],
                'coverage' => ['component' => 'CoverageAnalyzer', 'parallel' => false],
            ],
            'execution_strategy' => 'sequential_with_parallel_browser',
            'timeout_minutes' => 45,
            'failure_strategy' => 'continue_on_performance_degradation',
            'critical_components' => ['PerformanceBenchmarker'],
        ],
    ],

    // Communication Configuration
    'communication' => [
        'message_bus' => [
            'type' => 'redis', // redis, rabbitmq, kafka, memory
            'host' => 'localhost',
            'port' => 6379,
            'timeout' => 30,
            'retry_attempts' => 3,
            'enable_persistence' => true,
            'enable_clustering' => false,
        ],
        'event_dispatcher' => [
            'enable_async_events' => true,
            'max_event_queue_size' => 1000,
            'event_retention_hours' => 24,
            'enable_event_replay' => true,
        ],
        'data_exchange' => [
            'serialization_format' => 'json',
            'compression_enabled' => true,
            'encryption_enabled' => false,
            'max_payload_size_mb' => 10,
        ],
    ],

    // Monitoring Configuration
    'monitoring' => [
        'real_time_monitoring' => [
            'enabled' => true,
            'update_interval_seconds' => 5,
            'metrics_retention_hours' => 168, // 7 days
            'enable_alerting' => true,
            'alert_thresholds' => [
                'component_failure_rate' => 5.0, // percentage
                'execution_time_increase' => 50.0, // percentage
                'memory_usage_threshold' => 80.0, // percentage
                'cpu_usage_threshold' => 85.0, // percentage
            ],
        ],
        'performance_monitoring' => [
            'track_execution_time' => true,
            'track_resource_usage' => true,
            'track_component_efficiency' => true,
            'track_integration_health' => true,
            'generate_performance_reports' => true,
        ],
        'health_monitoring' => [
            'component_health_checks' => true,
            'integration_health_checks' => true,
            'dependency_health_checks' => true,
            'health_check_interval_seconds' => 30,
        ],
    ],

    // Optimization Configuration
    'optimization' => [
        'auto_optimization' => [
            'enabled' => true,
            'optimization_interval_hours' => 24,
            'performance_threshold_degradation' => 10.0, // percentage
            'enable_predictive_optimization' => true,
            'enable_adaptive_resource_allocation' => true,
        ],
        'resource_optimization' => [
            'enable_dynamic_scaling' => true,
            'enable_load_balancing' => true,
            'enable_caching' => true,
            'cache_ttl_minutes' => 60,
            'enable_resource_pooling' => true,
        ],
        'workflow_optimization' => [
            'enable_parallel_optimization' => true,
            'enable_dependency_optimization' => true,
            'enable_execution_order_optimization' => true,
            'enable_bottleneck_detection' => true,
        ],
    ],

    // Reporting Configuration
    'reporting' => [
        'integration_reports' => [
            'enabled' => true,
            'format' => ['html', 'json', 'xml'], // html, json, xml, pdf
            'include_detailed_metrics' => true,
            'include_performance_analysis' => true,
            'include_optimization_recommendations' => true,
            'include_trend_analysis' => true,
        ],
        'real_time_dashboard' => [
            'enabled' => true,
            'update_interval_seconds' => 10,
            'include_component_status' => true,
            'include_workflow_progress' => true,
            'include_performance_metrics' => true,
            'include_health_indicators' => true,
        ],
        'notification_settings' => [
            'email_notifications' => [
                'enabled' => false,
                'recipients' => [],
                'notification_levels' => ['error', 'warning'],
            ],
            'slack_notifications' => [
                'enabled' => false,
                'webhook_url' => '',
                'channel' => '#testing',
                'notification_levels' => ['error', 'warning', 'info'],
            ],
            'webhook_notifications' => [
                'enabled' => false,
                'endpoints' => [],
                'notification_levels' => ['error', 'warning'],
            ],
        ],
    ],

    // Error Handling Configuration
    'error_handling' => [
        'retry_strategy' => [
            'max_retries' => 3,
            'retry_delay_seconds' => 5,
            'exponential_backoff' => true,
            'retry_on_timeout' => true,
            'retry_on_resource_exhaustion' => true,
        ],
        'fallback_strategy' => [
            'enable_fallback_execution' => true,
            'fallback_timeout_seconds' => 300,
            'enable_graceful_degradation' => true,
            'enable_circuit_breaker' => true,
        ],
        'recovery_strategy' => [
            'enable_auto_recovery' => true,
            'recovery_timeout_seconds' => 600,
            'enable_state_restoration' => true,
            'enable_checkpoint_recovery' => true,
        ],
    ],

    // Security Configuration
    'security' => [
        'authentication' => [
            'enabled' => false,
            'method' => 'api_key', // api_key, oauth, jwt
            'token_expiry_hours' => 24,
        ],
        'authorization' => [
            'enabled' => false,
            'role_based_access' => false,
            'component_level_permissions' => false,
        ],
        'data_protection' => [
            'encrypt_sensitive_data' => false,
            'mask_sensitive_logs' => true,
            'secure_communication' => false,
        ],
    ],

    // Development Configuration
    'development' => [
        'debug_mode' => false,
        'verbose_logging' => false,
        'enable_profiling' => false,
        'enable_test_data_generation' => true,
        'enable_mock_services' => true,
        'enable_integration_playground' => false,
    ],

    // Environment-Specific Overrides
    'environments' => [
        'development' => [
            'integration.timeout_seconds' => 1800,
            'monitoring.real_time_monitoring.update_interval_seconds' => 10,
            'development.debug_mode' => true,
            'development.verbose_logging' => true,
        ],
        'testing' => [
            'integration.timeout_seconds' => 900,
            'monitoring.real_time_monitoring.enabled' => false,
            'optimization.auto_optimization.enabled' => false,
        ],
        'staging' => [
            'integration.timeout_seconds' => 2700,
            'monitoring.real_time_monitoring.enabled' => true,
            'optimization.auto_optimization.enabled' => true,
        ],
        'production' => [
            'integration.timeout_seconds' => 3600,
            'monitoring.real_time_monitoring.enabled' => true,
            'optimization.auto_optimization.enabled' => true,
            'reporting.real_time_dashboard.enabled' => true,
        ],
    ],
];
