# COPRRA Testing Performance Script (PowerShell)
# This script runs optimized test suites for better performance on Windows

param(
    [switch]$Coverage,
    [switch]$Quality,
    [switch]$Parallel
)

Write-Host "🚀 Starting COPRRA Performance Testing Suite..." -ForegroundColor Green

# Create reports directory if it doesn't exist
if (!(Test-Path "reports")) {
    New-Item -ItemType Directory -Path "reports" -Force | Out-Null
}

# Function to run tests with timing
function Run-TimedTest {
    param(
        [string]$TestName,
        [string]$Command
    )
    
    Write-Host "⏱️  Running $TestName..." -ForegroundColor Yellow
    $startTime = Get-Date
    
    try {
        Invoke-Expression $Command
        $endTime = Get-Date
        $duration = ($endTime - $startTime).TotalSeconds
        Write-Host "✅ $TestName completed in $([math]::Round($duration, 2))s" -ForegroundColor Green
    }
    catch {
        Write-Host "❌ $TestName failed: $($_.Exception.Message)" -ForegroundColor Red
        throw
    }
}

try {
    # PHP Unit Tests (Fast)
    Run-TimedTest "PHP Unit Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Unit --no-coverage"

    # PHP Feature Tests
    Run-TimedTest "PHP Feature Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Feature --no-coverage"

    # Frontend Tests
    if (Test-Path "package.json") {
        Run-TimedTest "Frontend Tests" "npm run test:frontend"
    }

    # Performance Tests
    Run-TimedTest "Performance Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Performance"

    # Security Tests
    Run-TimedTest "Security Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Security"

    # Integration Tests
    Run-TimedTest "Integration Tests" "vendor/bin/phpunit --configuration=phpunit.performance.xml --testsuite=Integration"

    # Generate Coverage Report (if requested)
    if ($Coverage) {
        Write-Host "📊 Generating coverage reports..." -ForegroundColor Cyan
        Run-TimedTest "PHP Coverage" "vendor/bin/phpunit --configuration=phpunit.performance.xml --coverage-html=reports/coverage"
        
        if (Test-Path "package.json") {
            Run-TimedTest "Frontend Coverage" "npm run test:coverage"
        }
    }

    # Run Quality Checks
    if ($Quality) {
        Write-Host "🔍 Running quality checks..." -ForegroundColor Cyan
        Run-TimedTest "PHPStan Analysis" "vendor/bin/phpstan analyse --memory-limit=512M"
        Run-TimedTest "PHP CS Fixer" "vendor/bin/php-cs-fixer fix --dry-run --diff"
        
        if (Test-Path "package.json") {
            Run-TimedTest "ESLint" "npm run lint:js"
        }
    }

    Write-Host "🎉 All tests completed successfully!" -ForegroundColor Green
    Write-Host "📋 Reports available in ./reports/ directory" -ForegroundColor Cyan
}
catch {
    Write-Host "💥 Test suite failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}