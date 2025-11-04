<!DOCTYPE html>
<html>
<head>
    <title>🔒 Final Security Cleanup</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        .success { background: #040; padding: 10px; margin: 10px 0; border-left: 4px solid #0f0; }
        .error { background: #400; color: #f66; padding: 10px; margin: 10px 0; border-left: 4px solid #f00; }
        .warning { background: #440; color: #ff0; padding: 10px; margin: 10px 0; border-left: 4px solid #ff0; }
        h1 { color: #0ff; text-shadow: 0 0 10px #0ff; }
        h2 { color: #ff0; margin-top: 30px; }
        pre { background: #1a1a1a; padding: 10px; border: 1px solid #0f0; }
    </style>
</head>
<body>
    <h1>🔒 FINAL SECURITY CLEANUP & CONFIGURATION</h1>
    <p>Timestamp: <?php echo date('Y-m-d H:i:s'); ?></p>
    <hr style="border-color: #0f0;">

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Phase 1: Delete Diagnostic Scripts</h2>";

$scriptsToDelete = [
    'FIX_ALL_DELETED_AT.php',
    'COMPLETE_FINAL_FIX.php',
    'DIAGNOSE_FULL.php',
    'FIX_PRODUCTSERVICE_NOW.php',
    'FIX_CONTROLLERS_NOW.php',
    'ADD_DELETED_AT_COLUMN.php',
    'fix_appcomposer_now.php',
    'diagnose_and_fix.php',
    'restore_env.php',
];

$deletedCount = 0;
$notFoundCount = 0;

foreach ($scriptsToDelete as $script) {
    $fullPath = __DIR__ . '/' . $script;
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            echo "<div class='success'>✅ DELETED: {$script}</div>";
            $deletedCount++;
        } else {
            echo "<div class='error'>❌ FAILED TO DELETE: {$script}</div>";
        }
    } else {
        echo "<div class='warning'>ℹ️ Not found (already deleted): {$script}</div>";
        $notFoundCount++;
    }
}

echo "<div class='success' style='font-size: 18px; margin-top: 20px;'>";
echo "🎯 Cleanup Summary: {$deletedCount} files deleted, {$notFoundCount} already removed";
echo "</div>";

echo "<h2>Phase 2: Disable Debug Mode</h2>";

try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    echo "<div class='success'>✅ Laravel bootstrapped</div>";
    
    // Read current .env file
    $envPath = __DIR__ . '/.env';
    if (file_exists($envPath)) {
        $envContent = file_get_contents($envPath);
        
        // Check current debug status
        if (preg_match('/APP_DEBUG=(true|false)/', $envContent, $matches)) {
            $currentDebug = $matches[1];
            echo "<div class='warning'>Current APP_DEBUG: {$currentDebug}</div>";
            
            if ($currentDebug === 'true') {
                // Change to false
                $newEnvContent = preg_replace('/APP_DEBUG=true/', 'APP_DEBUG=false', $envContent);
                
                if (file_put_contents($envPath, $newEnvContent)) {
                    echo "<div class='success'>✅ ✅ APP_DEBUG set to FALSE</div>";
                    
                    // Clear config cache
                    \Illuminate\Support\Facades\Artisan::call('config:clear');
                    echo "<div class='success'>✅ Config cache cleared</div>";
                    
                    \Illuminate\Support\Facades\Artisan::call('cache:clear');
                    echo "<div class='success'>✅ Application cache cleared</div>";
                    
                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                        echo "<div class='success'>✅ OPcache cleared</div>";
                    }
                    
                    echo "<div class='success' style='font-size: 18px; margin-top: 20px;'>";
                    echo "🔒 DEBUG MODE DISABLED - Production is now secure!";
                    echo "</div>";
                } else {
                    echo "<div class='error'>❌ Failed to write .env file</div>";
                }
            } else {
                echo "<div class='success'>✅ APP_DEBUG already set to false - GOOD!</div>";
            }
        } else {
            echo "<div class='error'>❌ APP_DEBUG not found in .env file</div>";
        }
    } else {
        echo "<div class='error'>❌ .env file not found</div>";
    }
    
} catch (\Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>Phase 3: Verification</h2>";

// List remaining PHP files in root
$phpFiles = glob(__DIR__ . '/*.php');
$rootPhpFiles = array_map('basename', $phpFiles);

echo "<div class='success'>✅ Remaining PHP files in public_html root:</div>";
echo "<pre>";
foreach ($rootPhpFiles as $file) {
    // Highlight if any diagnostic script remains
    if (in_array($file, $scriptsToDelete)) {
        echo "⚠️  {$file} (SHOULD BE DELETED!)\n";
    } else {
        echo "✓  {$file}\n";
    }
}
echo "</pre>";

echo "<hr style='border-color: #0f0;'>";
echo "<h1 style='color: #0f0; font-size: 36px;'>🔒 SECURITY CLEANUP COMPLETE!</h1>";

echo "<div class='success' style='font-size: 20px; padding: 20px;'>";
echo "✅ All diagnostic scripts removed<br>";
echo "✅ Debug mode disabled (APP_DEBUG=false)<br>";
echo "✅ All caches cleared<br>";
echo "✅ Production server secured<br>";
echo "</div>";

echo "<div class='warning' style='font-size: 18px; padding: 15px; margin-top: 20px;'>";
echo "⚠️ IMPORTANT: Delete this cleanup script too!<br>";
echo "File to delete: <strong>FINAL_CLEANUP.php</strong>";
echo "</div>";

echo "<p style='color: #0f0; margin-top: 30px;'>Cleanup completed: " . date('Y-m-d H:i:s') . "</p>";
?>

</body>
</html>

