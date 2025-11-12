#!/bin/bash
# Git Secrets Scanner
# Scans entire Git history for accidentally committed secrets

set -e

echo "============================================================"
echo "Git History Secrets Scanner"
echo "============================================================"
echo "Date: $(date)"
echo ""

# Check if git is available
if ! command -v git &> /dev/null; then
    echo "❌ Error: git not found"
    exit 1
fi

# Output file
OUTPUT_FILE="git_secrets_scan_$(date +%Y%m%d_%H%M%S).txt"
JSON_OUTPUT="git_secrets_scan_$(date +%Y%m%d_%H%M%S).json"

echo "📁 Scanning Git repository..."
echo "Output will be saved to: $OUTPUT_FILE"
echo ""

# Common secret patterns
declare -a PATTERNS=(
    "password\s*=\s*['\"][^'\"]+['\"]"
    "PASSWORD\s*=\s*['\"][^'\"]+['\"]"
    "secret\s*=\s*['\"][^'\"]+['\"]"
    "SECRET\s*=\s*['\"][^'\"]+['\"]"
    "api[_-]?key\s*=\s*['\"][^'\"]+['\"]"
    "API[_-]?KEY\s*=\s*['\"][^'\"]+['\"]"
    "access[_-]?token\s*=\s*['\"][^'\"]+['\"]"
    "ACCESS[_-]?TOKEN\s*=\s*['\"][^'\"]+['\"]"
    "private[_-]?key\s*=\s*['\"][^'\"]+['\"]"
    "PRIVATE[_-]?KEY\s*=\s*['\"][^'\"]+['\"]"
    "-----BEGIN\s+(RSA\s+)?PRIVATE\s+KEY-----"
    "-----BEGIN\s+OPENSSH\s+PRIVATE\s+KEY-----"
    "sk_live_[0-9a-zA-Z]{24,}"
    "sk_test_[0-9a-zA-Z]{24,}"
    "pk_live_[0-9a-zA-Z]{24,}"
    "pk_test_[0-9a-zA-Z]{24,}"
    "AKIA[0-9A-Z]{16}"
    "AIza[0-9A-Za-z\\-_]{35}"
    "ya29\\.[0-9A-Za-z\\-_]+"
    "[0-9]+-[0-9A-Za-z_]{32}\\.apps\\.googleusercontent\\.com"
    "xox[baprs]-[0-9]{12}-[0-9]{12}-[0-9]{12}-[a-z0-9]{32}"
    "ghp_[0-9a-zA-Z]{36}"
    "gho_[0-9a-zA-Z]{36}"
    "ghu_[0-9a-zA-Z]{36}"
    "ghs_[0-9a-zA-Z]{36}"
    "ghr_[0-9a-zA-Z]{36}"
    "github_pat_[0-9a-zA-Z]{22}_[0-9a-zA-Z]{59}"
    "xoxp-[0-9]{12}-[0-9]{12}-[0-9]{12}-[a-z0-9]{32}"
    "xoxa-[0-9]{12}-[0-9]{12}-[a-z0-9]{32}"
    "xoxb-[0-9]{12}-[0-9]{12}-[a-z0-9]{32}"
    "xoxo-[0-9]{12}-[0-9]{12}-[a-z0-9]{32}"
    "mongodb\\+srv://[^:]+:[^@]+@[^/]+/[^?]+"
    "postgres://[^:]+:[^@]+@[^/]+/[^?]+"
    "mysql://[^:]+:[^@]+@[^/]+/[^?]+"
    "redis://[^:]+:[^@]+@[^/]+"
    "DATABASE_URL=[^\\s]+"
    "DB_PASSWORD=[^\\s]+"
    "DB_PASS=[^\\s]+"
    "\\.env"
    "SENTRY_AUTH_TOKEN"
    "GITHUB_TOKEN"
    "GITLAB_TOKEN"
    "BITBUCKET_TOKEN"
    "JWT_SECRET"
    "SESSION_SECRET"
    "ENCRYPTION_KEY"
)

echo "🔍 Scanning for common secret patterns..."
echo ""

# Initialize JSON output
echo "{" > "$JSON_OUTPUT"
echo "  \"scan_date\": \"$(date -Iseconds)\"," >> "$JSON_OUTPUT"
echo "  \"repository\": \"$(git remote get-url origin 2>/dev/null || echo 'local')\"," >> "$JSON_OUTPUT"
echo "  \"total_commits\": $(git rev-list --all --count)," >> "$JSON_OUTPUT"
echo "  \"findings\": [" >> "$JSON_OUTPUT"

FINDINGS_COUNT=0
FIRST_FINDING=true

