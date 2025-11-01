<?php
// 🎉 COPRRA - Success Checker
echo "<h1>🎉 COPRRA - فاحص النجاح</h1>";
echo "<style>body{font-family:Arial;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:20px;}</style>";

echo "<div style='background:rgba(255,255,255,0.1);padding:30px;border-radius:20px;text-align:center;'>";

// فحص الملفات
$files = ['index.php', '.htaccess', 'advanced_database_setup.php', 'phpinfo.php', '.env'];
$uploaded_files = 0;

echo "<h2>📁 فحص الملفات المرفوعة</h2>";
foreach($files as $file) {
    if(file_exists($file)) {
        echo "<p style='color:#00ff00;'>✅ $file - موجود</p>";
        $uploaded_files++;
    } else {
        echo "<p style='color:#ff0000;'>❌ $file - غير موجود</p>";
    }
}

// فحص قاعدة البيانات
echo "<h2>🗄️ فحص قاعدة البيانات</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u574849695_coprra", "u574849695_coprra", "Hamo1510@Rayan146");
    echo "<p style='color:#00ff00;'>✅ اتصال قاعدة البيانات ناجح</p>";
    $db_status = true;
} catch(Exception $e) {
    echo "<p style='color:#ff0000;'>❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
    $db_status = false;
}

// النتيجة النهائية
echo "<h2>🏆 النتيجة النهائية</h2>";
if($uploaded_files == count($files) && $db_status) {
    echo "<h1 style='color:#00ff00;font-size:3em;'>🎉 تم العمل كله بنجاح!</h1>";
    echo "<p style='font-size:1.5em;'>الموقع يعمل بامتياز!</p>";
} else {
    echo "<h1 style='color:#ffff00;font-size:2em;'>⚠️ يحتاج إلى إكمال</h1>";
    echo "<p>الملفات المرفوعة: $uploaded_files/" . count($files) . "</p>";
}

echo "<p><a href='/' style='background:#FFD700;color:#333;padding:15px 30px;text-decoration:none;border-radius:10px;margin:10px;'>🏠 الصفحة الرئيسية</a></p>";
echo "<p><a href='/advanced_database_setup.php' style='background:#007cba;color:white;padding:15px 30px;text-decoration:none;border-radius:10px;margin:10px;'>🗄️ إعداد قاعدة البيانات</a></p>";

echo "</div>";
?>