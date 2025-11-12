# Root Directory Organization Plan

**Date:** 2025-01-27  
**Purpose:** Organize root directory by moving documentation and identifying temporary files

## Current State

The root directory contains over 100 files, including:
- Documentation files (*.md)
- Temporary scripts (*.py, *.sh, *.php)
- Build artifacts
- Configuration files
- Temporary directories

## Organization Plan

### 1. Documentation Files

**Location:** `docs/` directory

**Subdirectories:**
- `docs/reports/` - Reports, summaries, status files
- `docs/missions/` - Mission-related documentation
- `docs/audits/` - Audit reports
- `docs/deployment/` - Deployment guides and instructions

**Files to Move:**
- `*_REPORT*.md` → `docs/reports/`
- `*_SUMMARY*.md` → `docs/reports/`
- `*_STATUS*.md` → `docs/reports/`
- `MISSION_*.md` → `docs/missions/`
- `*_MISSION*.md` → `docs/missions/`
- `*_AUDIT*.md` → `docs/audits/`
- `AUDIT_*.md` → `docs/audits/`
- `DEPLOYMENT*.md` → `docs/deployment/`
- `SENTRY_*.md` → `docs/` (root of docs)
- `TECHNICAL_*.md` → `docs/`
- `COMPREHENSIVE_*.md` → `docs/`
- `FINAL_*.md` → `docs/reports/`
- `EXECUTIVE_*.md` → `docs/reports/`
- `COMPLETE_*.md` → `docs/reports/`

### 2. Temporary Directories

**Action:** Add to `.gitignore`

**Directories:**
- `dkim_keys/` - DKIM cryptographic keys (sensitive)
- `downloaded-ci/` - CI/CD artifacts
- `downloaded-ci-test-results/` - Test result artifacts
- `final_audit_outputs/` - Audit output files
- `raw_outputs/` - Raw analysis outputs
- `generated/` - Generated files
- `local/` - Local temporary files

### 3. Temporary Scripts

**Location:** Keep in root or move to `scripts/`

**Files to Review:**
- `*.py` scripts (deployment, automation)
- `*.sh` scripts (deployment, automation)
- `*.php` scripts (one-off fixes, utilities)

**Recommendation:** 
- Keep essential scripts in `scripts/`
- Archive or remove one-off utility scripts
- Document purpose of remaining scripts

### 4. Build Artifacts

**Action:** Ensure in `.gitignore`

**Files:**
- `*.zip` (except essential)
- `*.json` (reports, not config)
- `*.csv` (analysis outputs)

## Implementation

### Step 1: Update .gitignore

✅ **Completed:** Added temporary directories to `.gitignore`

### Step 2: Move Documentation

**Manual Process (Recommended):**
1. Review each file before moving
2. Ensure no broken references
3. Update any links in documentation
4. Commit changes incrementally

**Automated Process (Use with Caution):**
```bash
bash scripts/organize-root-directory.sh
```

### Step 3: Clean Up Temporary Files

**Review and Remove:**
- Old deployment scripts
- One-off fix scripts
- Duplicate files
- Build artifacts

## Files Already Organized

The following files are already in appropriate locations:
- `docs/CSP_CONFIGURATION.md` ✅
- `docs/DEPENDENCY_AUDIT_INSTRUCTIONS.md` ✅
- `docs/GIT_SECRETS_SCAN_INSTRUCTIONS.md` ✅
- `docs/STALE_BRANCHES_ANALYSIS.md` ✅
- `docs/TECHNICAL_AUDIT_REPORT.md` ✅

## Notes

- ⚠️ **Do not delete files without review** - Some may be referenced
- ⚠️ **Test after moving** - Ensure no broken links
- ⚠️ **Update documentation** - Fix any references to moved files
- ✅ **Backup first** - Create a backup before major reorganization

## Next Steps

1. ✅ Update `.gitignore` with temporary directories
2. ⏳ Manually review and move documentation files
3. ⏳ Clean up temporary scripts
4. ⏳ Remove build artifacts
5. ⏳ Update any references to moved files

