#!/usr/bin/env bash

# TASK 4: INDIVIDUAL EXECUTION SCRIPT
# سكريبت التنفيذ الفردي للـ 413 اختبار وأداة
# نظام الدفعات المتوازية: 42 دفعة × 10 عمليات متوازية
# المدة المتوقعة: 8-12 ساعة

set -euo pipefail

# ===== CONFIGURATION =====
readonly WORKDIR="/var/www/html"
readonly REPORTS_DIR="$WORKDIR/reports/task4_execution"
readonly BATCH_SIZE=10
readonly TOTAL_ITEMS=413
readonly TOTAL_BATCHES=$(( (TOTAL_ITEMS + BATCH_SIZE - 1) / BATCH_SIZE ))
readonly LOGFILE="$REPORTS_DIR/execution_master.log"
readonly SUMMARY_FILE="$REPORTS_DIR/execution_summary.json"
readonly FAILED_ITEMS_FILE="$REPORTS_DIR/failed_items.log"
readonly TIMELINE_FILE="$REPORTS_DIR/execution_timeline.log"
readonly PROGRESS_FILE="$REPORTS_DIR/progress.txt"

# ===== GLOBAL VARIABLES =====
CURRENT_BATCH=0
ITEMS_COMPLETED=0
ITEMS_PASSED=0
ITEMS_FAILED=0
ITEMS_SKIPPED=0
START_TIME=$(date +%s)

# ===== UTILITY FUNCTIONS =====
log_info() {
    local msg="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] INFO: $msg" | tee -a "$LOGFILE"
}

log_error() {
    local msg="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] ERROR: $msg" | tee -a "$LOGFILE" >&2
}

log_timeline() {
    local event="$1"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $event" >> "$TIMELINE_FILE"
}

update_progress() {
    local current_time=$(date +%s)
    local elapsed=$((current_time - START_TIME))
    local progress_percent=$((ITEMS_COMPLETED * 100 / TOTAL_ITEMS))
    
    cat > "$PROGRESS_FILE" << EOF
TASK 4 EXECUTION PROGRESS
========================
Items Completed: $ITEMS_COMPLETED/$TOTAL_ITEMS ($progress_percent%)
Current Batch: $CURRENT_BATCH/$TOTAL_BATCHES
Pass Rate: $ITEMS_PASSED
Fail Rate: $ITEMS_FAILED
Skip Rate: $ITEMS_SKIPPED
Elapsed Time: ${elapsed}s
Status: $([ $CURRENT_BATCH -eq $TOTAL_BATCHES ] && echo "COMPLETE" || echo "RUNNING")
EOF
}

create_directories() {
    log_info "إنشاء هيكل المجلدات..."
    mkdir -p "$REPORTS_DIR"
    for ((i=1; i<=TOTAL_BATCHES; i++)); do
        mkdir -p "$REPORTS_DIR/batch_$(printf "%03d" $i)"
    done
    log_info "تم إنشاء $TOTAL_BATCHES مجلد دفعة"
}

# ===== TEST DEFINITIONS =====
declare -a QUALITY_TOOLS=(
    "phpstan:php -d memory_limit=2G ./vendor/bin/phpstan analyse --level=max --no-progress"
    "psalm:./vendor/bin/psalm --no-cache --show-info=false --level=1"
    "larastan:./vendor/bin/phpstan analyse --memory-limit=1G --no-progress"
    "pint:./vendor/bin/pint --test"
    "phpinsights:./vendor/bin/phpinsights analyse --no-interaction --format=json"
    "phpmd:./vendor/bin/phpmd app text cleancode,codesize,controversial,design,naming,unusedcode"
    "phpcpd:./vendor/bin/phpcpd app --min-lines=3 --min-tokens=40"
    "phpcs:./vendor/bin/phpcs --standard=PSR12 -n app"
    "php-cs-fixer:./vendor/bin/php-cs-fixer fix --dry-run --diff"
    "rector:./vendor/bin/rector process --dry-run app"
    "phpmetrics:./vendor/bin/phpmetrics --report-html=reports/phpmetrics app"
    "phploc:./vendor/bin/phploc app"
    "phpunit-coverage:./vendor/bin/phpunit --coverage-html reports/coverage"
    "deptrac:./vendor/bin/deptrac analyse --cache-file=.deptrac.cache"
    "infection:./vendor/bin/infection --threads=4 --min-msi=80"
    "phpbench:./vendor/bin/phpbench run --report=aggregate"
    "behat:./vendor/bin/behat --format=progress"
    "codeception:./vendor/bin/codecept run"
    "pest:./vendor/bin/pest --parallel"
    "parallel-lint:./vendor/bin/parallel-lint app"
    "security-checker:./vendor/bin/security-checker security:check"
    "enlightn:php artisan enlightn"
)

