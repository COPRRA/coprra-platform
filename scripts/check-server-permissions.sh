#!/bin/bash
# Server File Permissions Audit Script
# Checks file and directory permissions on production server

set -e

echo "============================================================"
echo "Server File Permissions Audit"
echo "============================================================"
echo "Date: $(date)"
echo ""

PROJECT_DIR="/home/u990109832/domains/coprra.com/public_html"
cd "$PROJECT_DIR" || exit 1

echo "📁 Project Directory: $PROJECT_DIR"
echo ""

# Output file
OUTPUT_FILE="permissions_audit_$(date +%Y%m%d_%H%M%S).txt"
JSON_OUTPUT="permissions_audit_$(date +%Y%m%d_%H%M%S).json"

echo "🔍 Checking file permissions..."
echo ""

# Initialize JSON output
echo "{" > "$JSON_OUTPUT"
echo "  \"audit_date\": \"$(date -Iseconds)\"," >> "$JSON_OUTPUT"
echo "  \"project_directory\": \"$PROJECT_DIR\"," >> "$JSON_OUTPUT"
echo "  \"findings\": [" >> "$JSON_OUTPUT"

FINDINGS_COUNT=0
FIRST_FINDING=true

# Function to check permissions
check_permissions() {
    local path=$1
    local expected_file=$2
    local expected_dir=$3
    local description=$4
    
    if [ ! -e "$path" ]; then
        return
    fi
    
    local perms=$(stat -c "%a" "$path" 2>/dev/null || stat -f "%OLp" "$path" 2>/dev/null)
    local type=$(stat -c "%F" "$path" 2>/dev/null | grep -q "directory" && echo "directory" || echo "file")
    
    local expected=$expected_file
    if [ "$type" = "directory" ]; then
        expected=$expected_dir
    fi
    
    if [ "$perms" != "$expected" ]; then
        FINDINGS_COUNT=$((FINDINGS_COUNT + 1))
        
        # Add comma if not first finding
        if [ "$FIRST_FINDING" = false ]; then
            echo "," >> "$JSON_OUTPUT"
        fi
        FIRST_FINDING=false
        
        echo "============================================================" >> "$OUTPUT_FILE"
        echo "Finding #$FINDINGS_COUNT" >> "$OUTPUT_FILE"
        echo "Path: $path" >> "$OUTPUT_FILE"
        echo "Type: $type" >> "$OUTPUT_FILE"
        echo "Current Permissions: $perms" >> "$OUTPUT_FILE"
        echo "Expected Permissions: $expected" >> "$OUTPUT_FILE"
        echo "Description: $description" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        
        # Add to JSON
        echo "    {" >> "$JSON_OUTPUT"
        echo "      \"id\": $FINDINGS_COUNT," >> "$JSON_OUTPUT"
        echo "      \"path\": \"$path\"," >> "$JSON_OUTPUT"
        echo "      \"type\": \"$type\"," >> "$JSON_OUTPUT"
        echo "      \"current_permissions\": \"$perms\"," >> "$JSON_OUTPUT"
        echo "      \"expected_permissions\": \"$expected\"," >> "$JSON_OUTPUT"
        echo "      \"description\": \"$description\"," >> "$JSON_OUTPUT"
        echo "      \"severity\": \"$(determine_severity "$perms" "$expected")\"" >> "$JSON_OUTPUT"
        echo "    }" >> "$JSON_OUTPUT"
    fi
}

# Determine severity
determine_severity() {
    local current=$1
    local expected=$2
    
    # Check for overly permissive (777, 666)
    if [[ "$current" == *"7"* ]] || [[ "$current" == *"6"* ]]; then
        echo "high"
    elif [ "$current" != "$expected" ]; then
        echo "medium"
    else
        echo "low"
    fi
}

# Check critical directories
echo "Checking critical directories..."
check_permissions "storage" "644" "755" "Storage directory"
check_permissions "storage/app" "644" "775" "Storage app directory (writable)"
check_permissions "storage/framework" "644" "775" "Storage framework directory (writable)"
check_permissions "storage/framework/cache" "644" "775" "Storage cache directory (writable)"
check_permissions "storage/framework/sessions" "644" "775" "Storage sessions directory (writable)"
check_permissions "storage/framework/views" "644" "775" "Storage views directory (writable)"
check_permissions "storage/logs" "644" "775" "Storage logs directory (writable)"
check_permissions "bootstrap/cache" "644" "775" "Bootstrap cache directory (writable)"

# Check critical files
echo "Checking critical files..."
check_permissions ".env" "600" "755" "Environment file (should be 600)"
check_permissions "artisan" "755" "755" "Artisan command (should be executable)"
check_permissions "public/index.php" "644" "755" "Public index file"

