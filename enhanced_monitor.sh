#!/bin/bash

# Enhanced monitoring script for TASK 4 execution
REPORTS_DIR="/var/www/html/reports"
WORK_DIR="/var/www/html/work"

clear
echo "╔══════════════════════════════════════════════════════════════════════════════╗"
echo "║                    🚀 TASK 4: مراقب التنفيذ المحسن                          ║"
echo "╚══════════════════════════════════════════════════════════════════════════════╝"
echo

# Function to get current time
get_current_time() {
    date '+%Y-%m-%d %H:%M:%S'
}

# Function to calculate elapsed time
calculate_elapsed() {
    local start_time=$1
    local current_time=$(date +%s)
    local elapsed=$((current_time - start_time))
    
    local hours=$((elapsed / 3600))
    local minutes=$(((elapsed % 3600) / 60))
    local seconds=$((elapsed % 60))
    
    printf "%02d:%02d:%02d" $hours $minutes $seconds
}

# Check if execution is running
echo "🔍 حالة التنفيذ:"
if ps aux | grep -q "[r]un_individual_tests_task4_fixed.sh"; then
    echo "   ✅ السكريبت الرئيسي نشط ويعمل"
else
    echo "   ❌ السكريبت الرئيسي غير نشط"
fi

echo

# Show progress if available
if [ -f "$REPORTS_DIR/progress.txt" ]; then
    echo "📊 إحصائيات التقدم:"
    
    # Parse JSON manually since jq might not be available
    total_items=$(grep '"total_items"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
    items_completed=$(grep '"items_completed"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
    items_passed=$(grep '"items_passed"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
    items_failed=$(grep '"items_failed"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
    current_batch=$(grep '"current_batch"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
    total_batches=$(grep '"total_batches"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
    start_time=$(grep '"start_time"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
    
    echo "   📈 إجمالي العناصر: $total_items"
    echo "   ✅ العناصر المكتملة: $items_completed"
    echo "   🎯 العناصر الناجحة: $items_passed"
    echo "   ❌ العناصر الفاشلة: $items_failed"
    echo "   📦 الدفعة الحالية: $current_batch من $total_batches"
    
    if [ "$items_completed" -gt 0 ] && [ "$total_items" -gt 0 ]; then
        progress_percent=$((items_completed * 100 / total_items))
        echo "   📊 نسبة التقدم: $progress_percent%"
    else
        echo "   📊 نسبة التقدم: 0%"
    fi
    
    if [ "$start_time" -gt 0 ]; then
        elapsed_time=$(calculate_elapsed $start_time)
        echo "   ⏱️  الوقت المنقضي: $elapsed_time"
    fi
else
    echo "❌ ملف التقدم غير متوفر"
fi

echo

# Show current batch status
echo "📦 حالة الدفعة الحالية:"
current_batch_dir="$WORK_DIR/batch_001"
if [ -d "$current_batch_dir" ]; then
    echo "   📁 مجلد الدفعة: موجود"
    
    # Count items in current batch
    if [ -f "$current_batch_dir/items.txt" ]; then
        batch_items=$(wc -l < "$current_batch_dir/items.txt" 2>/dev/null || echo "0")
        echo "   📋 عناصر الدفعة: $batch_items"
    fi
    
    # Show running processes
    running_count=$(ps aux | grep -c "[p]hp\|[c]omposer\|[p]hpstan\|[p]salm" 2>/dev/null || echo "0")
    echo "   🔄 العمليات النشطة: $running_count"
else
    echo "   ❌ مجلد الدفعة غير موجود"
fi

echo

# Show recent timeline events
echo "📝 آخر الأحداث (آخر 10):"
if [ -f "$REPORTS_DIR/execution_timeline.log" ]; then
    tail -10 "$REPORTS_DIR/execution_timeline.log" | while read line; do
        if [[ $line == *"FAILED"* ]]; then
            echo "   ❌ $line"
        elif [[ $line == *"PASSED"* ]]; then
            echo "   ✅ $line"
        elif [[ $line == *"STARTED"* ]]; then
            echo "   🔄 $line"
        else
            echo "   📄 $line"
        fi
    done
else
    echo "   ❌ ملف الجدول الزمني غير متوفر"
fi

echo

# Show failed items summary
echo "💥 ملخص العناصر الفاشلة:"
if [ -f "$REPORTS_DIR/failed_items.txt" ]; then
    failed_count=$(wc -l < "$REPORTS_DIR/failed_items.txt" 2>/dev/null || echo "0")
    echo "   📊 إجمالي العناصر الفاشلة: $failed_count"
    
    if [ "$failed_count" -gt 0 ] && [ "$failed_count" -le 5 ]; then
        echo "   📋 العناصر الفاشلة:"
        while read line; do
            echo "      • $line"
        done < "$REPORTS_DIR/failed_items.txt"
    elif [ "$failed_count" -gt 5 ]; then
        echo "   📋 آخر 5 عناصر فاشلة:"
        tail -5 "$REPORTS_DIR/failed_items.txt" | while read line; do
            echo "      • $line"
        done
    fi
else
    echo "   ✅ لا توجد عناصر فاشلة مسجلة"
fi

echo

# Show system resources
echo "💻 موارد النظام:"
echo "   🖥️  المعالج: $(cat /proc/loadavg | cut -d' ' -f1 2>/dev/null || echo "غير متوفر") متوسط التحميل"
echo "   💾 الذاكرة: $(cat /proc/meminfo | grep MemAvailable | awk '{printf "%.1f GB متاحة", $2/1024/1024}' 2>/dev/null || echo "غير متوفر")"
echo "   💿 المساحة: $(df -h /var/www/html | awk 'NR==2 {print $4 " متاحة من " $2}' 2>/dev/null || echo "غير متوفر")"

echo

# Show estimated completion
if [ -f "$REPORTS_DIR/progress.txt" ] && [ "$items_completed" -gt 0 ] && [ "$start_time" -gt 0 ]; then
    current_time=$(date +%s)
    elapsed=$((current_time - start_time))
    
    if [ "$items_completed" -gt 0 ]; then
        avg_time_per_item=$((elapsed / items_completed))
        remaining_items=$((total_items - items_completed))
        estimated_remaining=$((remaining_items * avg_time_per_item))
        
        estimated_hours=$((estimated_remaining / 3600))
        estimated_minutes=$(((estimated_remaining % 3600) / 60))
        
        echo "⏰ التقدير الزمني:"
        echo "   📅 الوقت المتبقي المقدر: ${estimated_hours}h ${estimated_minutes}m"
        
        completion_time=$((current_time + estimated_remaining))
        completion_date=$(date -d "@$completion_time" '+%Y-%m-%d %H:%M:%S' 2>/dev/null || echo "غير محدد")
        echo "   🎯 وقت الإنجاز المتوقع: $completion_date"
    fi
fi

echo
echo "╔══════════════════════════════════════════════════════════════════════════════╗"
echo "║                          📊 انتهى تقرير المراقبة                            ║"
echo "║                    آخر تحديث: $(get_current_time)                    ║"
echo "╚══════════════════════════════════════════════════════════════════════════════╝"