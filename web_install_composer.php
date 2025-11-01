<?php
// 🔧 Web-based Composer installer (best-effort)
// This script attempts to:
// 1) Detect if vendor/autoload.php exists
// 2) Download composer.phar if missing
// 3) Run "composer install" using shell_exec/proc_open if available
// If execution functions are disabled, it will print guided steps.

header('Content-Type: text/html; charset=UTF-8');

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$docroot = __DIR__;
$vendorAutoload = $docroot . '/vendor/autoload.php';

echo '<h1>🚀 COPRRA - Web Composer Installer</h1>';
echo '<p>Document Root: ' . h($docroot) . '</p>';

if (file_exists($vendorAutoload)) {
    echo '<p>✅ vendor/autoload.php موجود بالفعل. لا حاجة للتثبيت.</p>';
    exit;
}

echo '<p>⚠️ vendor/autoload.php غير موجود. سنحاول تنزيل Composer وتثبيت الاعتمادات.</p>';

// Step 1: Download composer.phar
$composerPhar = $docroot . '/composer.phar';
if (!file_exists($composerPhar)) {
    echo '<p>⬇️ تنزيل composer.phar...</p>';
    $url = 'https://getcomposer.org/composer-stable.phar';
    $data = @file_get_contents($url);
    if ($data === false) {
        echo '<p>❌ فشل تنزيل composer.phar. تحقق من إعدادات allow_url_fopen والجدار الناري.</p>';
    } else {
        if (@file_put_contents($composerPhar, $data) === false) {
            echo '<p>❌ تعذر حفظ composer.phar في الجذر.</p>';
        } else {
            echo '<p>✅ تم تنزيل composer.phar.</p>';
        }
    }
}

// Step 2: Try to run composer install
function run_cmd($cmd){
    $out = '';
    if (function_exists('shell_exec')) {
        $out = shell_exec($cmd . ' 2>&1');
        return [true, $out];
    }
    if (function_exists('passthru')) {
        ob_start();
        passthru($cmd);
        $out = ob_get_clean();
        return [true, $out];
    }
    if (function_exists('proc_open')) {
        $descriptorspec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptorspec, $pipes, __DIR__);
        if (is_resource($process)) {
            $out = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);
            return [true, $out . "\n" . $err];
        }
    }
    return [false, 'Execution functions are disabled'];
}

if (file_exists($composerPhar)) {
    echo '<p>▶️ تشغيل: php composer.phar install --no-dev --prefer-dist --optimize-autoloader</p>';
    list($ok, $output) = run_cmd('php composer.phar install --no-dev --prefer-dist --optimize-autoloader');
    echo '<pre>' . h($output) . '</pre>';
    if ($ok && file_exists($vendorAutoload)) {
        echo '<p>✅ تم تثبيت الاعتمادات بنجاح.</p>';
        echo '<p>اذهب إلى <a href="/">الصفحة الرئيسية</a> لتجربة التطبيق.</p>';
        exit;
    } else {
        echo '<p>❌ لم ينجح التثبيت عبر الويب. قد تكون دوال التنفيذ معطلة.</p>';
    }
}

echo '<h2>🧭 خطوات بديلة عبر Hostinger hPanel</h2>';
echo '<ol>';
echo '<li>افتح hPanel → Advanced → PHP Composer</li>';
echo '<li>اختر المسار: Document Root لهذا الموقع (عادة public_html)</li>';
echo '<li>شغّل: composer install --no-dev --prefer-dist --optimize-autoloader</li>';
echo '<li>بعد الانتهاء، أعد تحميل الموقع</li>';
echo '</ol>';

echo '<p>يمكنك أيضًا تمكين SSH مؤقتًا وتشغيل الأمر يدويًا في الجذر.</p>';
?>
