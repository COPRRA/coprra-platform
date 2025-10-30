#!/bin/bash

echo '🔍 بدء مرحلة DEPENDENCY AUDIT AND SEQUENTIAL PROCESSING'
echo '=================================================='
echo

# إنشاء مجلد التقارير
mkdir -p /var/www/html/reports/dependency_audit
cd /var/www/html

echo '📊 1. تحليل التبعيات الأساسية...'
echo '--------------------------------'

# فحص composer.json
echo '🔧 فحص ملف composer.json:'
if [ -f composer.json ]; then
    echo '✅ ملف composer.json موجود'
    
    # استخراج التبعيات الأساسية
    echo '📦 التبعيات الأساسية:' > /var/www/html/reports/dependency_audit/main_dependencies.txt
    php -r "
