<?php
// permissions_fix.php: يجعل مجلدات Laravel قابلة للكتابة على الاستضافة المشتركة
// يستهدف: storage/ و bootstrap/cache

function chmod_recursive($path, $dirMode = 0775, $fileMode = 0664)
{
    if (!file_exists($path)) {
        echo "[FAIL] المسار غير موجود: {$path}\n";
        return;
    }
    if (is_dir($path)) {
        @chmod($path, $dirMode);
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $full = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($full)) {
                chmod_recursive($full, $dirMode, $fileMode);
            } else {
                @chmod($full, $fileMode);
            }
        }
    } else {
        @chmod($path, $fileMode);
    }
}

// استدعى من جذر الموقع (حيث توجد مجلدات app/, storage/, bootstrap/, public/)
echo "[INFO] ضبط صلاحيات storage و bootstrap/cache...\n";
chmod_recursive(__DIR__ . '/storage');
chmod_recursive(__DIR__ . '/bootstrap/cache');
echo "[DONE] تم ضبط الصلاحيات بنجاح.\n";

