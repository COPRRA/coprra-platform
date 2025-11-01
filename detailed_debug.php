<?php
echo "<h2>Detailed Laravel Debug</h2>";

// Check current working directory
echo "<h3>Current Working Directory:</h3>";
echo "getcwd(): " . getcwd() . "<br>";
echo "__DIR__: " . __DIR__ . "<br>";

// Check vendor paths with different methods
echo "<h3>Vendor Path Checks:</h3>";
$paths_to_check = [
    'vendor/autoload.php',
    './vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    getcwd() . '/vendor/autoload.php'
];

foreach ($paths_to_check as $path) {
    if (file_exists($path)) {
        echo "✅ $path exists<br>";
    } else {
        echo "❌ $path missing<br>";
    }
}

// List vendor directory contents
echo "<h3>Vendor Directory Listing:</h3>";
if (is_dir('vendor')) {
    $vendor_files = scandir('vendor');
    foreach ($vendor_files as $file) {
        if ($file != '.' && $file != '..') {
            $full_path = 'vendor/' . $file;
            echo $file . (is_dir($full_path) ? '/' : '') . "<br>";
        }
    }
} else {
    echo "❌ vendor/ directory not found<br>";
}

// Check if we can read the autoload file
echo "<h3>Autoload File Test:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "✅ vendor/autoload.php exists<br>";
    if (is_readable('vendor/autoload.php')) {
        echo "✅ vendor/autoload.php is readable<br>";
        $content = file_get_contents('vendor/autoload.php', false, null, 0, 200);
        echo "First 200 chars: " . htmlspecialchars($content) . "<br>";
    } else {
        echo "❌ vendor/autoload.php is not readable<br>";
    }
} else {
    echo "❌ vendor/autoload.php does not exist<br>";
}

// Try to include autoload
echo "<h3>Autoload Include Test:</h3>";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        echo "✅ Autoloader included successfully<br>";
        
        // Test Laravel bootstrap
        if (file_exists('bootstrap/app.php')) {
            echo "✅ bootstrap/app.php exists<br>";
            try {
                $app = require_once 'bootstrap/app.php';
                echo "✅ Laravel app created successfully<br>";
                echo "App class: " . get_class($app) . "<br>";
            } catch (Exception $e) {
                echo "❌ Error creating Laravel app: " . $e->getMessage() . "<br>";
                echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
            }
        } else {
            echo "❌ bootstrap/app.php missing<br>";
        }
    } else {
        echo "❌ Cannot include vendor/autoload.php - file not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error including autoloader: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

// Check .env file
echo "<h3>Environment File Check:</h3>";
if (file_exists('.env')) {
    echo "✅ .env file exists<br>";
    $env_content = file_get_contents('.env');
    $lines = explode("\n", $env_content);
    echo "First few lines:<br>";
    for ($i = 0; $i < min(5, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]) . "<br>";
    }
} else {
    echo "❌ .env file missing<br>";
}
?>