<?php
// 🔥 COPRRA - Advanced Diagnostic Tool
echo "<h1>🔥 COPRRA - تشخيص متقدم</h1>";
echo "<style>body{font-family:Arial;background:#f0f0f0;padding:20px;}</style>";

echo "<h2>📊 معلومات الخادم</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

echo "<h2>📁 الملفات الموجودة</h2>";
$files = scandir('.');
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        echo "<p>✅ $file</p>";
    }
}

echo "<h2>🗄️ اختبار قاعدة البيانات</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=u574849695_coprra", "u574849695_coprra", "Hamo1510@Rayan146");
    echo "<p>✅ اتصال قاعدة البيانات ناجح</p>";
} catch(Exception $e) {
    echo "<p>❌ خطأ في قاعدة البيانات: " . $e->getMessage() . "</p>";
}

echo "<h2>🌐 اختبار الاتصال</h2>";
echo "<p>✅ الموقع يعمل!</p>";
echo "<p><a href='/' style='background:#007cba;color:white;padding:10px;text-decoration:none;'>🏠 الصفحة الرئيسية</a></p>";
?>