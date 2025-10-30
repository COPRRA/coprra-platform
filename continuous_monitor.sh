#!/bin/bash

# Continuous monitoring script for TASK 4 execution
# Updates every 5 minutes

REPORTS_DIR="/var/www/html/reports"
MONITOR_SCRIPT="/var/www/html/enhanced_monitor.sh"
LOG_FILE="/var/www/html/reports/monitoring.log"

echo "🚀 بدء المراقبة المستمرة لـ TASK 4"
echo "📅 $(date '+%Y-%m-%d %H:%M:%S') - بدء المراقبة المستمرة" >> "$LOG_FILE"

# Function to check if main script is still running
is_script_running() {
    if ps aux | grep -q "[r]un_individual_tests_task4_fixed.sh"; then
        return 0  # Running
    else
        return 1  # Not running
    fi
}

# Function to get completion status
get_completion_status() {
    if [ -f "$REPORTS_DIR/progress.txt" ]; then
        total_items=$(grep '"total_items"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
        items_completed=$(grep '"items_completed"' "$REPORTS_DIR/progress.txt" | cut -d':' -f2 | tr -d ' ,' | head -1)
        
        if [ "$items_completed" -ge "$total_items" ]; then
            return 0  # Completed
        else
            return 1  # Not completed
        fi
    else
        return 1  # No progress file
    fi
}

# Main monitoring loop
while true; do
    echo "📊 $(date '+%Y-%m-%d %H:%M:%S') - تحديث المراقبة" >> "$LOG_FILE"
    
    # Run the enhanced monitor
    if [ -f "$MONITOR_SCRIPT" ]; then
        echo "════════════════════════════════════════════════════════════════════════════════"
        echo "🕐 تحديث المراقبة - $(date '+%Y-%m-%d %H:%M:%S')"
        echo "════════════════════════════════════════════════════════════════════════════════"
        
        bash "$MONITOR_SCRIPT"
        
        echo "════════════════════════════════════════════════════════════════════════════════"
        echo
    else
        echo "❌ سكريبت المراقبة المحسن غير موجود"
    fi
    
    # Check if script is still running
    if ! is_script_running; then
        echo "⚠️  $(date '+%Y-%m-%d %H:%M:%S') - السكريبت الرئيسي توقف" >> "$LOG_FILE"
        
        # Check if it completed successfully
        if get_completion_status; then
            echo "✅ $(date '+%Y-%m-%d %H:%M:%S') - اكتمل التنفيذ بنجاح!" >> "$LOG_FILE"
            echo "🎉 اكتمل تنفيذ TASK 4 بنجاح!"
            echo "📊 يمكنك الآن تشغيل سكريبت التحليل لإنشاء التقرير النهائي"
            break
        else
            echo "❌ $(date '+%Y-%m-%d %H:%M:%S') - توقف السكريبت قبل الاكتمال" >> "$LOG_FILE"
            echo "⚠️  السكريبت الرئيسي توقف قبل اكتمال جميع الاختبارات"
            echo "🔍 يرجى فحص السجلات لمعرفة السبب"
            break
        fi
    fi
    
    # Wait for 5 minutes before next update
    echo "⏳ انتظار 5 دقائق للتحديث التالي..."
    sleep 300  # 5 minutes
done

echo "📅 $(date '+%Y-%m-%d %H:%M:%S') - انتهاء المراقبة المستمرة" >> "$LOG_FILE"
echo "🏁 انتهت المراقبة المستمرة"