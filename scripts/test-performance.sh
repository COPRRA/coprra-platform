#!/bin/bash

# COPRRA Testing Performance Script
# This script runs optimized test suites for better performance

set -e

echo "🚀 Starting COPRRA Performance Testing Suite..."

# Create reports directory if it doesn't exist
mkdir -p reports

# Function to run tests with timing
run_timed_test() {
    local test_name="$1"
    local command="$2"
    
    echo "⏱️  Running $test_name..."
    start_time=$(date +%s)
    
    eval "$command"
    
    end_time=$(date +%s)
    duration=$((end_time - start_time))
    echo "✅ $test_name completed in ${duration}s"
}

# PHP Unit Tests (Fast)
run_timed_test "PHP Unit Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Unit --no-coverage"

# PHP Feature Tests
run_timed_test "PHP Feature Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Feature --no-coverage"

# Frontend Tests
run_timed_test "Frontend Tests" "npm run test:frontend"

# Performance Tests
run_timed_test "Performance Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Performance"

# Security Tests
run_timed_test "Security Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Security"

# Integration Tests
run_timed_test "Integration Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Integration"

# Generate Coverage Report (if requested)
if [ "$1" = "--coverage" ]; then
    echo "📊 Generating coverage reports..."
    run_timed_test "PHP Coverage" "vendor/bin/phpunit --configuration=phpunit.performance.xml --coverage-html=reports/coverage"
    run_timed_test "Frontend Coverage" "npm run test:coverage"
fi

# Run Quality Checks
if [ "$1" = "--quality" ]; then
    echo "🔍 Running quality checks..."
    run_timed_test "PHPStan Analysis" "vendor/bin/phpstan analyse --memory-limit=512M"
    run_timed_test "PHP CS Fixer" "vendor/bin/php-cs-fixer fix --dry-run --diff"
    run_timed_test "ESLint" "npm run lint:js"
fi

echo "🎉 All tests completed successfully!"
echo "📋 Reports available in ./reports/ directory"