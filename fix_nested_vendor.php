<?php
echo "<h2>Fixing Nested Vendor Directory</h2>";

// Check current structure
echo "<h3>Current Structure:</h3>";
if (is_dir('vendor/vendor')) {
    echo "❌ Nested vendor directory detected: vendor/vendor/<br>";
    
    // Move contents from vendor/vendor to vendor
    echo "Moving contents from vendor/vendor/ to vendor/<br>";
    
    // Create a temporary directory
    if (!is_dir('vendor_temp')) {
        mkdir('vendor_temp');
    }
    
    // Move vendor/vendor contents to temp
    $items = scandir('vendor/vendor');
    foreach ($items as $item) {
        if ($item != '.' && $item != '..') {
            $source = 'vendor/vendor/' . $item;
            $dest = 'vendor_temp/' . $item;
            
            if (is_dir($source)) {
                // Recursively copy directory
                exec("cp -r '$source' '$dest'");
            } else {
                copy($source, $dest);
            }
            echo "Moved: $source -> $dest<br>";
        }
    }
    
    // Remove the nested vendor directory
    exec('rm -rf vendor');
    
    // Rename temp to vendor
    rename('vendor_temp', 'vendor');
    
    echo "✅ Fixed nested vendor structure<br>";
} else {
    echo "✅ No nested vendor directory found<br>";
}

echo "<h3>Final Check:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "✅ vendor/autoload.php now exists at correct location!<br>";
    
    try {
        require_once 'vendor/autoload.php';
        echo "✅ Autoloader loaded successfully<br>";
        echo "✅ Laravel should work now!<br>";
    } catch (Exception $e) {
        echo "❌ Error loading autoloader: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ vendor/autoload.php still missing<br>";
    
    // List vendor directory contents
    if (is_dir('vendor')) {
        echo "<h4>Vendor directory contents:</h4>";
        $files = scandir('vendor');
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "- $file<br>";
            }
        }
    }
}
?>