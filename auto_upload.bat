@echo off
echo 🚀 COPRRA - Auto Upload Script
echo ================================

echo 📁 قائمة الملفات للرفع:
echo ✅ index.php
echo ✅ .htaccess  
echo ✅ advanced_database_setup.php
echo ✅ phpinfo.php
echo ✅ .env
echo ✅ diagnostic.php
echo ✅ composer.json

echo.
echo 🌐 فتح Hostinger...
start https://hpanel.hostinger.com/

echo.
echo 📋 معلومات تسجيل الدخول:
echo البريد: gasser.elshewaikh@gmail.com
echo كلمة المرور: Hamo1510@Rayan146

echo.
echo 🔄 انتظار 5 ثوان ثم فتح File Manager...
timeout /t 5 /nobreak

start https://hpanel.hostinger.com/file-manager

echo.
echo ✅ تم فتح جميع النوافذ المطلوبة
echo 📤 يرجى رفع الملفات يدوياً الآن

pause
