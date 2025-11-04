<?php
/**
 * SIMPLE FIX: Restore .env file from backup
 */

$backupEnv = '/home/u990109832/domains/coprra.com/public_html_backup_20251104_201438/.env';
$targetEnv = '/home/u990109832/domains/coprra.com/public_html/.env';

echo "<h1>🔧 Restore .env File</h1>";
echo "<p>Backup: <code>$backupEnv</code></p>";
echo "<p>Target: <code>$targetEnv</code></p>";
echo "<hr>";

if (!file_exists($backupEnv)) {
    echo "<p style='color:red;'>❌ Backup file not found!</p>";
    echo "<p>Checking alternative locations...</p>";

    // Try other possible backup locations
    $alternatives = glob('/home/u990109832/domains/coprra.com/public_html_backup*/.env');
    if (!empty($alternatives)) {
        $backupEnv = $alternatives[0];
        echo "<p style='color:orange;'>Found alternative: $backupEnv</p>";
    } else {
        echo "<p style='color:red;'>No backup found anywhere!</p>";
        exit;
    }
}

echo "<p style='color:green;'>✅ Backup file exists</p>";

if (copy($backupEnv, $targetEnv)) {
    echo "<p style='color:green;'><strong>✅ SUCCESS! .env file restored!</strong></p>";

    // Now enable debug mode
    $envContent = file_get_contents($targetEnv);
    $envContent = str_replace('APP_DEBUG=false', 'APP_DEBUG=true', $envContent);
    file_put_contents($targetEnv, $envContent);

    echo "<p style='color:green;'>✅ Debug mode enabled</p>";
    echo "<hr>";
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li><a href='/'>Test homepage</a></li>";
    echo "<li><a href='/products'>Test products</a></li>";
    echo "<li>If working, delete this file: " . __FILE__ . "</li>";
    echo "</ol>";
} else {
    echo "<p style='color:red;'>❌ Failed to copy file (permissions issue)</p>";
    echo "<p><strong>Manual fix needed:</strong></p>";
    echo "<pre>cp $backupEnv $targetEnv</pre>";
}
