#!/bin/bash

# مراقب التقدم بالنسبة المئوية المحسن - TASK 4
# يعرض تحديثات كل 5% من التقدم

LOG_FILE="/var/www/html/percentage_tracker.log"
LAST_PERCENTAGE_FILE="/var/www/html/last_percentage_tracker.txt"

# إنشاء ملف النسبة المئوية الأخيرة إذا لم يكن موجوداً
if [ ! -f "$LAST_PERCENTAGE_FILE" ]; then
    echo "0" > "$LAST_PERCENTAGE_FILE"
fi

echo "🎯 بدء مراقبة التقدم بالنسبة المئوية - $(date)" | tee -a "$LOG_FILE"
echo "📊 سيتم عرض التحديثات كل 5% من التقدم" | tee -a "$LOG_FILE"
echo "════════════════════════════════════════════════════════════════" | tee -a "$LOG_FILE"

while true; do
    # تشغيل المراقب المحسن والحصول على النتائج
    MONITOR_OUTPUT=$(./enhanced_monitor.sh 2>/dev/null)
    
    if [ $? -eq 0 ]; then
        # استخراج النسبة المئوية من النتائج
        CURRENT_PROGRESS=$(echo "$MONITOR_OUTPUT" | grep "📊 التقدم:" | awk '{print $3}' | sed 's/%//' | sed 's/(//')
        COMPLETED=$(echo "$MONITOR_OUTPUT" | grep "📊 التقدم:" | awk '{print $5}')
        TOTAL=$(echo "$MONITOR_OUTPUT" | grep "📊 التقدم:" | awk '{print $7}')
        PASSED=$(echo "$MONITOR_OUTPUT" | grep "✅ ناجح:" | awk '{print $3}')
        FAILED=$(echo "$MONITOR_OUTPUT" | grep "❌ فاشل:" | awk '{print $3}')
        CURRENT_BATCH=$(echo "$MONITOR_OUTPUT" | grep "📦 الدفعة الحالية:" | awk '{print $4}')
        TOTAL_BATCHES=$(echo "$MONITOR_OUTPUT" | grep "📦 الدفعة الحالية:" | awk '{print $6}')
        
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
                
                # استخراج الوقت المتبقي من النتائج
                REMAINING_TIME=$(echo "$MONITOR_OUTPUT" | grep "الوقت المتبقي المقدر:" | awk '{print $4}')
                COMPLETION_TIME=$(echo "$MONITOR_OUTPUT" | grep "وقت الإنجاز المتوقع:" | awk '{print $4}')
                
                if [ -n "$REMAINING_TIME" ]; then
                    echo "   ⏰ الوقت المتبقي المقدر: $REMAINING_TIME" | tee -a "$LOG_FILE"
                fi
                
                if [ -n "$COMPLETION_TIME" ]; then
                    echo "   🎯 وقت الإنجاز المتوقع: $COMPLETION_TIME" | tee -a "$LOG_FILE"
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
                echo "🎊 ═══════════════════════════════════════════════════════════════" | tee -a "$LOG_FILE"
                break
            fi
        else
            echo "⚠️  $(date '+%H:%M:%S') - بيانات التقدم غير صحيحة: $CURRENT_PROGRESS" | tee -a "$LOG_FILE"
        fi
    else
        echo "⚠️  $(date '+%H:%M:%S') - فشل في الحصول على بيانات المراقبة" | tee -a "$LOG_FILE"
    fi
    
    # التحقق من وجود ملف الإكمال
    if [ -f "/var/www/html/execution_complete.flag" ]; then
        echo "✅ $(date '+%H:%M:%S') - تم إكمال التنفيذ بنجاح!" | tee -a "$LOG_FILE"
        break
    fi
    
    # انتظار 60 ثانية قبل التحقق التالي
    sleep 60
done

echo "📋 انتهت المراقبة المستمرة - $(date)" | tee -a "$LOG_FILE"