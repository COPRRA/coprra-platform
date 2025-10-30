#!/bin/bash

# مراقب التقدم بالنسبة المئوية - TASK 4
# يعرض تحديثات كل 5% من التقدم

PROGRESS_FILE="/var/www/html/reports/task4_execution/progress.txt"
LOG_FILE="/var/www/html/percentage_monitor.log"
LAST_PERCENTAGE_FILE="/var/www/html/last_percentage.txt"

# إنشاء ملف النسبة المئوية الأخيرة إذا لم يكن موجوداً
if [ ! -f "$LAST_PERCENTAGE_FILE" ]; then
    echo "0" > "$LAST_PERCENTAGE_FILE"
fi

echo "🎯 بدء المراقبة المستمرة للتقدم بالنسبة المئوية - $(date)" | tee -a "$LOG_FILE"
echo "📊 سيتم عرض التحديثات كل 5% من التقدم" | tee -a "$LOG_FILE"
echo "════════════════════════════════════════════════════════════════" | tee -a "$LOG_FILE"

while true; do
    # التحقق من وجود ملف التقدم
    if [ ! -f "$PROGRESS_FILE" ]; then
        echo "⚠️  $(date '+%H:%M:%S') - ملف التقدم غير موجود، انتظار..." | tee -a "$LOG_FILE"
        sleep 30
        continue
    fi

    # قراءة التقدم الحالي
    if [ -f "$PROGRESS_FILE" ]; then
        CURRENT_PROGRESS=$(grep "Progress:" "$PROGRESS_FILE" | tail -1 | awk '{print $2}' | sed 's/%//')
        COMPLETED=$(grep "Completed:" "$PROGRESS_FILE" | tail -1 | awk '{print $2}')
        TOTAL=$(grep "Total:" "$PROGRESS_FILE" | tail -1 | awk '{print $2}')
        PASSED=$(grep "Passed:" "$PROGRESS_FILE" | tail -1 | awk '{print $2}')
        FAILED=$(grep "Failed:" "$PROGRESS_FILE" | tail -1 | awk '{print $2}')
        CURRENT_BATCH=$(grep "Current Batch:" "$PROGRESS_FILE" | tail -1 | awk '{print $3}')
        TOTAL_BATCHES=$(grep "Total Batches:" "$PROGRESS_FILE" | tail -1 | awk '{print $3}')
        
        # قراءة النسبة المئوية الأخيرة المعروضة
        LAST_PERCENTAGE=$(cat "$LAST_PERCENTAGE_FILE" 2>/dev/null || echo "0")
        
        # التحقق من صحة البيانات
        if [[ "$CURRENT_PROGRESS" =~ ^[0-9]+$ ]] && [ "$CURRENT_PROGRESS" -ge 0 ] && [ "$CURRENT_PROGRESS" -le 100 ]; then
            # حساب النسبة المئوية التالية (مضاعفات 5)
            NEXT_MILESTONE=$((($LAST_PERCENTAGE / 5 + 1) * 5))
            
            # إذا وصل التقدم إلى النسبة المئوية التالية
            if [ "$CURRENT_PROGRESS" -ge "$NEXT_MILESTONE" ] && [ "$NEXT_MILESTONE" -gt "$LAST_PERCENTAGE" ]; then
                echo "🎉 ═══════════════════════════════════════════════════════════════" | tee -a "$LOG_FILE"
                echo "🚀 تحديث التقدم: $CURRENT_PROGRESS% مكتمل! ($(date '+%H:%M:%S'))" | tee -a "$LOG_FILE"
                echo "📊 التفاصيل:" | tee -a "$LOG_FILE"
                echo "   ✅ مكتمل: $COMPLETED من $TOTAL عنصر" | tee -a "$LOG_FILE"
                echo "   🎯 ناجح: $PASSED عنصر" | tee -a "$LOG_FILE"
                echo "   ❌ فاشل: $FAILED عنصر" | tee -a "$LOG_FILE"
                echo "   📦 الدفعة: $CURRENT_BATCH من $TOTAL_BATCHES" | tee -a "$LOG_FILE"
                
                # حساب الوقت المتبقي المقدر
                if [ "$CURRENT_PROGRESS" -gt 0 ]; then
                    # قراءة وقت البداية من ملف السجل الرئيسي
                    START_TIME=$(grep "Script started at:" /var/www/html/execution_master.log 2>/dev/null | head -1 | awk '{print $4}' | tr -d '[]')
                    if [ -n "$START_TIME" ]; then
                        CURRENT_TIME=$(date +%s)
                        START_TIMESTAMP=$(date -d "$START_TIME" +%s 2>/dev/null || echo "$CURRENT_TIME")
                        ELAPSED_SECONDS=$((CURRENT_TIME - START_TIMESTAMP))
                        
                        if [ "$ELAPSED_SECONDS" -gt 0 ]; then
                            ESTIMATED_TOTAL_SECONDS=$((ELAPSED_SECONDS * 100 / CURRENT_PROGRESS))
                            REMAINING_SECONDS=$((ESTIMATED_TOTAL_SECONDS - ELAPSED_SECONDS))
                            
                            if [ "$REMAINING_SECONDS" -gt 0 ]; then
                                REMAINING_HOURS=$((REMAINING_SECONDS / 3600))
                                REMAINING_MINUTES=$(((REMAINING_SECONDS % 3600) / 60))
                                echo "   ⏰ الوقت المتبقي المقدر: ${REMAINING_HOURS}h ${REMAINING_MINUTES}m" | tee -a "$LOG_FILE"
                                
                                COMPLETION_TIME=$(date -d "+${REMAINING_SECONDS} seconds" '+%H:%M')
                                echo "   🎯 وقت الإنجاز المتوقع: $COMPLETION_TIME" | tee -a "$LOG_FILE"
                            fi
                        fi
                    fi
                fi
                
                echo "🎉 ═══════════════════════════════════════════════════════════════" | tee -a "$LOG_FILE"
                echo "" | tee -a "$LOG_FILE"
                
                # تحديث النسبة المئوية الأخيرة
                echo "$CURRENT_PROGRESS" > "$LAST_PERCENTAGE_FILE"
            fi
            
            # إذا وصل إلى 100%
            if [ "$CURRENT_PROGRESS" -eq 100 ]; then
                echo "🎊 ═══════════════════════════════════════════════════════════════" | tee -a "$LOG_FILE"
                echo "🏆 تم إكمال TASK 4 بنجاح! 100% مكتمل!" | tee -a "$LOG_FILE"
                echo "📊 النتائج النهائية:" | tee -a "$LOG_FILE"
                echo "   ✅ إجمالي العناصر: $TOTAL" | tee -a "$LOG_FILE"
                echo "   🎯 العناصر الناجحة: $PASSED" | tee -a "$LOG_FILE"
                echo "   ❌ العناصر الفاشلة: $FAILED" | tee -a "$LOG_FILE"
                echo "   📦 إجمالي الدفعات: $TOTAL_BATCHES" | tee -a "$LOG_FILE"
                echo "🎊 ═══════════════════════════════════════════════════════════════" | tee -a "$LOG_FILE"
                break
            fi
        else
            echo "⚠️  $(date '+%H:%M:%S') - بيانات التقدم غير صحيحة: $CURRENT_PROGRESS%" | tee -a "$LOG_FILE"
        fi
    fi
    
    # التحقق من حالة السكريبت الرئيسي
    MAIN_SCRIPT_PID=$(pgrep -f "run_individual_tests_task4_fixed.sh" 2>/dev/null)
    if [ -z "$MAIN_SCRIPT_PID" ]; then
        echo "⚠️  $(date '+%H:%M:%S') - السكريبت الرئيسي غير نشط، التحقق من الإكمال..." | tee -a "$LOG_FILE"
        
        # التحقق من ملف الإكمال
        if [ -f "/var/www/html/execution_complete.flag" ]; then
            echo "✅ $(date '+%H:%M:%S') - تم إكمال التنفيذ بنجاح!" | tee -a "$LOG_FILE"
            break
        else
            echo "❌ $(date '+%H:%M:%S') - السكريبت توقف بشكل غير متوقع" | tee -a "$LOG_FILE"
            break
        fi
    fi
    
    # انتظار 30 ثانية قبل التحقق التالي
    sleep 30
done

echo "📋 انتهت المراقبة المستمرة - $(date)" | tee -a "$LOG_FILE"