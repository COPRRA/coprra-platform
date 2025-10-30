# COPRRA Optimized Test Runner
param(
    [string]$TestSuite = "All",
    [switch]$Coverage = $false,
    [switch]$Performance = $false,
    [switch]$Verbose = $false
)

Write-Host "=================================================="
Write-Host "    COPRRA Optimized Test Suite Runner" -ForegroundColor Blue
Write-Host "=================================================="
Write-Host ""

Write-Host "Test Configuration:" -ForegroundColor Cyan
Write-Host "  Test Suite: $TestSuite"
Write-Host "  Coverage: $Coverage"
Write-Host "  Performance: $Performance"
Write-Host ""

# Check prerequisites
Write-Host "Checking prerequisites..." -ForegroundColor Yellow

try {
    php -v | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ PHP is available" -ForegroundColor Green
    }
} catch {
    Write-Host "✗ PHP is not available" -ForegroundColor Red
    exit 1
}

# Create reports directory
if (!(Test-Path "reports")) {
    New-Item -ItemType Directory -Path "reports" | Out-Null
}

# Set environment variables
$env:APP_ENV = "testing"
$env:XDEBUG_MODE = "off"

Write-Host "✓ Environment optimized" -ForegroundColor Green
Write-Host ""

# Build PHPUnit command
$phpunitArgs = @()
$phpunitArgs += "vendor/bin/phpunit"

# Use performance configuration if requested
if ($Performance) {
    $phpunitArgs += "--configuration", "phpunit-performance.xml"
} else {
    $phpunitArgs += "--configuration", "phpunit.xml"
}

# Test suite selection
switch ($TestSuite) {
    "AI-ML" { $phpunitArgs += "--testsuite", "AI-ML" }
    "Security" { $phpunitArgs += "--testsuite", "Security" }
    "EdgeCases" { $phpunitArgs += "--testsuite", "EdgeCases" }
    "Integration" { $phpunitArgs += "--testsuite", "Integration" }
    "Unit-Fast" { $phpunitArgs += "--testsuite", "Unit-Fast" }
    "Performance" { $phpunitArgs += "--testsuite", "Performance" }
    default { 
        # Run all tests
    }
}

# Coverage
if ($Coverage) {
    $phpunitArgs += "--coverage-html", "reports/coverage"
    $phpunitArgs += "--coverage-text"
}

# Verbose output
if ($Verbose) {
    $phpunitArgs += "--verbose"
}

# Colors
$phpunitArgs += "--colors=always"

Write-Host "Running tests..." -ForegroundColor Yellow
Write-Host "Command: php $($phpunitArgs -join ' ')" -ForegroundColor Cyan
Write-Host ""

$startTime = Get-Date
& php @phpunitArgs
$exitCode = $LASTEXITCODE
$endTime = Get-Date
$duration = $endTime - $startTime

Write-Host ""
Write-Host "Test execution completed in $($duration.TotalSeconds) seconds" -ForegroundColor Cyan

# Generate simple performance report
$reportFile = "reports/test-execution-report.txt"
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

@"
COPRRA Test Execution Report
Generated: $timestamp

Test Suite: $TestSuite
Duration: $($duration.TotalSeconds) seconds
Exit Code: $exitCode
Coverage: $Coverage
Performance Mode: $Performance

"@ | Out-File -FilePath $reportFile -Encoding UTF8

Write-Host ""
Write-Host "=================================================="
Write-Host "              Test Summary" -ForegroundColor Blue
Write-Host "=================================================="

if ($exitCode -eq 0) {
    Write-Host "✓ All tests passed successfully!" -ForegroundColor Green
} else {
    Write-Host "✗ Some tests failed (Exit code: $exitCode)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Reports generated in: reports/" -ForegroundColor Cyan

if (Test-Path "reports/coverage") {
    Write-Host "  - Coverage report: reports/coverage/index.html"
}

Write-Host "  - Execution report: reports/test-execution-report.txt"
Write-Host ""

exit $exitCode