#!/bin/bash
# Dependency Audit Script for Production Server
# This script audits both Composer and NPM dependencies for vulnerabilities

set -e

echo "============================================================"
echo "Dependency Vulnerability Audit"
echo "============================================================"
echo "Date: $(date)"
echo ""

# Change to project directory
PROJECT_DIR="/home/u990109832/domains/coprra.com/public_html"
cd "$PROJECT_DIR" || exit 1

echo "📁 Project Directory: $PROJECT_DIR"
echo ""

# Check if composer.json exists
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found"
    exit 1
fi

# Check if package.json exists
if [ ! -f "package.json" ]; then
    echo "⚠️  Warning: package.json not found"
fi

echo "============================================================"
echo "1. Composer Dependency Audit"
echo "============================================================"
echo ""

# Check Composer version
if ! command -v composer &> /dev/null; then
    echo "❌ Error: Composer not found"
    exit 1
fi

echo "Composer version: $(composer --version)"
echo ""

# Run composer audit
echo "Running: composer audit"
echo "----------------------------------------"
if composer audit --format=json > /tmp/composer_audit.json 2>&1; then
    echo "✅ Composer audit completed successfully"
    echo ""
    echo "Summary:"
    composer audit --format=plain 2>&1 || true
else
    AUDIT_EXIT_CODE=$?
    echo "⚠️  Composer audit found issues (exit code: $AUDIT_EXIT_CODE)"
    echo ""
    echo "Vulnerabilities found:"
    composer audit --format=plain 2>&1 || true
fi

echo ""
echo "Detailed JSON report saved to: /tmp/composer_audit.json"
echo ""

# Count vulnerabilities
if [ -f "/tmp/composer_audit.json" ]; then
    VULN_COUNT=$(jq -r '.advisories | length' /tmp/composer_audit.json 2>/dev/null || echo "0")
    echo "Total vulnerabilities found: $VULN_COUNT"
    echo ""
fi

echo "============================================================"
echo "2. NPM Dependency Audit"
echo "============================================================"
echo ""

# Check if npm is available
if ! command -v npm &> /dev/null; then
    echo "⚠️  Warning: npm not found. Skipping NPM audit."
    echo ""
else
    echo "NPM version: $(npm --version)"
    echo ""
    
    # Check if node_modules exists
    if [ ! -d "node_modules" ]; then
        echo "⚠️  Warning: node_modules not found. Running npm install first..."
        npm install --production 2>&1 | tail -5
        echo ""
    fi
    
    # Run npm audit
    echo "Running: npm audit"
    echo "----------------------------------------"
    if npm audit --json > /tmp/npm_audit.json 2>&1; then
        echo "✅ NPM audit completed successfully"
        echo ""
        echo "Summary:"
        npm audit --audit-level=moderate 2>&1 || true
    else
        AUDIT_EXIT_CODE=$?
        echo "⚠️  NPM audit found issues (exit code: $AUDIT_EXIT_CODE)"
        echo ""
        echo "Vulnerabilities found:"
        npm audit --audit-level=moderate 2>&1 || true
    fi
    
    echo ""
    echo "Detailed JSON report saved to: /tmp/npm_audit.json"
    echo ""
    
    # Count vulnerabilities
    if [ -f "/tmp/npm_audit.json" ]; then
        VULN_COUNT=$(jq -r '.metadata.vulnerabilities.total' /tmp/npm_audit.json 2>/dev/null || echo "0")
        echo "Total vulnerabilities found: $VULN_COUNT"
        echo ""
    fi
fi

echo "============================================================"
echo "Audit Complete"
echo "============================================================"
echo ""
echo "Reports saved to:"
echo "  - /tmp/composer_audit.json"
if [ -f "/tmp/npm_audit.json" ]; then
    echo "  - /tmp/npm_audit.json"
fi
echo ""
echo "To view detailed reports, run:"
echo "  cat /tmp/composer_audit.json | jq"
if [ -f "/tmp/npm_audit.json" ]; then
    echo "  cat /tmp/npm_audit.json | jq"
fi

