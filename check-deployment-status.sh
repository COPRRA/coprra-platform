#!/bin/bash

#═══════════════════════════════════════════════════════════════════
# COPRRA Deployment Status Checker
# Run this script on the server to verify deployment health
#═══════════════════════════════════════════════════════════════════

PROJECT_ROOT="/home/u990109832/public_html"
DB_NAME="u990109832_coprra"
MYSQL_USER="u990109832"
MYSQL_PASS="Hamo1510@Rayan146"

echo "═══════════════════════════════════════════════════════════════════"
echo "🔍 COPRRA Deployment Status Check"
echo "═══════════════════════════════════════════════════════════════════"
echo "Timestamp: $(date)"
echo ""

#═══════════════════════════════════════════════════════════════════
# 1. FILE SYSTEM CHECK
#═══════════════════════════════════════════════════════════════════

echo "📁 File System:"
echo "───────────────────────────────────────────────────────────────────"

if [ -d "$PROJECT_ROOT" ]; then
    cd "$PROJECT_ROOT" || exit 1

    # Check essential directories
    for dir in app config public routes storage vendor; do
        if [ -d "$dir" ]; then
            echo "  ✅ $dir/"
        else
            echo "  ❌ $dir/ MISSING!"
        fi
    done

    # Check essential files
    for file in .env artisan composer.json; do
        if [ -f "$file" ]; then
            echo "  ✅ $file"
        else
            echo "  ❌ $file MISSING!"
        fi
    done
else
    echo "  ❌ Project root not found: $PROJECT_ROOT"
    exit 1
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# 2. PERMISSIONS CHECK
#═══════════════════════════════════════════════════════════════════

echo "🔐 Permissions:"
echo "───────────────────────────────────────────────────────────────────"

if [ -d "storage" ]; then
    STORAGE_PERMS=$(stat -c '%a' storage/)
    echo "  storage/: $STORAGE_PERMS $([ "$STORAGE_PERMS" = "775" ] && echo "✅" || echo "⚠️  (should be 775)")"
else
    echo "  ❌ storage/ not found"
fi

if [ -d "bootstrap/cache" ]; then
    CACHE_PERMS=$(stat -c '%a' bootstrap/cache/)
    echo "  bootstrap/cache/: $CACHE_PERMS $([ "$CACHE_PERMS" = "775" ] && echo "✅" || echo "⚠️  (should be 775)")"
else
    echo "  ❌ bootstrap/cache/ not found"
fi

# Check if files are writable
if [ -w "storage/logs" ]; then
    echo "  ✅ storage/logs writable"
else
    echo "  ❌ storage/logs NOT writable"
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# 3. ENVIRONMENT CHECK
#═══════════════════════════════════════════════════════════════════

echo "⚙️  Environment Configuration:"
echo "───────────────────────────────────────────────────────────────────"

if [ -f ".env" ]; then
    echo "  ✅ .env file exists"

    # Check critical variables
    if grep -q "APP_KEY=base64:" .env; then
        echo "  ✅ APP_KEY is set"
    else
        echo "  ❌ APP_KEY not set or invalid"
    fi

    APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
    echo "  Environment: $APP_ENV $([ "$APP_ENV" = "production" ] && echo "✅" || echo "⚠️  (should be production)")"

    APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d '=' -f2)
    echo "  Debug Mode: $APP_DEBUG $([ "$APP_DEBUG" = "false" ] && echo "✅" || echo "⚠️  (should be false)")"

    APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2)
    echo "  App URL: $APP_URL"

    DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
    echo "  Database: $DB_DATABASE"
else
    echo "  ❌ .env file not found!"
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# 4. DATABASE CHECK
#═══════════════════════════════════════════════════════════════════

echo "💾 Database:"
echo "───────────────────────────────────────────────────────────────────"

# Check if database exists
DB_EXISTS=$(mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -sse "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}';" 2>/dev/null)

if [ "$DB_EXISTS" = "1" ]; then
    echo "  ✅ Database exists: $DB_NAME"

    # Count tables
    TABLE_COUNT=$(mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -sse "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}';" 2>/dev/null)
    echo "  ✅ Tables: $TABLE_COUNT"

    # Check Laravel connection
    if php artisan db:show 2>&1 | grep -q "MySQL\|Connection"; then
        echo "  ✅ Laravel database connection: OK"
    else
        echo "  ❌ Laravel database connection: FAILED"
    fi

    # List some tables
    echo "  Sample tables:"
    mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -sse "SELECT table_name FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}' LIMIT 5;" 2>/dev/null | while read table; do
        echo "    - $table"
    done
else
    echo "  ❌ Database not found: $DB_NAME"
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# 5. LARAVEL CHECK
#═══════════════════════════════════════════════════════════════════

echo "🔧 Laravel Application:"
echo "───────────────────────────────────────────────────────────────────"

# Check PHP version
PHP_VERSION=$(php -v | head -1)
echo "  PHP: $PHP_VERSION"

# Check Laravel version
if command -v php artisan &> /dev/null; then
    LARAVEL_VERSION=$(php artisan --version 2>/dev/null)
    echo "  Laravel: $LARAVEL_VERSION"
    echo "  ✅ Artisan commands available"
else
    echo "  ❌ Artisan not available"
fi

