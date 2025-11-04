<?php
/**
 * CRITICAL FIXES DEPLOYMENT SCRIPT - Execute this script via web browser
 * Access: https://coprra.com/deploy_critical_fixes.php
 */
echo "<h1>DEPLOYMENT IN PROGRESS</h1>";
echo "<p>Executing critical fixes deployment...</p>";

// Change to project root
chdir('/home/u990109832/domains/coprra.com/public_html');

// Step 1: Update .env to MySQL
echo "<h2>Step 1: Switching to MySQL...</h2>";
$env = file_get_contents('.env');
$env = preg_replace('/DB_CONNECTION=.*/','DB_CONNECTION=mysql', $env);
$env = preg_replace('/DB_DATABASE=.*/','DB_DATABASE=u990109832_coprra_db', $env);
file_put_contents('.env', $env);
echo "<p>✅ Database config updated</p>";

// Step 2: Pull from GitHub
echo "<h2>Step 2: Pulling from GitHub...</h2>";
exec('git pull origin main 2>&1', $output);
echo "<pre>" . implode("\n", $output) . "</pre>";

// Step 3: Run seeders
echo "<h2>Step 3: Running seeders...</h2>";
exec('php artisan db:seed --class=ProductSeeder --force 2>&1', $output2);
echo "<pre>" . implode("\n", $output2) . "</pre>";

// Step 4: Clear caches
echo "<h2>Step 4: Clearing caches...</h2>";
exec('php artisan config:clear && php artisan cache:clear 2>&1', $output3);
echo "<pre>" . implode("\n", $output3) . "</pre>";

echo "<h2>✅ DEPLOYMENT COMPLETE</h2>";
echo "<p>Please delete this file now!</p>";
?>
