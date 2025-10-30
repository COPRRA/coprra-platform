#!/usr/bin/env bash

# TASK 4 EXECUTION MONITOR
# مراقب التنفيذ للـ 413 اختبار وأداة
# يعرض التقدم في الوقت الفعلي

set -euo pipefail

readonly WORKDIR="/var/www/html"
readonly REPORTS_DIR="$WORKDIR/reports/task4_execution"
readonly PROGRESS_FILE="$REPORTS_DIR/progress.txt"
readonly SUMMARY_FILE="$REPORTS_DIR/execution_summary.json"
readonly TIMELINE_FILE="$REPORTS_DIR/execution_timeline.log"
readonly FAILED_ITEMS_FILE="$REPORTS_DIR/failed_items.log"

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly WHITE='\033[1;37m'
readonly NC='\033[0m' # No Color

show_header() {
    clear
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                    TASK 4 EXECUTION MONITOR                    ║${NC}"
    echo -e "${CYAN}║                  مراقب التنفيذ الفردي للاختبارات                ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════════╝${NC}"
    echo
}

show_progress() {
    if [ ! -f "$PROGRESS_FILE" ]; then
        echo -e "${YELLOW}⚠️  ملف التقدم غير موجود. التنفيذ لم يبدأ بعد.${NC}"
        return
    fi
    
    echo -e "${WHITE}📊 حالة التقدم:${NC}"
    echo -e "${BLUE}$(cat "$PROGRESS_FILE")${NC}"
    echo
}

show_recent_timeline() {
    if [ ! -f "$TIMELINE_FILE" ]; then
        echo -e "${YELLOW}⚠️  ملف الجدول الزمني غير موجود.${NC}"
        return
    fi
    
    echo -e "${WHITE}📅 آخر الأحداث (آخر 10):${NC}"
    tail -n 10 "$TIMELINE_FILE" | while read -r line; do
        if [[ "$line" == *"SUCCESS"* ]]; then
            echo -e "${GREEN}✅ $line${NC}"
        elif [[ "$line" == *"FAILED"* ]]; then
            echo -e "${RED}❌ $line${NC}"
        elif [[ "$line" == *"START"* ]]; then
            echo -e "${YELLOW}🚀 $line${NC}"
        else
            echo -e "${BLUE}ℹ️  $line${NC}"
        fi
    done
    echo
}

show_failed_items() {
    if [ ! -f "$FAILED_ITEMS_FILE" ]; then
        echo -e "${GREEN}✅ لا توجد عناصر فاشلة حتى الآن!${NC}"
        return
    fi
    
    local failed_count=$(wc -l < "$FAILED_ITEMS_FILE")
    if [ "$failed_count" -eq 0 ]; then
        echo -e "${GREEN}✅ لا توجد عناصر فاشلة حتى الآن!${NC}"
        return
    fi
    
    echo -e "${RED}❌ العناصر الفاشلة ($failed_count):${NC}"
    head -n 5 "$FAILED_ITEMS_FILE" | while IFS=: read -r id name command exit_code; do
        echo -e "${RED}   • $id: $name (Exit: $exit_code)${NC}"
    done
    
    if [ "$failed_count" -gt 5 ]; then
        echo -e "${RED}   ... و $((failed_count - 5)) عنصر آخر${NC}"
    fi
    echo
}

show_system_resources() {
    echo -e "${WHITE}💻 موارد النظام:${NC}"
    
    # CPU Usage
    local cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1 || echo "N/A")
    echo -e "${BLUE}   CPU: ${cpu_usage}%${NC}"
    
    # Memory Usage
    local mem_info=$(free -m | awk 'NR==2{printf "%.1f%%", $3*100/$2}' || echo "N/A")
    echo -e "${BLUE}   Memory: ${mem_info}${NC}"
    
    # Disk Usage
    local disk_usage=$(df -h "$WORKDIR" | awk 'NR==2{print $5}' || echo "N/A")
    echo -e "${BLUE}   Disk: ${disk_usage}${NC}"
    
    # Running Processes
    local process_count=$(pgrep -f "run_individual_tests_task4" | wc -l || echo "0")
    echo -e "${BLUE}   Active Processes: ${process_count}${NC}"
    echo
}

