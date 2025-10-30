# Performance Testing Guidelines

## Overview

This document provides comprehensive guidelines for performance testing in the COPRRA application. It covers testing procedures, baseline measurements, regression detection, and best practices for maintaining optimal application performance.

## Table of Contents

1. [Performance Testing Infrastructure](#performance-testing-infrastructure)
2. [Running Performance Tests](#running-performance-tests)
3. [Performance Baselines](#performance-baselines)
4. [Regression Detection](#regression-detection)
5. [Performance Thresholds](#performance-thresholds)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)

## Performance Testing Infrastructure

### Test Files Structure

```
tests/
├── Feature/
│   └── Performance/
│       ├── CriticalEndpointsTest.php      # Core API endpoint tests
│       ├── AIEndpointsPerformanceTest.php # AI-specific performance tests
│       ├── PerformanceTest.php            # General performance tests
│       ├── DatabaseQueryTimeTest.php      # Database performance tests
│       ├── MemoryUsageTest.php           # Memory usage tests
│       ├── MemoryLeakTest.php            # Memory leak detection
│       ├── AIModelPerformanceTest.php     # AI model performance
│       └── ConcurrentUserTest.php        # Load testing
```

### Configuration Files

- `config/performance_benchmarks.php` - Performance thresholds and baseline configurations
- `storage/performance_baselines.json` - Current performance baselines
- `.github/workflows/performance-regression.yml` - CI/CD performance testing

### Scripts

- `scripts/measure_performance_baseline.php` - Measure and establish baselines
- `scripts/compare_performance_baselines.php` - Compare performance metrics

## Running Performance Tests

### Local Testing

```bash
# Run all performance tests
php artisan test tests/Feature/Performance/

# Run specific performance test
php artisan test tests/Feature/Performance/CriticalEndpointsTest.php

# Run with specific filter
php artisan test --filter=testAuthenticationPerformance
```

### Measuring Baselines

```bash
# Measure current performance baselines
php scripts/measure_performance_baseline.php

# Compare with previous baselines
php scripts/compare_performance_baselines.php
```

### CI/CD Integration

Performance tests run automatically on:
- Pull requests (regression detection)
- Main branch pushes (baseline updates)
- Scheduled runs (daily performance monitoring)

## Performance Baselines

### Current Baselines

Performance baselines are stored in `storage/performance_baselines.json` and include:

#### Response Time Baselines (ms)
- Authentication endpoints: ~150ms
- Product listing: ~200ms
- Product details: ~180ms
- Search functionality: ~300ms
- AI analysis: ~800ms
- System health: ~50ms

#### Memory Usage Baselines (MB)
- Standard endpoints: ~25MB
- AI endpoints: ~60MB
- Bulk operations: ~45MB

#### Database Query Baselines
- Standard endpoints: 3-5 queries
- Complex endpoints: 8-12 queries
- AI endpoints: 5-8 queries

### Updating Baselines

Baselines should be updated when:
1. Legitimate performance improvements are made
2. New features are added that affect performance
3. Infrastructure changes occur

```bash
# Update baselines after performance improvements
php scripts/measure_performance_baseline.php
git add storage/performance_baselines.json
git commit -m "Update performance baselines after optimization"
```

## Regression Detection

### Thresholds for Regression

A performance regression is detected when:
- Response time increases by >20%
- Memory usage increases by >25%
- Query count increases by >30%
- Cache hit ratio decreases by >10%

### Regression Workflow

1. **Detection**: Automated comparison in CI/CD
2. **Notification**: Slack alerts and PR comments
3. **Investigation**: Review performance reports
4. **Resolution**: Fix performance issues or update baselines

### Performance Reports

Reports are generated in HTML format and include:
- Current vs baseline comparisons
- Trend analysis
- Detailed metrics breakdown
- Recommendations for optimization

## Performance Thresholds

### Response Time Thresholds

```php
// Standard API endpoints
const RESPONSE_TIME_THRESHOLD_MS = 500;

// Search and complex operations
const SEARCH_RESPONSE_TIME_THRESHOLD_MS = 1000;

// AI operations
const AI_RESPONSE_TIME_THRESHOLD_MS = 3000;

// System endpoints
const SYSTEM_RESPONSE_TIME_THRESHOLD_MS = 200;
```

### Memory Usage Thresholds

```php
// Per request memory limits
const MEMORY_THRESHOLD_MB = 50;
const AI_MEMORY_THRESHOLD_MB = 100;
const BULK_MEMORY_THRESHOLD_MB = 75;

// Peak memory detection
const PEAK_MEMORY_THRESHOLD_MB = 128;
```

### Database Performance Thresholds

```php
// Query count limits
const MAX_QUERIES_PER_REQUEST = 15;
const STANDARD_MAX_QUERIES = 10;
const AI_MAX_QUERIES = 20;

// Query time limits
const MAX_QUERY_TIME_MS = 100;
const SLOW_QUERY_THRESHOLD_MS = 50;
```

### Cache Performance Thresholds

```php
// Cache hit ratios
const MIN_CACHE_HIT_RATIO = 0.8;
const OPTIMAL_CACHE_HIT_RATIO = 0.9;

// Cache operation times
const MAX_CACHE_OPERATION_TIME_MS = 10;
```

## Best Practices

### Writing Performance Tests

1. **Use Realistic Data**: Test with production-like data volumes
2. **Isolate Tests**: Each test should be independent
3. **Measure Multiple Metrics**: Response time, memory, queries
4. **Set Appropriate Thresholds**: Based on business requirements
5. **Include Warm-up**: Allow for cache warming and JIT compilation

```php
public function testEndpointPerformance()
{
    // Warm-up request
    $this->get('/api/endpoint');
    
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    $response = $this->get('/api/endpoint');
    
    $responseTime = (microtime(true) - $startTime) * 1000;
    $memoryUsed = (memory_get_usage(true) - $startMemory) / 1024 / 1024;
    
    $this->assertLessThan(self::RESPONSE_TIME_THRESHOLD_MS, $responseTime);
    $this->assertLessThan(self::MEMORY_THRESHOLD_MB, $memoryUsed);
}
```

### Performance Optimization Guidelines

1. **Database Optimization**
   - Use eager loading to prevent N+1 queries
   - Implement proper indexing
   - Monitor query execution plans

2. **Caching Strategy**
   - Cache frequently accessed data
   - Use appropriate cache TTLs
   - Monitor cache hit ratios

3. **Memory Management**
   - Avoid memory leaks in long-running processes
   - Use generators for large datasets
   - Monitor peak memory usage

4. **API Optimization**
   - Implement pagination for large datasets
   - Use compression for responses
   - Optimize serialization

### Monitoring and Alerting

1. **Continuous Monitoring**
   - Set up performance dashboards
   - Monitor key metrics in production
   - Use APM tools for detailed insights

2. **Alerting Thresholds**
   - Response time > 2x baseline
   - Memory usage > 1.5x baseline
   - Error rate > 1%
   - Cache hit ratio < 80%

## Troubleshooting

### Common Performance Issues

1. **Slow Response Times**
   - Check for N+1 query problems
   - Verify cache effectiveness
   - Review database query performance
   - Check for blocking operations

2. **High Memory Usage**
   - Look for memory leaks
   - Check for large object retention
   - Review data loading patterns
   - Monitor garbage collection

3. **Database Performance**
   - Analyze slow query logs
   - Check index usage
   - Review query execution plans
   - Monitor connection pooling

### Debugging Performance Tests

1. **Test Failures**
   - Check baseline validity
   - Verify test environment consistency
   - Review recent code changes
   - Check for external dependencies

2. **Inconsistent Results**
   - Ensure test isolation
   - Check for background processes
   - Verify hardware consistency
   - Review test data setup

### Performance Analysis Tools

1. **Laravel Specific**
   - Laravel Debugbar
   - Laravel Telescope
   - Clockwork

2. **PHP Profiling**
   - Xdebug profiler
   - Blackfire
   - Tideways

3. **Database Analysis**
   - MySQL slow query log
   - EXPLAIN statements
   - Performance schema

## Conclusion

Regular performance testing is crucial for maintaining application quality. Follow these guidelines to:

- Establish reliable performance baselines
- Detect regressions early
- Maintain optimal application performance
- Ensure scalability as the application grows

For questions or issues with performance testing, consult the development team or refer to the detailed performance testing report in `PERFORMANCE_TESTING_REPORT.md`.