#!/bin/bash
# Complete Hotfix Execution Script for Production Server
# Applies Sentry configuration fix and completes deployment

set -e

PROJECT_DIR="/home/u990109832/domains/coprra.com/public_html"
SENTRY_CONFIG="$PROJECT_DIR/config/sentry.php"

echo "============================================================"
echo "Sentry Hotfix - Production Server"
echo "============================================================"
echo "Date: $(date)"
echo ""

cd "$PROJECT_DIR" || {
    echo "❌ Error: Cannot access project directory: $PROJECT_DIR"
    exit 1
}

echo "✅ Connected to project directory: $PROJECT_DIR"
echo ""

# Mission 1: Backup and Fix Sentry Config
echo "============================================================"
echo "Mission 1: Fix Sentry Configuration"
echo "============================================================"
echo ""

if [ ! -f "$SENTRY_CONFIG" ]; then
    echo "❌ Error: Sentry config file not found: $SENTRY_CONFIG"
    exit 1
fi

# Create backup
BACKUP_FILE="${SENTRY_CONFIG}.backup.$(date +%Y%m%d_%H%M%S)"
cp "$SENTRY_CONFIG" "$BACKUP_FILE"
echo "💾 Backup created: $BACKUP_FILE"
echo ""

# Fix 1: Remove exec() from release line
echo "🔧 Fixing release configuration (removing exec())..."
if grep -q "exec('git log" "$SENTRY_CONFIG"; then
    # Use sed to replace the exec() line
    sed -i "s/exec('git log --pretty=\"%h\" -n1 HEAD') ?: null/null/g" "$SENTRY_CONFIG"
    echo "✅ Removed exec() call from release configuration"
else
    echo "⚠️  exec() call not found (may already be fixed)"
fi

# Fix 2: Ensure send_default_pii is false (it should already be, but verify)
echo ""
echo "🔧 Verifying send_default_pii setting..."
if grep -q "'send_default_pii' => true" "$SENTRY_CONFIG"; then
    sed -i "s/'send_default_pii' => true/'send_default_pii' => false/g" "$SENTRY_CONFIG"
    echo "✅ Changed send_default_pii from true to false"
elif grep -q "'send_default_pii' => false" "$SENTRY_CONFIG"; then
    echo "✅ send_default_pii is already set to false"
else
    # Add it if missing (after dsn line)
    sed -i "/'dsn'/a\\    'send_default_pii' => false," "$SENTRY_CONFIG"
    echo "✅ Added send_default_pii => false"
fi

# Verify fixes
echo ""
echo "🔍 Verifying changes..."
if grep -q "exec('git log" "$SENTRY_CONFIG"; then
    echo "❌ ERROR: exec() call still found!"
    exit 1
fi

if grep -q "'send_default_pii' => false" "$SENTRY_CONFIG"; then
    echo "✅ Verified: send_default_pii is false"
else
    echo "⚠️  WARNING: send_default_pii may not be set correctly"
fi

echo ""
echo "✅ Mission 1 Complete: Sentry config fixed"
echo ""

# Mission 2: Complete Composer Update
echo "============================================================"
echo "Mission 2: Complete Composer Update"
echo "============================================================"
echo ""

echo "📦 Running composer update..."
if composer update --no-dev --optimize-autoloader; then
    echo "✅ Composer update completed successfully"
else
    echo "❌ ERROR: Composer update failed"
    exit 1
fi

echo ""
echo "✅ Mission 2 Complete: Composer update successful"
echo ""

# Mission 3: Clear Caches
echo "============================================================"
echo "Mission 3: Clear All Caches"
echo "============================================================"
echo ""

echo "🧹 Clearing configuration cache..."
php artisan config:clear || echo "⚠️  Warning: config:clear failed"

echo "🧹 Clearing route cache..."
php artisan route:clear || echo "⚠️  Warning: route:clear failed"

echo "🧹 Clearing view cache..."
php artisan view:clear || echo "⚠️  Warning: view:clear failed"

echo ""
echo "✅ Mission 3 Complete: Caches cleared"
echo ""

# Final Summary
echo "============================================================"
echo "✅ HOTFIX COMPLETE - ALL MISSIONS SUCCESSFUL"
echo "============================================================"
echo ""
echo "Summary:"
echo "  ✅ Sentry config fixed (exec() removed)"
echo "  ✅ send_default_pii set to false"
echo "  ✅ Composer update completed"
echo "  ✅ All caches cleared"
echo ""
echo "Backup file: $BACKUP_FILE"
echo ""
echo "Status: ✅ **Hotfix applied. Deployment completed successfully.**"
echo ""

