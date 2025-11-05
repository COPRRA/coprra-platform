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

// --- Maintenance Actions ---
echo "<h2>🛠 إجراءات الصيانة</h2>";
$action = $_GET['action'] ?? '';
if ($action === 'purge_views') {
    $viewsDir = __DIR__ . '/storage/framework/views';
    $deleted = 0;
    if (is_dir($viewsDir)) {
        foreach (glob($viewsDir . '/*.php') as $viewFile) {
            if (@unlink($viewFile)) { $deleted++; }
        }
    }
    echo "<p>🧹 تم حذف ملفات Blade المترجمة: {$deleted}</p>";
}

if ($action === 'reset_opcache') {
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "<p>♻️ تم إعادة ضبط OPcache بنجاح.</p>";
    } else {
        echo "<p>⚠️ OPcache غير مفعّل.</p>";
    }
}

if ($action === 'clear_caches') {
    $out = @shell_exec('php artisan optimize:clear 2>&1');
    echo "<pre style='background:#fff;padding:10px;border:1px solid #ddd;'>" . htmlspecialchars($out ?? 'No output') . "</pre>";
}

echo "<p>روابط سريعة: ";
echo "<a href='?action=purge_views'>🧹 حذف القوالب المترجمة</a> | ";
echo "<a href='?action=reset_opcache'>♻️ إعادة ضبط OPcache</a> | ";
echo "<a href='?action=clear_caches'>🧯 Laravel optimize:clear</a>";
echo "</p>";

// --- View file insights ---
echo "<h2>🧩 فحص ملفات القوالب (Blade)</h2>";
$viewFiles = [
    __DIR__ . '/resources/views/brands/index.blade.php',
    __DIR__ . '/resources/views/brands/index_clean.blade.php',
    __DIR__ . '/resources/views/categories/index.blade.php',
    __DIR__ . '/resources/views/categories/index_clean.blade.php',
    __DIR__ . '/resources/views/welcome.blade.php',
];
foreach ($viewFiles as $vf) {
    echo '<div style="background:#fff;padding:10px;border:1px solid #ddd;margin-bottom:10px">';
    echo '<strong>' . htmlspecialchars(str_replace(__DIR__.'/', '', $vf)) . '</strong><br/>';
    if (is_readable($vf)) {
        echo 'mtime: ' . date('c', filemtime($vf)) . '<br/>';
        $content = @file_get_contents($vf);
        $lines = explode("\n", (string)$content);
        $preview = array_slice($lines, 0, 20);
        echo '<pre>' . htmlspecialchars(implode("\n", $preview)) . '</pre>';
    } else {
        echo '⚠️ غير قابل للقراءة.';
    }
    echo '</div>';
}
?>
