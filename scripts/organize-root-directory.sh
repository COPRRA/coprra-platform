#!/bin/bash
# Organize Root Directory Script
# Moves documentation and temporary files to appropriate locations

set -e

echo "============================================================"
echo "Root Directory Organization"
echo "============================================================"
echo ""

# Create docs subdirectories if they don't exist
mkdir -p docs/reports
mkdir -p docs/missions
mkdir -p docs/audits
mkdir -p docs/deployment

echo "📁 Created docs subdirectories"
echo ""

# Move documentation files to docs/
echo "Moving documentation files..."

# Reports
mv *_REPORT*.md docs/reports/ 2>/dev/null || true
mv *_SUMMARY*.md docs/reports/ 2>/dev/null || true
mv *_STATUS*.md docs/reports/ 2>/dev/null || true

# Mission files
mv MISSION_*.md docs/missions/ 2>/dev/null || true
mv *_MISSION*.md docs/missions/ 2>/dev/null || true

# Audit files
mv *_AUDIT*.md docs/audits/ 2>/dev/null || true
mv AUDIT_*.md docs/audits/ 2>/dev/null || true

# Deployment files
mv DEPLOYMENT*.md docs/deployment/ 2>/dev/null || true
mv deployment*.md docs/deployment/ 2>/dev/null || true

# Sentry files
mv SENTRY_*.md docs/ 2>/dev/null || true

# Other documentation
mv TECHNICAL_*.md docs/ 2>/dev/null || true
mv COMPREHENSIVE_*.md docs/ 2>/dev/null || true
mv FINAL_*.md docs/reports/ 2>/dev/null || true
mv EXECUTIVE_*.md docs/reports/ 2>/dev/null || true
mv COMPLETE_*.md docs/reports/ 2>/dev/null || true

echo "✅ Documentation files moved"
echo ""

# Update .gitignore for temporary directories
echo "Updating .gitignore..."

# Check if dkim_keys is already in .gitignore
if ! grep -q "^dkim_keys/" .gitignore && ! grep -q "^/dkim_keys" .gitignore; then
    echo "" >> .gitignore
    echo "# DKIM keys directory" >> .gitignore
    echo "dkim_keys/" >> .gitignore
    echo "✅ Added dkim_keys/ to .gitignore"
fi

# Add temporary directories if not present
TEMP_DIRS=("downloaded-ci" "raw_outputs" "final_audit_outputs" "generated" "local")

for dir in "${TEMP_DIRS[@]}"; do
    if ! grep -q "^$dir/" .gitignore && ! grep -q "^/$dir" .gitignore; then
        echo "$dir/" >> .gitignore
        echo "✅ Added $dir/ to .gitignore"
    fi
done

echo ""
echo "============================================================"
echo "Organization Complete"
echo "============================================================"
echo ""
echo "Documentation moved to:"
echo "  - docs/reports/ - Reports and summaries"
echo "  - docs/missions/ - Mission documentation"
echo "  - docs/audits/ - Audit reports"
echo "  - docs/deployment/ - Deployment guides"
echo ""
echo ".gitignore updated with temporary directories"
echo ""