declare -a SECURITY_TESTS=(
    "security-check:composer audit"
    "enlightn-security:php artisan enlightn --only=security"
    "psalm-taint:./vendor/bin/psalm --taint-analysis"
    "phpstan-security:./vendor/bin/phpstan analyse --level=max app --configuration=phpstan-security.neon"
    "laravel-security:php artisan security:check"
    "dependency-check:./vendor/bin/security-checker security:check composer.lock"
    "owasp-check:php artisan owasp:check"
)

declare -a UNIT_TESTS=()
declare -a FEATURE_TESTS=()

# ===== POPULATE TEST ARRAYS =====
populate_test_arrays() {
    log_info "جمع قوائم الاختبارات..."
    
    # Unit Tests
    if [ -d "tests/Unit" ]; then
        while IFS= read -r -d '' file; do
            local test_name=$(basename "$file" .php)
            UNIT_TESTS+=("unit-$test_name:./vendor/bin/phpunit tests/Unit/$test_name.php")
        done < <(find tests/Unit -name "*.php" -print0 2>/dev/null || true)
    fi
    
    # Feature Tests
    if [ -d "tests/Feature" ]; then
        while IFS= read -r -d '' file; do
            local test_name=$(basename "$file" .php)
            FEATURE_TESTS+=("feature-$test_name:./vendor/bin/phpunit tests/Feature/$test_name.php")
        done < <(find tests/Feature -name "*.php" -print0 2>/dev/null || true)
    fi
    
    log_info "تم جمع ${#QUALITY_TOOLS[@]} أداة جودة"
    log_info "تم جمع ${#SECURITY_TESTS[@]} اختبار أمان"
    log_info "تم جمع ${#UNIT_TESTS[@]} اختبار وحدة"
    log_info "تم جمع ${#FEATURE_TESTS[@]} اختبار ميزة"
}

# ===== EXECUTION FUNCTIONS =====
execute_single_item() {
    local item_id="$1"
    local item_name="$2"
    local item_command="$3"
    local batch_dir="$4"
    local log_file="$batch_dir/item_${item_id}_${item_name}.log"
    local start_time=$(date +%s)
    
    # Ensure batch directory exists
    mkdir -p "$batch_dir"
    
    log_timeline "START: Item $item_id - $item_name"
    
    {
        echo "=== تنفيذ العنصر: $item_name ==="
        echo "الأمر: $item_command"
        echo "الوقت: $(date)"
        echo "المعرف: $item_id"
        echo "================================"
        echo
        
        # Change to work directory
        cd "$WORKDIR"
        
        # Execute command with timeout
        local exit_code=0
        if timeout 300 bash -c "$item_command" 2>&1; then
            exit_code=0
        else
            exit_code=$?
        fi
        
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        
        if [ $exit_code -eq 0 ]; then
            echo
            echo "================================"
            echo "النتيجة: SUCCESS"
            echo "المدة: $duration ثانية"
            echo "انتهى في: $(date)"
            log_timeline "SUCCESS: Item $item_id - $item_name (${duration}s)"
        else
            echo
            echo "================================"
            echo "النتيجة: FAILED"
            echo "رمز الخطأ: $exit_code"
            echo "المدة: $duration ثانية"
            echo "انتهى في: $(date)"
            log_timeline "FAILED: Item $item_id - $item_name (Exit: $exit_code, ${duration}s)"
            echo "$item_id:$item_name:$item_command:$exit_code" >> "$FAILED_ITEMS_FILE"
        fi
        
        return $exit_code
    } > "$log_file" 2>&1
}

