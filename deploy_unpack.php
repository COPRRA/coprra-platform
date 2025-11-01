<?php
// يقوم بفك ضغط vendor.zip إن وجد داخل جذر الموقع
// يحتاج إلى تمكين امتداد zip في PHP

$zipFile = __DIR__ . '/vendor.zip';
$targetDir = __DIR__ . '/vendor';

if (!file_exists($zipFile)) {
    http_response_code(404);
    echo "vendor.zip غير موجود";
    exit;
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo "ZipArchive غير متاح في هذا الخادم. يرجى فك الضغط يدويًا عبر المدير";
    exit;
}

$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) {
    http_response_code(500);
    echo "تعذر فتح الأرشيف";
    exit;
}

// تأكد من وجود مجلد vendor
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0775, true);
}

if (!$zip->extractTo($targetDir)) {
    http_response_code(500);
    echo "تعذر الاستخراج";
    $zip->close();
    exit;
}

$zip->close();
echo "تم فك ضغط vendor.zip بنجاح.";