# Scan each commit
for commit in $(git rev-list --all); do
    COMMIT_HASH=$(git rev-parse --short "$commit")
    COMMIT_DATE=$(git log -1 --format=%ci "$commit")
    COMMIT_AUTHOR=$(git log -1 --format=%an "$commit")
    COMMIT_MESSAGE=$(git log -1 --format=%s "$commit")
    
    # Check each pattern
    for pattern in "${PATTERNS[@]}"; do
        # Search in commit diff
        if git show "$commit" | grep -iE "$pattern" > /dev/null 2>&1; then
            FINDINGS_COUNT=$((FINDINGS_COUNT + 1))
            
            # Add comma if not first finding
            if [ "$FIRST_FINDING" = false ]; then
                echo "," >> "$JSON_OUTPUT"
            fi
            FIRST_FINDING=false
            
            # Extract matching lines
            MATCHES=$(git show "$commit" | grep -iE "$pattern" | head -5)
            
            echo "============================================================" >> "$OUTPUT_FILE"
            echo "Finding #$FINDINGS_COUNT" >> "$OUTPUT_FILE"
            echo "Pattern: $pattern" >> "$OUTPUT_FILE"
            echo "Commit: $COMMIT_HASH" >> "$OUTPUT_FILE"
            echo "Date: $COMMIT_DATE" >> "$OUTPUT_FILE"
            echo "Author: $COMMIT_AUTHOR" >> "$OUTPUT_FILE"
            echo "Message: $COMMIT_MESSAGE" >> "$OUTPUT_FILE"
            echo "Matches:" >> "$OUTPUT_FILE"
            echo "$MATCHES" >> "$OUTPUT_FILE"
            echo "" >> "$OUTPUT_FILE"
            
            # Add to JSON
            echo "    {" >> "$JSON_OUTPUT"
            echo "      \"id\": $FINDINGS_COUNT," >> "$JSON_OUTPUT"
            echo "      \"pattern\": \"$pattern\"," >> "$JSON_OUTPUT"
            echo "      \"commit_hash\": \"$COMMIT_HASH\"," >> "$JSON_OUTPUT"
            echo "      \"commit_date\": \"$COMMIT_DATE\"," >> "$JSON_OUTPUT"
            echo "      \"author\": \"$COMMIT_AUTHOR\"," >> "$JSON_OUTPUT"
            echo "      \"message\": \"$COMMIT_MESSAGE\"," >> "$JSON_OUTPUT"
            # Convert matches to JSON array (without jq dependency)
            if command -v jq &> /dev/null; then
                echo "      \"matches\": $(echo "$MATCHES" | jq -R -s -c 'split("\n") | map(select(. != ""))')" >> "$JSON_OUTPUT"
            else
                # Manual JSON array conversion without jq
                echo -n "      \"matches\": [" >> "$JSON_OUTPUT"
                FIRST_MATCH=1
                echo "$MATCHES" | while IFS= read -r line; do
                    if [ -n "$line" ]; then
                        if [ $FIRST_MATCH -eq 1 ]; then
                            FIRST_MATCH=0
                        else
                            echo -n "," >> "$JSON_OUTPUT"
                        fi
                        # Escape JSON special characters
                        ESCAPED_LINE=$(echo "$line" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g' | sed 's/\t/\\t/g' | sed 's/\r/\\r/g' | sed 's/\n/\\n/g')
                        echo -n "\"$ESCAPED_LINE\"" >> "$JSON_OUTPUT"
                    fi
                done
                echo "]" >> "$JSON_OUTPUT"
            fi
            echo "    }" >> "$JSON_OUTPUT"
        fi
    done
done

# Close JSON
echo "  ]," >> "$JSON_OUTPUT"
echo "  \"total_findings\": $FINDINGS_COUNT" >> "$JSON_OUTPUT"
echo "}" >> "$JSON_OUTPUT"

echo ""
echo "============================================================"
echo "Scan Complete"
echo "============================================================"
echo ""
echo "Total findings: $FINDINGS_COUNT"
echo ""
echo "Reports saved to:"
echo "  - $OUTPUT_FILE (human-readable)"
echo "  - $JSON_OUTPUT (JSON format)"
echo ""

if [ $FINDINGS_COUNT -gt 0 ]; then
    echo "⚠️  WARNING: Secrets found in Git history!"
    echo ""
    echo "Recommended actions:"
    echo "1. Review all findings in $OUTPUT_FILE"
    echo "2. Invalidate any exposed credentials immediately"
    echo "3. Consider using git-filter-repo to remove secrets from history"
    echo "4. Rotate all potentially exposed keys/tokens"
    echo "5. Add .gitignore rules to prevent future commits"
    echo ""
else
    echo "✅ No secrets found in Git history"
    echo ""
fi

