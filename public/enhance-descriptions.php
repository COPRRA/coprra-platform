<?php
// Temporary script to enhance product descriptions
// Access via: https://coprra.com/enhance-descriptions.php?secret=ENHANCE2025&limit=10

// Security check
if ($_GET['secret'] !== 'ENHANCE2025') {
    die('❌ Access Denied');
}

set_time_limit(300); // 5 minutes max

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Services\ProductDescriptionGenerator;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Description Enhancement</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
        .separator { border-top: 1px solid #0f0; margin: 20px 0; }
    </style>
</head>
<body>
<h1>🚀 Product Description Enhancement</h1>
<hr>

<?php

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$force = isset($_GET['force']) && $_GET['force'] === '1';

echo "<p class='info'>📌 Limit: {$limit} products</p>";
echo "<p class='info'>📌 Force: " . ($force ? 'Yes' : 'No') . "</p>\n";
echo "<hr>\n";

// Get products
$query = Product::with(['brand', 'category']);

if (!$force) {
    $query->whereRaw('LENGTH(description) < 300');
}

$products = $query->limit($limit)->get();

echo "<p class='success'>📦 Found " . $products->count() . " products</p>\n";
echo "<hr>\n";

$success = 0;
$failed = 0;

foreach ($products as $product) {
    echo "<div style='margin-bottom: 30px;'>";
    echo "<h3>Product #{$product->id}: {$product->name}</h3>";
    echo "<p><strong>Brand:</strong> " . ($product->brand->name ?? 'N/A') . "</p>";
    echo "<p><strong>Category:</strong> " . ($product->category->name ?? 'N/A') . "</p>";
    echo "<p><strong>Old Length:</strong> " . strlen($product->description ?? '') . " chars</p>";

    try {
        $productData = [
            'name' => $product->name,
            'brand' => $product->brand->name ?? '',
            'category' => $product->category->name ?? '',
            'description' => $product->description ?? '',
            'specs' => json_decode($product->specifications, true) ?? [],
            'features' => json_decode($product->features, true) ?? [],
        ];

        $enhancedDescription = ProductDescriptionGenerator::generate($productData);

        $product->update(['description' => $enhancedDescription]);

        echo "<p class='success'>✅ New Length: " . strlen($enhancedDescription) . " chars</p>";
        echo "<p><strong>Preview:</strong></p>";
        echo "<div style='background: #222; padding: 10px; margin: 10px 0; white-space: pre-wrap;'>";
        echo htmlspecialchars(substr($enhancedDescription, 0, 300)) . "...";
        echo "</div>";

        $success++;
    } catch (\Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        $failed++;
    }

    echo "</div><hr>";
    flush();
}

echo "<h2 class='success'>✅ Enhancement Complete!</h2>";
echo "<p><strong>Successfully Enhanced:</strong> {$success}</p>";
echo "<p><strong>Failed:</strong> {$failed}</p>";
echo "<p><strong>Success Rate:</strong> " . round(($success / $products->count()) * 100, 2) . "%</p>";

?>

<hr>
<p class='info'>🎉 Done! Check a product page to see the enhanced description.</p>
<p><a href="https://coprra.com/products" style="color: #0ff;">View Products →</a></p>

</body>
</html>
