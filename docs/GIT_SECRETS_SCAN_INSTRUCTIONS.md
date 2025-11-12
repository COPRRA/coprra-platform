# Git Secrets Scanning Instructions

## Overview

This document provides instructions for scanning Git history for accidentally committed secrets (API keys, passwords, tokens, etc.).

## Why This Matters

Secrets committed to Git history remain accessible even if removed in later commits. This poses a security risk if the repository is public or compromised.

## Tools Available

### Option 1: Custom Script (Recommended)

Use the provided script:

```bash
bash scripts/scan-git-secrets.sh
```

This script:
- Scans entire Git history
- Checks for common secret patterns
- Generates human-readable and JSON reports
- Identifies commits containing potential secrets

### Option 2: Gitleaks (Professional Tool)

Install gitleaks:

```bash
# Linux/Mac
brew install gitleaks
# or
wget https://github.com/gitleaks/gitleaks/releases/download/v8.18.0/gitleaks_8.18.0_linux_x64.tar.gz
tar -xzf gitleaks_8.18.0_linux_x64.tar.gz
sudo mv gitleaks /usr/local/bin/
```

Run scan:

```bash
gitleaks detect --source . --verbose --report gitleaks-report.json
```

### Option 3: Git-Secrets (AWS Tool)

Install git-secrets:

```bash
git clone https://github.com/awslabs/git-secrets.git
cd git-secrets
sudo make install
```

Configure:

```bash
git secrets --install
git secrets --register-aws
```

Scan:

```bash
git secrets --scan-history
```

## What Gets Scanned

The scanner looks for:

- **Passwords**: `password=`, `PASSWORD=`
- **API Keys**: `api_key=`, `API_KEY=`
- **Tokens**: `access_token=`, `ACCESS_TOKEN=`
- **Private Keys**: RSA, OpenSSH private keys
- **Stripe Keys**: `sk_live_`, `sk_test_`, `pk_live_`, `pk_test_`
- **AWS Keys**: `AKIA[0-9A-Z]{16}`
- **Google Keys**: `AIza[0-9A-Za-z\\-_]{35}`
- **GitHub Tokens**: `ghp_`, `github_pat_`
- **Slack Tokens**: `xoxb-`, `xoxp-`
- **Database URLs**: `mongodb+srv://`, `postgres://`, `mysql://`
- **Environment Files**: `.env` references
- **Other Secrets**: JWT secrets, session secrets, encryption keys

## Interpreting Results

### If Secrets Are Found

1. **Immediate Actions:**
   - Review each finding in the report
   - Identify which secrets are real vs false positives
   - Invalidate exposed credentials immediately
   - Rotate all potentially exposed keys/tokens

2. **Cleanup Options:**
   - **Option A**: Use `git-filter-repo` to remove secrets from history
   - **Option B**: Create new repository without history (if acceptable)
   - **Option C**: Use BFG Repo-Cleaner for large repositories

3. **Prevention:**
   - Add `.gitignore` rules for sensitive files
   - Use pre-commit hooks to prevent future commits
   - Use environment variables instead of hardcoded secrets
   - Use secret management tools (Vault, AWS Secrets Manager, etc.)

### Example: Using git-filter-repo

```bash
# Install git-filter-repo
pip install git-filter-repo

# Remove file containing secrets
git filter-repo --path path/to/file --invert-paths

# Remove specific string from history
git filter-repo --replace-text <(echo "old_secret==>new_secret")

# Force push (WARNING: Rewrites history)
git push origin --force --all
```

## Best Practices

1. **Never commit secrets** - Use environment variables
2. **Use .gitignore** - Add `.env`, `*.key`, `*.pem`, etc.
3. **Use pre-commit hooks** - Automatically scan before commits
4. **Rotate secrets regularly** - Even if not exposed
5. **Use secret scanning in CI/CD** - Catch issues early
6. **Review commits** - Check diffs before pushing

## Pre-commit Hook Example

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash
# Run gitleaks before commit
if command -v gitleaks &> /dev/null; then
    gitleaks protect --staged
    if [ $? -ne 0 ]; then
        echo "❌ Gitleaks found secrets. Commit aborted."
        exit 1
    fi
fi
```

Make it executable:

```bash
chmod +x .git/hooks/pre-commit
```

## Reporting

After scanning, report findings:

1. **List all exposed secrets** (without showing actual values)
2. **Categorize by severity** (Critical, High, Medium, Low)
3. **Recommend actions** for each finding
4. **Document cleanup steps** taken
5. **Update security policies** to prevent recurrence

## Important Notes

- ⚠️ **Do not share scan reports publicly** - They may contain sensitive information
- ⚠️ **Review findings carefully** - Some may be false positives
- ⚠️ **Invalidate secrets immediately** - Don't wait for cleanup
- ⚠️ **Coordinate with team** - If history is rewritten, everyone needs to re-clone

