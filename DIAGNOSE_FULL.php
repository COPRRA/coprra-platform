<!DOCTYPE html>
<html>
<head>
    <title>🔬 Complete Diagnostic - COPRRA</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #0a0a0a; color: #00ff00; padding: 20px; }
        .success { background: #004400; border-left: 4px solid #00ff00; padding: 10px; margin: 10px 0; }
        .error { background: #440000; border-left: 4px solid #ff0000; color: #ff6666; padding: 10px; margin: 10px 0; }
        .info { background: #003344; border-left: 4px solid: #00aaff; color: #66ccff; padding: 10px; margin: 10px 0; }
        h1 { color: #00ffff; text-shadow: 0 0 10px #00ffff; }
        h2 { color: #ffff00; }
        pre { background: #1a1a1a; padding: 15px; overflow-x: auto; border: 1px solid #00ff00; }
    </style>
</head>
<body>
    <h1>🔬 COMPLETE DIAGNOSTIC SCAN - COPRRA</h1>
    <p>Time: <?php echo date('Y-m-d H:i:s'); ?></p>
    <hr>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Step 1: Bootstrap Laravel Application</h2>";
try {
    require __DIR__.'/vendor/autoload.php';
    echo "<div class='success'>✅ Autoloader loaded</div>";

    $app = require_once __DIR__.'/bootstrap/app.php';
    echo "<div class='success'>✅ Application bootstrapped</div>";

    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    echo "<div class='success'>✅ Kernel bootstrapped</div>";

} catch (\Exception $e) {
    echo "<div class='error'>❌ Bootstrap Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    die();
}

echo "<h2>Step 2: Test Service Container Bindings</h2>";

$bindings = [
    'App\Services\ProductService',
    'App\Repositories\ProductRepository',
    'App\Services\Contracts\CacheServiceContract',
    'App\Http\Controllers\ProductController',
    'App\Http\Controllers\CategoryController',
    'App\Http\Controllers\BrandController',
];

foreach ($bindings as $binding) {
    try {
        $isBound = app()->bound($binding);
        if ($isBound) {
            echo "<div class='success'>✅ {$binding}: BOUND</div>";
        } else {
            echo "<div class='info'>ℹ️  {$binding}: NOT BOUND (will try to resolve anyway)</div>";
        }
    } catch (\Exception $e) {
        echo "<div class='error'>❌ {$binding}: ERROR - " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo "<h2>Step 3: Attempt to Instantiate ProductController</h2>";
try {
    $controller = app(\App\Http\Controllers\ProductController::class);
    echo "<div class='success'>✅ SUCCESS: ProductController instantiated!</div>";
    echo "<div class='info'>Controller class: " . get_class($controller) . "</div>";

    // Check if index method exists
    if (method_exists($controller, 'index')) {
        echo "<div class='success'>✅ index() method EXISTS</div>";
    } else {
        echo "<div class='error'>❌ index() method MISSING</div>";
    }

} catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
    echo "<div class='error'>❌ BINDING RESOLUTION ERROR</div>";
    echo "<div class='error'>Message: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (\Exception $e) {
    echo "<div class='error'>❌ GENERAL ERROR</div>";
    echo "<div class='error'>Type: " . get_class($e) . "</div>";
    echo "<div class='error'>Message: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>Step 4: Test Database Connection</h2>";
try {
    $pdo = DB::connection()->getPdo();
    echo "<div class='success'>✅ Database connected</div>";

    // Count products
    $count = DB::table('products')->count();
    echo "<div class='info'>Products in database: {$count}</div>";

    if ($count === 0) {
        echo "<div class='error'>⚠️  WARNING: No products in database!</div>";
    }

} catch (\Exception $e) {
    echo "<div class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>Step 5: Test ProductService</h2>";
try {
    $service = app(\App\Services\ProductService::class);
    echo "<div class='success'>✅ ProductService instantiated</div>";

    // Check if getPaginatedProducts exists
    if (method_exists($service, 'getPaginatedProducts')) {
        echo "<div class='success'>✅ getPaginatedProducts() method EXISTS</div>";

        // Try to call it
        try {
            $products = $service->getPaginatedProducts(5);
            echo "<div class='success'>✅ getPaginatedProducts() executed successfully</div>";
            echo "<div class='info'>Returned: " . get_class($products) . "</div>";
            echo "<div class='info'>Count: " . $products->count() . " items</div>";
        } catch (\Exception $e) {
            echo "<div class='error'>❌ Error calling getPaginatedProducts(): " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='error'>❌ getPaginatedProducts() method MISSING</div>";
    }

} catch (\Exception $e) {
    echo "<div class='error'>❌ ProductService Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>Step 6: Simulate ProductController->index() Call</h2>";
try {
    $controller = app(\App\Http\Controllers\ProductController::class);
    $request = \Illuminate\Http\Request::create('/products', 'GET');

    echo "<div class='info'>Attempting to call index() method...</div>";

    $response = $controller->index($request);
    echo "<div class='success'>✅ index() executed successfully!</div>";
    echo "<div class='info'>Response type: " . get_class($response) . "</div>";

} catch (\Exception $e) {
    echo "<div class='error'>❌ CRITICAL ERROR FOUND!</div>";
    echo "<div class='error'><strong>Exception Type:</strong> " . get_class($e) . "</div>";
    echo "<div class='error'><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='error'><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</div>";
    echo "<h3>Full Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>Step 7: Environment Check</h2>";
echo "<div class='info'>APP_DEBUG: " . (config('app.debug') ? 'TRUE' : 'FALSE') . "</div>";
echo "<div class='info'>APP_ENV: " . config('app.env') . "</div>";
echo "<div class='info'>APP_URL: " . config('app.url') . "</div>";
echo "<div class='info'>PHP Version: " . phpversion() . "</div>";

echo "<hr>";
echo "<h2>🎯 DIAGNOSTIC COMPLETE</h2>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

?>

</body>
</html>