# Check routes
if php artisan route:list --json &> /dev/null; then
    ROUTE_COUNT=$(php artisan route:list --json 2>/dev/null | grep -c '"uri"' || echo "0")
    echo "  ✅ Routes loaded: $ROUTE_COUNT"
else
    echo "  ⚠️  Cannot load routes (this is normal if caches are active)"
fi

# Check migration status
echo "  Migration Status:"
if php artisan migrate:status 2>&1 | grep -q "Ran\|Pending"; then
    echo "  ✅ Migrations table exists"
    PENDING=$(php artisan migrate:status 2>/dev/null | grep -c "Pending" || echo "0")
    if [ "$PENDING" -gt 0 ]; then
        echo "  ⚠️  Pending migrations: $PENDING"
    else
        echo "  ✅ All migrations ran"
    fi
else
    echo "  ❌ Cannot check migration status"
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# 6. WEB SERVER CHECK
#═══════════════════════════════════════════════════════════════════

echo "🌐 Web Server:"
echo "───────────────────────────────────────────────────────────────────"

# Check .htaccess files
if [ -f ".htaccess" ]; then
    echo "  ✅ Main .htaccess exists"
    if grep -q "RewriteRule.*public" .htaccess; then
        echo "  ✅ Routes to public/ directory"
    fi
    if grep -q "HTTPS.*off" .htaccess; then
        echo "  ✅ HTTPS redirect configured"
    fi
else
    echo "  ❌ Main .htaccess not found"
fi

if [ -f "public/.htaccess" ]; then
    echo "  ✅ public/.htaccess exists"
    if grep -q "RewriteRule.*index.php" public/.htaccess; then
        echo "  ✅ Laravel front controller configured"
    fi
else
    echo "  ❌ public/.htaccess not found"
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# 7. CACHE STATUS
#═══════════════════════════════════════════════════════════════════

echo "💾 Cache Status:"
echo "───────────────────────────────────────────────────────────────────"

if [ -f "bootstrap/cache/config.php" ]; then
    echo "  ✅ Config cached"
else
    echo "  ⚠️  Config not cached"
fi

if [ -f "bootstrap/cache/routes-v7.php" ]; then
    echo "  ✅ Routes cached"
else
    echo "  ⚠️  Routes not cached"
fi

if [ -d "storage/framework/views" ] && [ "$(ls -A storage/framework/views)" ]; then
    echo "  ✅ Views compiled"
else
    echo "  ⚠️  Views not compiled"
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# 8. LOG CHECK
#═══════════════════════════════════════════════════════════════════

echo "📋 Recent Logs:"
echo "───────────────────────────────────────────────────────────────────"

if [ -f "storage/logs/laravel.log" ]; then
    LOG_SIZE=$(du -h storage/logs/laravel.log | cut -f1)
    echo "  Log file size: $LOG_SIZE"

    # Check for recent errors
    ERROR_COUNT=$(tail -100 storage/logs/laravel.log 2>/dev/null | grep -c "ERROR\|CRITICAL\|EMERGENCY" || echo "0")
    if [ "$ERROR_COUNT" -gt 0 ]; then
        echo "  ⚠️  Recent errors found: $ERROR_COUNT (last 100 lines)"
        echo "  Latest errors:"
        tail -100 storage/logs/laravel.log | grep "ERROR\|CRITICAL\|EMERGENCY" | tail -3 | sed 's/^/    /'
    else
        echo "  ✅ No recent errors (last 100 lines)"
    fi
else
    echo "  ℹ️  No log file yet (this is normal for new deployment)"
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# 9. DISK SPACE
#═══════════════════════════════════════════════════════════════════

echo "💿 Disk Usage:"
echo "───────────────────────────────────────────────────────────────────"

PROJECT_SIZE=$(du -sh "$PROJECT_ROOT" 2>/dev/null | cut -f1)
echo "  Project size: $PROJECT_SIZE"

STORAGE_SIZE=$(du -sh "$PROJECT_ROOT/storage" 2>/dev/null | cut -f1)
echo "  Storage size: $STORAGE_SIZE"

VENDOR_SIZE=$(du -sh "$PROJECT_ROOT/vendor" 2>/dev/null | cut -f1)
echo "  Vendor size: $VENDOR_SIZE"

echo ""

#═══════════════════════════════════════════════════════════════════
# 10. OVERALL STATUS
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "📊 Overall Status"
echo "═══════════════════════════════════════════════════════════════════"

# Count issues
ISSUES=0

[ ! -f ".env" ] && ((ISSUES++))
[ ! -d "vendor" ] && ((ISSUES++))
[ "$DB_EXISTS" != "1" ] && ((ISSUES++))
[ ! -f ".htaccess" ] && ((ISSUES++))
[ "$ERROR_COUNT" -gt 5 ] && ((ISSUES++))

if [ $ISSUES -eq 0 ]; then
    echo "✅ DEPLOYMENT STATUS: HEALTHY"
    echo ""
    echo "All systems operational!"
else
    echo "⚠️  DEPLOYMENT STATUS: NEEDS ATTENTION"
    echo ""
    echo "Found $ISSUES potential issue(s). Review the details above."
fi

echo ""
echo "🌐 Website: https://coprra.com"
echo "📅 Check completed: $(date)"
echo "═══════════════════════════════════════════════════════════════════"
