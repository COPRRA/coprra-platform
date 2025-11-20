# Dependency Audit Instructions

## Overview

This document provides instructions for auditing dependencies for vulnerabilities on the production server.

## Prerequisites

- SSH access to production server
- Composer installed
- NPM installed (if Node.js dependencies exist)

## Execution Steps

### 1. Connect to Production Server

```bash
ssh -p 65002 u990109832@45.87.81.218
```

### 2. Navigate to Project Directory

```bash
cd /home/u990109832/domains/coprra.com/public_html
```

### 3. Run Audit Script

```bash
bash scripts/audit-dependencies.sh
```

### 4. Review Results

The script will:
- Run `composer audit` and save results to `/tmp/composer_audit.json`
- Run `npm audit` and save results to `/tmp/npm_audit.json`
- Display summary of vulnerabilities found

### 5. Manual Audit (Alternative)

If the script is not available, run manually:

```bash
# Composer audit
composer audit

# NPM audit
npm audit
```

## Interpreting Results

### Composer Audit

- Exit code 0: No vulnerabilities found
- Exit code > 0: Vulnerabilities found
- Check severity levels: Critical, High, Medium, Low

### NPM Audit

- Exit code 0: No vulnerabilities found
- Exit code > 0: Vulnerabilities found
- Check severity levels: Critical, High, Moderate, Low, Info

## Next Steps

After running the audit:

1. **Review vulnerabilities** - Check the JSON reports for details
2. **Assess severity** - Prioritize Critical and High severity issues
3. **Plan updates** - Determine which packages need updates
4. **Test updates** - Update packages in development first
5. **Deploy fixes** - Apply updates to production after testing

## Important Notes

- **Do not automatically fix vulnerabilities** - Review each one first
- Some vulnerabilities may be false positives
- Updates may introduce breaking changes
- Always test updates in development/staging first

