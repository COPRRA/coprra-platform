#!/bin/bash
# Hotfix Script: Fix Sentry Configuration on Production
# Resolves "Call to undefined function exec()" error

set -e

echo "============================================================"
echo "Sentry Configuration Hotfix"
echo "============================================================"
echo ""

PROJECT_DIR="/home/u990109832/domains/coprra.com/public_html"
SENTRY_CONFIG="$PROJECT_DIR/config/sentry.php"

cd "$PROJECT_DIR" || exit 1

echo "📁 Project Directory: $PROJECT_DIR"
echo "📄 Config File: $SENTRY_CONFIG"
echo ""

# Backup original file
if [ -f "$SENTRY_CONFIG" ]; then
    echo "💾 Creating backup..."
    cp "$SENTRY_CONFIG" "${SENTRY_CONFIG}.backup.$(date +%Y%m%d_%H%M%S)"
    echo "✅ Backup created"
    echo ""
fi

# Check if file exists
if [ ! -f "$SENTRY_CONFIG" ]; then
    echo "❌ Error: $SENTRY_CONFIG not found!"
    exit 1
fi

# Fix 1: Remove exec() from release line
echo "🔧 Fix 1: Removing exec() from release configuration..."
if grep -q "exec('git log" "$SENTRY_CONFIG"; then
    sed -i "s/exec('git log --pretty=\"%h\" -n1 HEAD') ?: null/null/g" "$SENTRY_CONFIG"
    echo "✅ Removed exec() call from release configuration"
else
    echo "⚠️  exec() call not found in release line (may already be fixed)"
fi

# Fix 2: Ensure send_default_pii is false
echo ""
echo "🔧 Fix 2: Ensuring send_default_pii is false..."
if grep -q "'send_default_pii'" "$SENTRY_CONFIG"; then
    # Check current value
    if grep -q "'send_default_pii' => true" "$SENTRY_CONFIG"; then
        sed -i "s/'send_default_pii' => true/'send_default_pii' => false/g" "$SENTRY_CONFIG"
        echo "✅ Changed send_default_pii from true to false"
    else
        echo "✅ send_default_pii is already set to false"
    fi
else
    # Add send_default_pii if it doesn't exist
    # Find the line with 'dsn' and add send_default_pii after it
    if grep -q "'dsn'" "$SENTRY_CONFIG"; then
        sed -i "/'dsn'/a\\    'send_default_pii' => false," "$SENTRY_CONFIG"
        echo "✅ Added send_default_pii => false"
    else
        echo "⚠️  Could not find 'dsn' line to add send_default_pii"
    fi
fi

echo ""
echo "============================================================"
echo "✅ Hotfix Applied Successfully"
echo "============================================================"
echo ""
echo "Verifying changes..."
echo ""

# Verify changes
if grep -q "exec('git log" "$SENTRY_CONFIG"; then
    echo "❌ Warning: exec() call still found in config file!"
    exit 1
else
    echo "✅ Verified: No exec() calls found"
fi

if grep -q "'send_default_pii' => false" "$SENTRY_CONFIG"; then
    echo "✅ Verified: send_default_pii is set to false"
else
    echo "⚠️  Warning: send_default_pii may not be set correctly"
fi

echo ""
echo "📋 Next steps:"
echo "1. Run: composer update --no-dev --optimize-autoloader"
echo "2. Run: php artisan config:clear"
echo "3. Run: php artisan route:clear"
echo "4. Run: php artisan view:clear"
echo ""

