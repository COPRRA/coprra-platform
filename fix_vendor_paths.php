<?php
echo "<h2>Fixing Vendor Path Issues</h2>";

// Function to recursively rename directories and files
function fixPaths($dir) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $oldPath = $dir . '/' . $item;
        
        // If item contains backslashes, it needs to be fixed
        if (strpos($item, '\\') !== false) {
            $newItem = str_replace('\\', '/', $item);
            $newPath = $dir . '/' . $newItem;
            
            echo "Renaming: $oldPath -> $newPath<br>";
            
            if (is_dir($oldPath)) {
                // Create new directory structure
                $parts = explode('/', $newItem);
                $currentPath = $dir;
                foreach ($parts as $part) {
                    $currentPath .= '/' . $part;
                    if (!is_dir($currentPath)) {
                        mkdir($currentPath, 0755, true);
                    }
                }
                
                // Move contents recursively
                $subItems = scandir($oldPath);
                foreach ($subItems as $subItem) {
                    if ($subItem == '.' || $subItem == '..') continue;
                    $subOldPath = $oldPath . '/' . $subItem;
                    $subNewPath = $newPath . '/' . $subItem;
                    rename($subOldPath, $subNewPath);
                }
                
                // Remove old directory
                rmdir($oldPath);
            } else {
                // Move file
                $newDir = dirname($newPath);
                if (!is_dir($newDir)) {
                    mkdir($newDir, 0755, true);
                }
                rename($oldPath, $newPath);
            }
        } else if (is_dir($oldPath)) {
            // Recursively process subdirectories
            fixPaths($oldPath);
        }
    }
}

// Delete existing vendor directory and re-extract
echo "Removing existing vendor directory...<br>";
if (is_dir('vendor')) {
    // Simple approach: delete and re-extract
    exec('rm -rf vendor');
    echo "✅ Vendor directory removed<br>";
}

// Re-extract vendor.zip
if (file_exists('vendor.zip')) {
    echo "Re-extracting vendor.zip...<br>";
    $zip = new ZipArchive;
    $res = $zip->open('vendor.zip');
    if ($res === TRUE) {
        $zip->extractTo('.');
        $zip->close();
        echo "✅ vendor.zip extracted<br>";
        
        // Check if autoload.php exists now
        if (file_exists('vendor/autoload.php')) {
            echo "✅ vendor/autoload.php now exists!<br>";
        } else {
            echo "❌ vendor/autoload.php still missing<br>";
            
            // Try to fix paths
            echo "Attempting to fix vendor paths...<br>";
            fixPaths('vendor');
            
            if (file_exists('vendor/autoload.php')) {
                echo "✅ vendor/autoload.php fixed!<br>";
            } else {
                echo "❌ vendor/autoload.php still missing after fix<br>";
            }
        }
    } else {
        echo "❌ Cannot open vendor.zip<br>";
    }
} else {
    echo "❌ vendor.zip not found<br>";
}

echo "<h3>Final Check:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "✅ vendor/autoload.php exists<br>";
    try {
        require_once 'vendor/autoload.php';
        echo "✅ Autoloader loaded successfully<br>";
    } catch (Exception $e) {
        echo "❌ Error loading autoloader: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ vendor/autoload.php still missing<br>";
}
?>