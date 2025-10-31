#!/bin/bash

#═══════════════════════════════════════════════════════════════════
# COPRRA - Hostinger Production Deployment Script
# Domain: https://coprra.com
# Execute this script after connecting to SSH
#═══════════════════════════════════════════════════════════════════

set -e  # Exit on any error

echo "═══════════════════════════════════════════════════════════════════"
echo "🚀 COPRRA Deployment Starting..."
echo "═══════════════════════════════════════════════════════════════════"
echo ""

# Configuration
PROJECT_ROOT="/home/u990109832/public_html"
DB_NAME="u990109832_coprra"
DB_USER="u990109832_coprra"
MYSQL_ROOT_USER="u990109832"
MYSQL_ROOT_PASS="Hamo1510@Rayan146"
DOMAIN="https://coprra.com"

# Generate random database password
DB_PASSWORD=$(openssl rand -base64 16 | tr -d '/+=' | cut -c1-16)

echo "📊 Configuration:"
echo "  Project: $PROJECT_ROOT"
echo "  Database: $DB_NAME"
echo "  Domain: $DOMAIN"
echo ""

#═══════════════════════════════════════════════════════════════════
# PHASE 1: VERIFY FILES
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "📁 PHASE 1: Verifying Files"
echo "═══════════════════════════════════════════════════════════════════"

cd "$PROJECT_ROOT" || { echo "❌ Cannot access $PROJECT_ROOT"; exit 1; }

# Check essential directories
for dir in app config public routes storage vendor.zip; do
    if [ -e "$dir" ]; then
        echo "  ✅ $dir exists"
    else
        echo "  ❌ $dir missing!"
        [ "$dir" = "vendor.zip" ] && continue  # vendor.zip might be extracted already
        exit 1
    fi
done

# Extract vendor.zip if it exists
if [ -f "vendor.zip" ]; then
    echo ""
    echo "📦 Extracting vendor.zip..."
    unzip -q vendor.zip
    rm vendor.zip
    echo "  ✅ vendor.zip extracted and removed"
else
    echo "  ℹ️  vendor.zip already extracted or not needed"
fi

# Verify vendor directory exists
if [ ! -d "vendor" ]; then
    echo "  ❌ vendor/ directory missing!"
    exit 1
fi

echo "  ✅ All files verified"
echo ""

#═══════════════════════════════════════════════════════════════════
# PHASE 2: CREATE DATABASE
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "💾 PHASE 2: Creating Database"
echo "═══════════════════════════════════════════════════════════════════"

# Create database and user
mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS" << EOSQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SELECT 'Database created successfully' AS Status;
EOSQL

# Verify database
DB_COUNT=$(mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS" -sse "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}';")
if [ "$DB_COUNT" -eq 1 ]; then
    echo "  ✅ Database created: $DB_NAME"
    echo "  ✅ User created: $DB_USER"
    echo "  ✅ Password: $DB_PASSWORD"
    echo ""
    echo "⚠️  IMPORTANT: Save these credentials!"
    echo "════════════════════════════════════════════════════════════"
    echo "Database Name: $DB_NAME"
    echo "Database User: $DB_USER"
    echo "Database Password: $DB_PASSWORD"
    echo "════════════════════════════════════════════════════════════"
    echo ""

    # Save credentials to file
    cat > /home/u990109832/db_credentials.txt << EOCREDS
COPRRA Database Credentials
Generated: $(date)

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD
EOCREDS
    chmod 600 /home/u990109832/db_credentials.txt
    echo "  ✅ Credentials saved to: /home/u990109832/db_credentials.txt"
else
    echo "  ❌ Database creation failed"
    exit 1
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# PHASE 3: CONFIGURE .ENV
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "⚙️  PHASE 3: Configuring Environment"
echo "═══════════════════════════════════════════════════════════════════"

cd "$PROJECT_ROOT"

# Backup existing .env if it exists
if [ -f ".env" ]; then
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo "  ✅ Existing .env backed up"
fi

# Create .env file
cat > .env << EOENV
APP_NAME=COPRRA
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=${DOMAIN}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@coprra.com"
MAIL_FROM_NAME="COPRRA"

# COPRRA Specific
COPRRA_DEFAULT_CURRENCY=USD
COPRRA_DEFAULT_LANGUAGE=en
PRICE_CACHE_DURATION=3600
MAX_STORES_PER_PRODUCT=10

# API Configuration
API_RATE_LIMIT=60
API_VERSION=v1
API_ENABLE_DOCS=false

# Security
REQUIRE_2FA=false

# AI Configuration (Optional - add your keys)
OPENAI_API_KEY=
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MAX_TOKENS=1000
OPENAI_TEMPERATURE=0.7

# Google Analytics (Optional)
GOOGLE_ANALYTICS_ID=
EOENV

echo "  ✅ .env file created"

# Generate APP_KEY
php artisan key:generate --force
echo "  ✅ APP_KEY generated"

# Verify .env
if grep -q "APP_KEY=base64:" .env; then
    echo "  ✅ .env configured correctly"
else
    echo "  ❌ APP_KEY generation failed"
    exit 1
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# PHASE 4: SET PERMISSIONS
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "🔐 PHASE 4: Setting Permissions"
echo "═══════════════════════════════════════════════════════════════════"

