<?php
/**
 * FIXED: Comprehensive Diagnostic and Fix Script
 * Upload to public_html/ and access via browser
 */

$projectRoot = __DIR__; // This is already public_html
$results = [];

// Function to run artisan command
function runArtisan($command) {
    global $projectRoot;
    $fullCommand = "cd $projectRoot && php artisan $command 2>&1";
    exec($fullCommand, $output, $returnVar);
    return [
        'command' => $command,
        'output' => implode("\n", $output),
        'success' => $returnVar === 0
    ];
}

echo "<html><head><style>
body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
.success { color: #4ec9b0; }
.error { color: #f48771; }
.warning { color: #dcdcaa; }
.section { background: #252526; padding: 15px; margin: 10px 0; border-left: 3px solid #007acc; }
pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 COPRRA Deployment Diagnostics (FIXED)</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Project Root: <code>$projectRoot</code></p>";
echo "<hr>";

// ============================================
// 1. ENVIRONMENT CHECK
// ============================================
echo "<div class='section'>";
echo "<h2>1. Environment Check</h2>";

$envFile = $projectRoot . '/.env';
$envExists = file_exists($envFile);
echo "<p>📄 .env file: " . ($envExists ? "<span class='success'>✅ EXISTS</span>" : "<span class='error'>❌ MISSING</span>") . "</p>";

if (!$envExists) {
    echo "<p class='error'><strong>CRITICAL: .env file is missing!</strong></p>";
    echo "<p>This is the PRIMARY cause of 500 errors.</p>";

    // Check if there's a backup
    $backupPath = dirname($projectRoot) . '/public_html_backup_20251104_201438/.env';
    if (file_exists($backupPath)) {
        echo "<p class='warning'>⚠️ Found backup .env at: $backupPath</p>";
        echo "<p>Attempting to restore...</p>";

        if (copy($backupPath, $envFile)) {
            echo "<p class='success'>✅ .env file restored from backup!</p>";
            $envExists = true;
        } else {
            echo "<p class='error'>❌ Failed to restore .env (permissions issue)</p>";
            echo "<p><strong>MANUAL FIX REQUIRED:</strong></p>";
            echo "<pre>cp $backupPath $envFile</pre>";
        }
    } else {
        echo "<p class='error'>❌ No backup found either!</p>";
        echo "<p><strong>MANUAL FIX:</strong> Copy .env from local project or backup</p>";
    }
}

if ($envExists) {
    $envContent = file_get_contents($envFile);
    preg_match('/APP_DEBUG=(\w+)/', $envContent, $debugMatch);
    $debugMode = $debugMatch[1] ?? 'unknown';
    echo "<p>🐛 APP_DEBUG: <span class='warning'>$debugMode</span></p>";

    // Enable debug mode if false
    if ($debugMode === 'false') {
        $envContent = str_replace('APP_DEBUG=false', 'APP_DEBUG=true', $envContent);
        if (file_put_contents($envFile, $envContent)) {
            echo "<p><span class='success'>✅ Debug mode ENABLED</span></p>";
        } else {
            echo "<p><span class='error'>❌ Cannot write to .env (permissions)</span></p>";
        }
    }

    preg_match('/APP_ENV=(\w+)/', $envContent, $envMatch);
    echo "<p>🌍 APP_ENV: " . ($envMatch[1] ?? 'unknown') . "</p>";

    preg_match('/DB_DATABASE=(.+)/', $envContent, $dbMatch);
    echo "<p>🗄️ DB_DATABASE: " . ($dbMatch[1] ?? 'NOT SET') . "</p>";
}
echo "</div>";

// ============================================
// 2. CRITICAL PATHS CHECK
// ============================================
echo "<div class='section'>";
echo "<h2>2. Critical Paths</h2>";

$paths = [
    ['vendor/autoload.php', 'Composer Autoloader'],
    ['storage', 'Storage Directory'],
    ['storage/logs', 'Logs Directory'],
    ['storage/framework', 'Framework Directory'],
    ['bootstrap/cache', 'Bootstrap Cache'],
    ['app', 'App Directory'],
    ['public', 'Public Directory'],
];

foreach ($paths as $p) {
    $fullPath = $projectRoot . '/' . $p[0];
    $exists = file_exists($fullPath);
    $writable = is_writable($fullPath);

    $status = $exists ? '✅' : '❌';
    $perms = $writable ? 'writable' : 'READ-ONLY';
    $class = $exists ? 'success' : 'error';

    echo "<p class='$class'>$status {$p[1]}: $fullPath ($perms)</p>";
}
echo "</div>";

// ============================================
// 3. STOP IF NO .env
// ============================================
if (!$envExists) {
    echo "<div class='section'>";
    echo "<h2>⛔ CANNOT PROCEED</h2>";
    echo "<p class='error'>The .env file MUST exist before running Laravel commands.</p>";
    echo "<p><strong>Next Step:</strong></p>";
    echo "<ol>";
    echo "<li>Restore .env file from backup</li>";
    echo "<li>Run this script again</li>";
    echo "</ol>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// ============================================
// 4. RECENT ERRORS FROM LOG
// ============================================
echo "<div class='section'>";
echo "<h2>3. Recent Laravel Errors</h2>";

$logFile = $projectRoot . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $recentErrors = [];

    foreach (array_reverse($lines) as $line) {
        if (strpos($line, '.ERROR') !== false) {
            $recentErrors[] = $line;
            if (count($recentErrors) >= 5) break;
        }
    }

    if (!empty($recentErrors)) {
        echo "<pre>";
        foreach (array_reverse($recentErrors) as $error) {
            echo htmlspecialchars(substr($error, 0, 300)) . "\n\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='success'>✅ No recent errors found</p>";
    }
} else {
    echo "<p class='error'>❌ Log file not found: $logFile</p>";
}
echo "</div>";

// ============================================
// 5. RUN MIGRATIONS
// ============================================
echo "<div class='section'>";
echo "<h2>4. Database Migrations</h2>";

$migrateResult = runArtisan('migrate --force');
echo "<p>Command: <code>php artisan migrate --force</code></p>";
echo "<pre>" . htmlspecialchars($migrateResult['output']) . "</pre>";
echo "<p>" . ($migrateResult['success'] ? "<span class='success'>✅ Migrations completed</span>" : "<span class='error'>❌ Migrations failed</span>") . "</p>";
echo "</div>";

// ============================================
// 6. CLEAR ALL CACHES
// ============================================
echo "<div class='section'>";
echo "<h2>5. Clear All Caches</h2>";

$cacheCommands = ['config:clear', 'cache:clear', 'view:clear', 'route:clear'];
foreach ($cacheCommands as $cmd) {
    $result = runArtisan($cmd);
    $status = $result['success'] ? '✅' : '❌';
    $class = $result['success'] ? 'success' : 'error';
    echo "<p class='$class'>📝 $cmd: $status</p>";
    if (!empty($result['output'])) {
        echo "<pre style='font-size:10px;'>" . htmlspecialchars(substr($result['output'], 0, 200)) . "</pre>";
    }
}
echo "</div>";

// ============================================
// 7. OPTIMIZE APPLICATION
// ============================================
echo "<div class='section'>";
echo "<h2>6. Optimize Application</h2>";

$optimizeResult = runArtisan('optimize');
echo "<pre>" . htmlspecialchars(substr($optimizeResult['output'], 0, 500)) . "</pre>";
echo "<p>" . ($optimizeResult['success'] ? "<span class='success'>✅ Optimization complete</span>" : "<span class='error'>❌ Optimization failed</span>") . "</p>";
echo "</div>";

// ============================================
// 8. FINAL RECOMMENDATIONS
// ============================================
echo "<div class='section'>";
echo "<h2>7. Next Steps - Test The Site!</h2>";
echo "<ol>";
echo "<li>✅ <strong>Test homepage:</strong> <a href='/' target='_blank' style='color:#4ec9b0;'>https://coprra.com</a></li>";
echo "<li>✅ <strong>Test products:</strong> <a href='/products' target='_blank' style='color:#4ec9b0;'>https://coprra.com/products</a></li>";
echo "<li>✅ <strong>Test categories:</strong> <a href='/categories' target='_blank' style='color:#4ec9b0;'>https://coprra.com/categories</a></li>";
echo "<li>✅ <strong>Test brands:</strong> <a href='/brands' target='_blank' style='color:#4ec9b0;'>https://coprra.com/brands</a></li>";
echo "<li>✅ <strong>Test login:</strong> <a href='/login' target='_blank' style='color:#4ec9b0;'>https://coprra.com/login</a></li>";
echo "<li>⚠️ <strong>After testing:</strong> Set APP_DEBUG=false in .env</li>";
echo "<li>🗑️ <strong>DELETE THIS FILE:</strong> " . __FILE__ . "</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><em>Diagnostic completed at " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