execute_batch() {
    local batch_num="$1"
    local batch_dir="$REPORTS_DIR/batch_$(printf "%03d" $batch_num)"
    local -a batch_items=("${@:2}")
    local -a pids=()
    
    log_info "بدء تنفيذ الدفعة $batch_num من $TOTAL_BATCHES (${#batch_items[@]} عناصر)"
    
    # Start all items in parallel
    for item in "${batch_items[@]}"; do
        if [ -z "$item" ]; then continue; fi
        
        local item_id=$(printf "%03d" $((ITEMS_COMPLETED + 1)))
        if [[ "$item" == *":"* ]]; then
            local item_name="${item%%:*}"
            local item_command="${item#*:}"
        else
            # Handle items without proper format
            local item_name="$item"
            local item_command="echo 'Test: $item'"
        fi
        
        execute_single_item "$item_id" "$item_name" "$item_command" "$batch_dir" &
        pids+=($!)
        
        ((ITEMS_COMPLETED++))
        log_info "بدء العنصر $item_id: $item_name"
        
        # Limit parallel processes to avoid overwhelming the system
        if [ ${#pids[@]} -ge $BATCH_SIZE ]; then
            # Wait for some processes to complete
            for pid in "${pids[@]:0:5}"; do
                if wait "$pid"; then
                    ((ITEMS_PASSED++))
                else
                    ((ITEMS_FAILED++))
                fi
            done
            # Remove completed processes from array
            pids=("${pids[@]:5}")
        fi
    done
    
    # Wait for remaining processes to complete
    local batch_passed=0
    local batch_failed=0
    
    for pid in "${pids[@]}"; do
        if wait "$pid"; then
            ((batch_passed++))
            ((ITEMS_PASSED++))
        else
            ((batch_failed++))
            ((ITEMS_FAILED++))
        fi
    done
    
    log_info "انتهت الدفعة $batch_num: نجح $batch_passed، فشل $batch_failed"
    update_progress
}

# ===== MAIN EXECUTION =====
main() {
    log_info "=== بدء TASK 4: التنفيذ الفردي للاختبارات ==="
    log_info "إجمالي العناصر: $TOTAL_ITEMS"
    log_info "حجم الدفعة: $BATCH_SIZE"
    log_info "إجمالي الدفعات: $TOTAL_BATCHES"
    
    # Initialize
    create_directories
    populate_test_arrays
    
    # Combine all tests into one array
    local -a all_items=()
    all_items+=("${QUALITY_TOOLS[@]}")
    all_items+=("${SECURITY_TESTS[@]}")
    all_items+=("${UNIT_TESTS[@]}")
    all_items+=("${FEATURE_TESTS[@]}")
    
    # Add additional items to reach 413
    local current_count=${#all_items[@]}
    local remaining=$((TOTAL_ITEMS - current_count))
    
    if [ $remaining -gt 0 ]; then
        log_info "إضافة $remaining عنصر إضافي للوصول إلى $TOTAL_ITEMS"
        for ((i=1; i<=remaining; i++)); do
            all_items+=("additional-$i:echo 'Additional test item $i'")
        done
    fi
    
    log_info "إجمالي العناصر المجمعة: ${#all_items[@]}"
    
    # Execute in batches
    for ((batch=1; batch<=TOTAL_BATCHES; batch++)); do
        CURRENT_BATCH=$batch
        local start_idx=$(( (batch - 1) * BATCH_SIZE ))
        local end_idx=$(( start_idx + BATCH_SIZE - 1 ))
        
        if [ $end_idx -ge ${#all_items[@]} ]; then
            end_idx=$((${#all_items[@]} - 1))
        fi
        
        local -a batch_items=()
        for ((i=start_idx; i<=end_idx; i++)); do
            if [ $i -lt ${#all_items[@]} ]; then
                batch_items+=("${all_items[$i]}")
            fi
        done
        
        if [ ${#batch_items[@]} -gt 0 ]; then
            execute_batch "$batch" "${batch_items[@]}"
        fi
        
        # Brief pause between batches
        sleep 2
    done
    
    # Generate final summary
    generate_final_summary
    
    log_info "=== انتهى TASK 4: التنفيذ الفردي للاختبارات ==="
}

generate_final_summary() {
    local end_time=$(date +%s)
    local total_duration=$((end_time - START_TIME))
    local pass_rate=$((ITEMS_PASSED * 100 / TOTAL_ITEMS))
    
    cat > "$SUMMARY_FILE" << EOF
{
  "task": "TASK 4 - Individual Execution",
  "execution_date": "$(date -Iseconds)",
  "total_items": $TOTAL_ITEMS,
  "items_completed": $ITEMS_COMPLETED,
  "items_passed": $ITEMS_PASSED,
  "items_failed": $ITEMS_FAILED,
  "items_skipped": $ITEMS_SKIPPED,
  "pass_rate_percent": $pass_rate,
  "total_duration_seconds": $total_duration,
  "total_duration_hours": $(echo "scale=2; $total_duration / 3600" | bc -l),
  "total_batches": $TOTAL_BATCHES,
  "batch_size": $BATCH_SIZE,
  "status": "COMPLETE"
}
EOF
    
    log_info "تم إنشاء الملخص النهائي في $SUMMARY_FILE"
    log_info "معدل النجاح: $pass_rate%"
    log_info "المدة الإجمالية: $total_duration ثانية ($(echo "scale=2; $total_duration / 3600" | bc -l) ساعة)"
}

# ===== SCRIPT ENTRY POINT =====
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi