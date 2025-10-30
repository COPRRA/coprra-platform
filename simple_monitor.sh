#!/bin/bash

# Simple monitoring script for TASK 4 execution
REPORTS_DIR="/var/www/html/reports"

echo "=== TASK 4 Individual Test Execution Monitor ==="
echo "=== مراقب تنفيذ الاختبارات الفردية ==="
echo

# Check if execution is running
if pgrep -f "run_individual_tests_task4_fixed.sh" > /dev/null; then
    echo "✅ السكريبت يعمل حالياً"
else
    echo "❌ السكريبت غير نشط"
fi

echo

# Show progress if available
if [ -f "$REPORTS_DIR/progress.txt" ]; then
    echo "📊 التقدم الحالي:"
    cat "$REPORTS_DIR/progress.txt" | jq -r '
        "إجمالي العناصر: " + (.total_items | tostring) + 
        "\nالعناصر المكتملة: " + (.items_completed | tostring) + 
        "\nالعناصر الناجحة: " + (.items_passed | tostring) + 
        "\nالعناصر الفاشلة: " + (.items_failed | tostring) + 
        "\nنسبة التقدم: " + (.progress_percent | tostring) + "%" +
        "\nالدفعة الحالية: " + (.current_batch | tostring) + "/" + (.total_batches | tostring)
    ' 2>/dev/null || echo "لا يمكن قراءة ملف التقدم"
else
    echo "❌ ملف التقدم غير متوفر"
fi

echo

# Show recent timeline events
if [ -f "$REPORTS_DIR/execution_timeline.log" ]; then
    echo "📝 آخر الأحداث:"
    tail -10 "$REPORTS_DIR/execution_timeline.log" | while read line; do
        echo "  $line"
    done
else
    echo "❌ ملف الجدول الزمني غير متوفر"
fi

echo

# Show failed items count
if [ -f "$REPORTS_DIR/failed_items.txt" ]; then
    failed_count=$(wc -l < "$REPORTS_DIR/failed_items.txt" 2>/dev/null || echo "0")
    echo "❌ العناصر الفاشلة: $failed_count"
else
    echo "✅ لا توجد عناصر فاشلة مسجلة"
fi

echo

# Show system resources
echo "💻 موارد النظام:"
echo "  المعالج: $(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1 2>/dev/null || echo "غير متوفر")% استخدام"
echo "  الذاكرة: $(free -m | awk 'NR==2{printf "%.1f%%", $3*100/$2}' 2>/dev/null || echo "غير متوفر") استخدام"
echo "  المساحة: $(df -h /var/www/html | awk 'NR==2 {print $5}' 2>/dev/null || echo "غير متوفر") استخدام"

echo
echo "=== انتهى التقرير ==="