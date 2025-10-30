# Simple COPRRA Test Runner
param(
    [string]$Suite = "All"
)

Write-Host "COPRRA Test Runner" -ForegroundColor Blue
Write-Host "Running test suite: $Suite" -ForegroundColor Cyan

# Set environment
$env:APP_ENV = "testing"

# Create reports directory
if (!(Test-Path "reports")) {
    New-Item -ItemType Directory -Path "reports" -Force | Out-Null
}

# Run tests based on suite
switch ($Suite) {
    "AI" {
        Write-Host "Running AI/ML tests..." -ForegroundColor Yellow
        php vendor/bin/phpunit tests/Unit/Services/AI/ --colors=always
    }
    "Security" {
        Write-Host "Running Security tests..." -ForegroundColor Yellow
        php vendor/bin/phpunit tests/Unit/Services/Security/ --colors=always
    }
    "Integration" {
        Write-Host "Running Integration tests..." -ForegroundColor Yellow
        php vendor/bin/phpunit tests/Feature/Integration/ --colors=always
    }
    "EdgeCases" {
        Write-Host "Running Edge Cases tests..." -ForegroundColor Yellow
        php vendor/bin/phpunit tests/Unit/Services/EdgeCases/ --colors=always
    }
    default {
        Write-Host "Running all tests..." -ForegroundColor Yellow
        php vendor/bin/phpunit --colors=always
    }
}

$exitCode = $LASTEXITCODE

if ($exitCode -eq 0) {
    Write-Host "Tests completed successfully!" -ForegroundColor Green
} else {
    Write-Host "Some tests failed. Exit code: $exitCode" -ForegroundColor Red
}

exit $exitCode