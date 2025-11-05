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
    $dbHost = getenv('DB_HOST') ?: 'localhost';
    $dbName = getenv('DB_DATABASE') ?: 'unknown';
    $dbUser = getenv('DB_USERNAME') ?: 'unknown';
    $dbPass = getenv('DB_PASSWORD') ?: '';
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    echo "<p>✅ اتصال قاعدة البيانات ناجح ({$dbName}@{$dbHost})</p>";
} catch(Exception $e) {
    echo "<p>❌ خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>🧾 آخر سجل Laravel</h2>";
$logPath = __DIR__ . '/storage/logs/laravel.log';
if (is_readable($logPath)) {
    $log = @file_get_contents($logPath);
    if ($log !== false) {
        $lines = explode("\n", $log);
        $tail = array_slice($lines, -200);
        echo '<pre style="background:#fff;padding:10px;border:1px solid #ddd;max-height:400px;overflow:auto">' . htmlspecialchars(implode("\n", $tail)) . '</pre>';
    } else {
        echo "<p>⚠️ تعذر قراءة الملف.</p>";
    }
} else {
    echo "<p>⚠️ ملف السجل غير موجود أو لا يمكن قراءته.</p>";
}

echo "<h2>🌐 اختبار الاتصال</h2>";
echo "<p>✅ الموقع يعمل!</p>";
echo "<p><a href='/' style='background:#007cba;color:white;padding:10px;text-decoration:none;'>🏠 الصفحة الرئيسية</a></p>";
?>