show_batch_status() {
    echo -e "${WHITE}📦 حالة الدفعات:${NC}"
    
    local batch_count=0
    local completed_batches=0
    
    for batch_dir in "$REPORTS_DIR"/batch_*; do
        if [ -d "$batch_dir" ]; then
            ((batch_count++))
            local batch_name=$(basename "$batch_dir")
            local log_count=$(find "$batch_dir" -name "*.log" | wc -l)
            
            if [ "$log_count" -gt 0 ]; then
                ((completed_batches++))
                echo -e "${GREEN}   ✅ $batch_name: $log_count ملف${NC}"
            else
                echo -e "${YELLOW}   ⏳ $batch_name: في الانتظار${NC}"
            fi
        fi
    done
    
    if [ "$batch_count" -eq 0 ]; then
        echo -e "${YELLOW}   ⚠️  لم يتم إنشاء دفعات بعد${NC}"
    else
        echo -e "${BLUE}   📊 إجمالي: $completed_batches/$batch_count دفعة مكتملة${NC}"
    fi
    echo
}

show_summary() {
    if [ ! -f "$SUMMARY_FILE" ]; then
        echo -e "${YELLOW}⚠️  الملخص النهائي غير متوفر بعد.${NC}"
        return
    fi
    
    echo -e "${WHITE}📋 الملخص النهائي:${NC}"
    echo -e "${GREEN}$(cat "$SUMMARY_FILE" | jq -r '
        "   ✅ إجمالي العناصر: " + (.total_items | tostring) + "\n" +
        "   ✅ مكتمل: " + (.items_completed | tostring) + "\n" +
        "   ✅ نجح: " + (.items_passed | tostring) + "\n" +
        "   ❌ فشل: " + (.items_failed | tostring) + "\n" +
        "   📊 معدل النجاح: " + (.pass_rate_percent | tostring) + "%\n" +
        "   ⏱️  المدة: " + (.total_duration_hours | tostring) + " ساعة"
    ')${NC}"
    echo
}

monitor_loop() {
    local refresh_interval=5
    
    while true; do
        show_header
        show_progress
        show_recent_timeline
        show_failed_items
        show_system_resources
        show_batch_status
        show_summary
        
        echo -e "${PURPLE}🔄 التحديث كل $refresh_interval ثوانٍ... (اضغط Ctrl+C للخروج)${NC}"
        sleep "$refresh_interval"
    done
}

show_help() {
    echo "TASK 4 Execution Monitor - مراقب التنفيذ"
    echo
    echo "الاستخدام:"
    echo "  $0 [OPTIONS]"
    echo
    echo "الخيارات:"
    echo "  -h, --help     عرض هذه المساعدة"
    echo "  -o, --once     عرض واحد فقط (بدون تحديث مستمر)"
    echo "  -i, --interval SECONDS  فترة التحديث (افتراضي: 5 ثوانٍ)"
    echo
    echo "أمثلة:"
    echo "  $0                    # مراقبة مستمرة"
    echo "  $0 --once           # عرض واحد"
    echo "  $0 --interval 10    # تحديث كل 10 ثوانٍ"
}

main() {
    local once_only=false
    local refresh_interval=5
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -o|--once)
                once_only=true
                shift
                ;;
            -i|--interval)
                refresh_interval="$2"
                shift 2
                ;;
            *)
                echo "خيار غير معروف: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    # Check if reports directory exists
    if [ ! -d "$REPORTS_DIR" ]; then
        echo -e "${RED}❌ مجلد التقارير غير موجود: $REPORTS_DIR${NC}"
        echo -e "${YELLOW}💡 تأكد من تشغيل سكريبت التنفيذ أولاً.${NC}"
        exit 1
    fi
    
    if [ "$once_only" = true ]; then
        show_header
        show_progress
        show_recent_timeline
        show_failed_items
        show_system_resources
        show_batch_status
        show_summary
    else
        monitor_loop
    fi
}

# Handle Ctrl+C gracefully
trap 'echo -e "\n${YELLOW}🛑 تم إيقاف المراقبة.${NC}"; exit 0' INT

if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi