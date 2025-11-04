<!DOCTYPE html>
<html>
<head>
    <title>Fix Database - Add deleted_at Column</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        .success { background: #040; padding: 10px; margin: 10px 0; }
        .error { background: #400; color: #f66; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 Adding deleted_at Column to Products Table</h1>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo "<div class='success'>✅ Laravel bootstrapped</div>";

    // Check if column exists
    $hasColumn = DB::select("SHOW COLUMNS FROM products LIKE 'deleted_at'");

    if (count($hasColumn) > 0) {
        echo "<div class='success'>ℹ️ Column 'deleted_at' already exists!</div>";
    } else {
        echo "<div class='success'>Adding column 'deleted_at'...</div>";

        DB::statement("ALTER TABLE products ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");

        echo "<div class='success'>✅ Column 'deleted_at' added successfully!</div>";
    }

    // Verify
    $columns = DB::select("SHOW COLUMNS FROM products");
    echo "<h2>Current products table structure:</h2>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo $col->Field . " - " . $col->Type . "\n";
    }
    echo "</pre>";

    echo "<hr>";
    echo "<h2 style='color: #0f0;'>🎉 DATABASE FIXED!</h2>";
    echo "<p>Test now: <a href='/products' style='color: #fff;'>/products</a></p>";

} catch (\Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

</body>
</html>