# Check for overly permissive files (777, 666)
echo "Scanning for overly permissive files..."
find . -type f -perm 777 -o -type f -perm 666 2>/dev/null | while read -r file; do
    FINDINGS_COUNT=$((FINDINGS_COUNT + 1))
    
    if [ "$FIRST_FINDING" = false ]; then
        echo "," >> "$JSON_OUTPUT"
    fi
    FIRST_FINDING=false
    
    perms=$(stat -c "%a" "$file" 2>/dev/null || stat -f "%OLp" "$file" 2>/dev/null)
    
    echo "============================================================" >> "$OUTPUT_FILE"
    echo "Finding #$FINDINGS_COUNT - OVERLY PERMISSIVE" >> "$OUTPUT_FILE"
    echo "Path: $file" >> "$OUTPUT_FILE"
    echo "Permissions: $perms" >> "$OUTPUT_FILE"
    echo "Severity: HIGH - File is world-writable" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    
    echo "    {" >> "$JSON_OUTPUT"
    echo "      \"id\": $FINDINGS_COUNT," >> "$JSON_OUTPUT"
    echo "      \"path\": \"$file\"," >> "$JSON_OUTPUT"
    echo "      \"type\": \"file\"," >> "$JSON_OUTPUT"
    echo "      \"current_permissions\": \"$perms\"," >> "$JSON_OUTPUT"
    echo "      \"expected_permissions\": \"644\"," >> "$JSON_OUTPUT"
    echo "      \"description\": \"File is overly permissive (world-writable)\"," >> "$JSON_OUTPUT"
    echo "      \"severity\": \"high\"" >> "$JSON_OUTPUT"
    echo "    }" >> "$JSON_OUTPUT"
done

# Check for overly permissive directories (777)
echo "Scanning for overly permissive directories..."
find . -type d -perm 777 2>/dev/null | while read -r dir; do
    FINDINGS_COUNT=$((FINDINGS_COUNT + 1))
    
    if [ "$FIRST_FINDING" = false ]; then
        echo "," >> "$JSON_OUTPUT"
    fi
    FIRST_FINDING=false
    
    perms=$(stat -c "%a" "$dir" 2>/dev/null || stat -f "%OLp" "$dir" 2>/dev/null)
    
    echo "============================================================" >> "$OUTPUT_FILE"
    echo "Finding #$FINDINGS_COUNT - OVERLY PERMISSIVE DIRECTORY" >> "$OUTPUT_FILE"
    echo "Path: $dir" >> "$OUTPUT_FILE"
    echo "Permissions: $perms" >> "$OUTPUT_FILE"
    echo "Severity: HIGH - Directory is world-writable" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    
    echo "    {" >> "$JSON_OUTPUT"
    echo "      \"id\": $FINDINGS_COUNT," >> "$JSON_OUTPUT"
    echo "      \"path\": \"$dir\"," >> "$JSON_OUTPUT"
    echo "      \"type\": \"directory\"," >> "$JSON_OUTPUT"
    echo "      \"current_permissions\": \"$perms\"," >> "$JSON_OUTPUT"
    echo "      \"expected_permissions\": \"755\"," >> "$JSON_OUTPUT"
    echo "      \"description\": \"Directory is overly permissive (world-writable)\"," >> "$JSON_OUTPUT"
    echo "      \"severity\": \"high\"" >> "$JSON_OUTPUT"
    echo "    }" >> "$JSON_OUTPUT"
done

# Close JSON
echo "  ]," >> "$JSON_OUTPUT"
echo "  \"total_findings\": $FINDINGS_COUNT" >> "$JSON_OUTPUT"
echo "}" >> "$JSON_OUTPUT"

echo ""
echo "============================================================"
echo "Audit Complete"
echo "============================================================"
echo ""
echo "Total findings: $FINDINGS_COUNT"
echo ""
echo "Reports saved to:"
echo "  - $OUTPUT_FILE (human-readable)"
echo "  - $JSON_OUTPUT (JSON format)"
echo ""

if [ $FINDINGS_COUNT -gt 0 ]; then
    echo "⚠️  WARNING: Permission issues found!"
    echo ""
    echo "Recommended actions:"
    echo "1. Review all findings in $OUTPUT_FILE"
    echo "2. Fix high-severity issues immediately"
    echo "3. Update medium-severity issues as needed"
    echo ""
    echo "Example fixes:"
    echo "  chmod 755 storage"
    echo "  chmod 775 storage/app"
    echo "  chmod 600 .env"
    echo ""
else
    echo "✅ No permission issues found"
    echo ""
fi

