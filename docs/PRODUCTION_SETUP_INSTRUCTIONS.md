# Production Setup & Verification Instructions

**Date:** 2025-01-27  
**Purpose:** Complete production server setup and verification

---

## Overview

This guide provides instructions for completing the production setup after a clean git clone and running verification audits.

---

## Prerequisites

- ✅ Clean git clone completed in `/home/u990109832/domains/coprra.com/public_html`
- ✅ Base `.env` file created from `.env.example`
- ✅ `APP_KEY` generated
- ✅ SSH access to production server

---

## Execution Steps

### Step 1: Connect to Production Server

```bash
ssh -p 65002 u990109832@45.87.81.218
```

### Step 2: Navigate to Project Directory

```bash
cd /home/u990109832/domains/coprra.com/public_html
```

### Step 3: Run Complete Setup Script

**Option A: Automated Script (Recommended)**

```bash
# Make script executable
chmod +x scripts/production-setup-complete.sh

# Run the complete setup script
bash scripts/production-setup-complete.sh
```

This script will:
1. ✅ Configure `.env` file with all production values
2. ✅ Install Composer dependencies
3. ✅ Create storage link
4. ✅ Clear all caches
5. ✅ Execute all verification audits

**Option B: Manual Execution**

Follow the steps below if you prefer manual execution.

---

## Manual Setup Steps

### 1. Configure .env File

Edit the `.env` file:

```bash
nano .env
```

Set the following values:

```env
APP_NAME="COPRRA"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://coprra.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u990109832_coprra_db
DB_USERNAME=u990109832_gasser
DB_PASSWORD=Hamo1510@Rayan146

SENTRY_LARAVEL_DSN=https://2c4a83601aa63d57b84bcaac47290c13@o4510335302696960.ingest.de.sentry.io/4510335304859728
GOOGLE_ANALYTICS_ID=G-G90X9EXPBC
```

Save and exit (Ctrl+X, Y, Enter)

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Create Storage Link

```bash
php artisan storage:link
```

### 4. Clear All Caches

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 5. Run Verification Audits

```bash
# Make scripts executable
chmod +x scripts/*.sh

# Run dependency audit
bash scripts/audit-dependencies.sh

# Run Git secrets scan
bash scripts/scan-git-secrets.sh

# Run permissions audit
bash scripts/check-server-permissions.sh
```

---

## Verification Checklist

After setup, verify:

- [ ] `.env` file contains all production values
- [ ] `composer install` completed without errors
- [ ] Storage link created successfully
- [ ] All caches cleared
- [ ] Application accessible at https://coprra.com
- [ ] Health endpoint responds: https://coprra.com/health
- [ ] All audit scripts executed successfully

---

## Expected Output

The setup script will output:

1. **Mission 1:** Server connection confirmation
2. **Mission 2:** .env configuration status
3. **Mission 3:** Dependency installation and cache clearing status
4. **Mission 4:** Full audit reports from all three scripts

---

## Troubleshooting

### Composer Install Fails

```bash
# Check PHP version
php -v

# Check Composer version
composer --version

# Try with verbose output
composer install --no-dev --optimize-autoloader -vvv
```

### Storage Link Fails

```bash
# Remove existing link if present
rm public/storage

# Create link again
php artisan storage:link
```

### Permission Errors

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R u990109832:u990109832 storage bootstrap/cache
```

---

## Final Status

After successful completion:

✅ **Production setup and verification complete. The application is live and healthy.**

---

**Last Updated:** 2025-01-27  
**Status:** Ready for execution

