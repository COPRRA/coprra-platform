# Server File Permissions Audit Guide

**Date:** 2025-01-27  
**Purpose:** Document server file permissions audit process

## Overview

This document provides instructions for auditing file and directory permissions on the production server to ensure security best practices.

## Recommended Permissions

### Directories
- **Standard directories:** `755` (rwxr-xr-x)
- **Writable directories (storage, cache):** `775` (rwxrwxr-x)

### Files
- **Standard files:** `644` (rw-r--r--)
- **Executable files:** `755` (rwxr-xr-x)
- **Sensitive files (.env):** `600` (rw-------)

## Critical Paths to Check

### Storage Directories (Must be Writable)
- `storage/` - `775`
- `storage/app/` - `775`
- `storage/framework/` - `775`
- `storage/framework/cache/` - `775`
- `storage/framework/sessions/` - `775`
- `storage/framework/views/` - `775`
- `storage/logs/` - `775`

### Cache Directories (Must be Writable)
- `bootstrap/cache/` - `775`

### Sensitive Files
- `.env` - `600` (read/write for owner only)

### Executable Files
- `artisan` - `755`

### Public Files
- `public/index.php` - `644`

## Running the Audit

### Step 1: Connect to Server

```bash
ssh -p 65002 u990109832@45.87.81.218
```

### Step 2: Navigate to Project

```bash
cd /home/u990109832/domains/coprra.com/public_html
```

### Step 3: Run Audit Script

```bash
bash scripts/check-server-permissions.sh
```

### Step 4: Review Results

The script generates two files:
- `permissions_audit_YYYYMMDD_HHMMSS.txt` - Human-readable report
- `permissions_audit_YYYYMMDD_HHMMSS.json` - JSON format for parsing

## Manual Verification

If the script is not available, check manually:

```bash
# Check storage permissions
ls -la storage/
ls -la storage/app/
ls -la storage/framework/

# Check .env permissions
ls -la .env

# Check for overly permissive files (777)
find . -type f -perm 777

# Check for overly permissive directories (777)
find . -type d -perm 777
```

## Common Issues and Fixes

### Issue: Storage Directory Not Writable

**Symptom:** Laravel cannot write to storage, causing errors

**Fix:**
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Issue: .env File Too Permissive

**Symptom:** Environment file readable by others

**Fix:**
```bash
chmod 600 .env
```

### Issue: Overly Permissive Files (777)

**Symptom:** Files are world-writable

**Fix:**
```bash
# Find and fix
find . -type f -perm 777 -exec chmod 644 {} \;
find . -type d -perm 777 -exec chmod 755 {} \;
```

## Security Best Practices

1. **Never use 777 permissions** - World-writable files are a security risk
2. **Protect .env file** - Use 600 permissions (owner read/write only)
3. **Limit writable directories** - Only storage and cache should be writable
4. **Regular audits** - Run permission audits regularly
5. **Document changes** - Keep track of permission changes

## Severity Levels

### High Severity
- Files/directories with `777` permissions (world-writable)
- `.env` file with permissions other than `600`
- Critical directories not writable (storage, cache)

### Medium Severity
- Standard files with incorrect permissions
- Non-critical directories with incorrect permissions

### Low Severity
- Minor permission mismatches
- Non-sensitive files with slightly incorrect permissions

## Automated Fixes

**⚠️ Use with caution - Review before applying**

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache

# Fix .env permissions
chmod 600 .env

# Fix overly permissive files
find . -type f -perm 777 -exec chmod 644 {} \;
find . -type d -perm 777 -exec chmod 755 {} \;

# Fix standard file permissions
find . -type f -not -perm 644 -not -perm 755 -exec chmod 644 {} \;
find . -type d -not -perm 755 -exec chmod 755 {} \;
```

## Notes

- ⚠️ **Always backup before changing permissions**
- ⚠️ **Test changes in staging first**
- ⚠️ **Review audit results carefully**
- ✅ **Document all permission changes**
- ✅ **Run audits regularly (monthly recommended)**

## Next Steps After Audit

1. Review audit report
2. Prioritize high-severity issues
3. Fix permissions incrementally
4. Test application functionality
5. Document changes made
6. Schedule next audit