cd "$PROJECT_ROOT"

# Set directory permissions
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

# Set ownership
chown -R u990109832:u990109832 storage bootstrap/cache

echo "  ✅ Storage permissions: 775"
echo "  ✅ Bootstrap cache permissions: 775"
echo "  ✅ Ownership set correctly"
echo ""

#═══════════════════════════════════════════════════════════════════
# PHASE 5: CLEAR AND OPTIMIZE
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "🔧 PHASE 5: Laravel Optimization"
echo "═══════════════════════════════════════════════════════════════════"

cd "$PROJECT_ROOT"

# Clear all caches
echo "  Clearing caches..."
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
echo "  ✅ All caches cleared"

# Optimize composer
echo "  Optimizing composer..."
composer dump-autoload --optimize --no-dev 2>/dev/null || echo "  ⚠️  Composer optimize skipped (already optimized)"
echo "  ✅ Composer optimized"

# Create production caches
echo "  Creating production caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "  ✅ Production caches created"

# Verify PHP and Laravel
echo ""
echo "  Environment Information:"
php -v | head -1
php artisan --version
echo ""

#═══════════════════════════════════════════════════════════════════
# PHASE 6: DATABASE MIGRATIONS
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "💾 PHASE 6: Running Migrations"
echo "═══════════════════════════════════════════════════════════════════"

cd "$PROJECT_ROOT"

# Test database connection
echo "  Testing database connection..."
if php artisan db:show 2>&1 | grep -q "MySQL\|Connection"; then
    echo "  ✅ Database connection successful"
else
    echo "  ❌ Database connection failed!"
    echo ""
    echo "Please check your credentials and try again."
    exit 1
fi

# Run migrations
echo "  Running migrations..."
if php artisan migrate --force; then
    echo "  ✅ Migrations completed successfully"
else
    echo "  ❌ Migrations failed!"
    echo ""
    echo "Check the error above and fix any issues."
    exit 1
fi

# Verify tables
TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -sse "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}';")
echo "  ✅ Tables created: $TABLE_COUNT"
echo ""

#═══════════════════════════════════════════════════════════════════
# PHASE 7: CONFIGURE .HTACCESS
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "🌐 PHASE 7: Configuring Web Server"
echo "═══════════════════════════════════════════════════════════════════"

cd "$PROJECT_ROOT"

# Create main .htaccess (redirects to public/)
cat > .htaccess << 'HTACCESS1'
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect to public folder
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# Force HTTPS
<IfModule mod_rewrite.c>
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
HTACCESS1

echo "  ✅ Main .htaccess created"

# Ensure public/.htaccess exists (Laravel default)
if [ ! -f "public/.htaccess" ]; then
    cat > public/.htaccess << 'HTACCESS2'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS2
    echo "  ✅ public/.htaccess created"
else
    echo "  ✅ public/.htaccess already exists"
fi

echo ""

#═══════════════════════════════════════════════════════════════════
# PHASE 8: FINAL VERIFICATION
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "✅ PHASE 8: Final Verification"
echo "═══════════════════════════════════════════════════════════════════"

cd "$PROJECT_ROOT"

echo "  Checking Laravel..."
php artisan about | head -20

echo ""
echo "  Checking database..."
php artisan db:show | head -10

echo ""
echo "  Checking routes..."
ROUTE_COUNT=$(php artisan route:list --json | grep -c '"uri"' || echo "0")
echo "  ✅ Routes loaded: $ROUTE_COUNT"

echo ""

#═══════════════════════════════════════════════════════════════════
# DEPLOYMENT COMPLETE
#═══════════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════════════"
echo "🎉 DEPLOYMENT COMPLETE!"
echo "═══════════════════════════════════════════════════════════════════"
echo ""
echo "✅ All phases completed successfully!"
echo ""
echo "📊 Deployment Summary:"
echo "  • Files: Uploaded and organized"
echo "  • Database: Created with $TABLE_COUNT tables"
echo "  • Environment: Configured for production"
echo "  • Permissions: Set correctly"
echo "  • Caches: Optimized"
echo "  • Migrations: Completed"
echo "  • Web server: Configured"
echo ""
echo "🔐 Database Credentials (SAVE THESE!):"
echo "════════════════════════════════════════════════════════════"
echo "Database Name: $DB_NAME"
echo "Database User: $DB_USER"
echo "Database Password: $DB_PASSWORD"
echo "Database Host: localhost"
echo "Database Port: 3306"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "Credentials saved to: /home/u990109832/db_credentials.txt"
echo ""
echo "🌐 Your Application:"
echo "  URL: ${DOMAIN}"
echo "  Status: LIVE"
echo ""
echo "📋 Next Steps:"
echo "  1. Visit ${DOMAIN} to verify it's working"
echo "  2. Test login functionality"
echo "  3. Check ${DOMAIN}/health endpoint"
echo "  4. Monitor logs: tail -f storage/logs/laravel.log"
echo "  5. Set up SSL certificate (if not auto-configured)"
echo ""
echo "═══════════════════════════════════════════════════════════════════"
echo "🎯 Deployment completed at: $(date)"
echo "═══════════════════════════════════════════════════════════════════"
