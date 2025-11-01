<?php
echo "<h2>Running Composer Install</h2>";

// Check if composer.json exists
if (!file_exists('composer.json')) {
    echo "❌ composer.json not found<br>";
    exit;
}

echo "✅ composer.json found<br>";

// Try to run composer install
echo "<h3>Running composer install...</h3>";

// First, try to download composer if it doesn't exist
if (!file_exists('composer.phar')) {
    echo "Downloading composer...<br>";
    $composerInstaller = file_get_contents('https://getcomposer.org/installer');
    if ($composerInstaller) {
        file_put_contents('composer-setup.php', $composerInstaller);
        
        // Run the installer
        ob_start();
        include 'composer-setup.php';
        $output = ob_get_clean();
        echo "Composer installer output: $output<br>";
        
        // Clean up
        unlink('composer-setup.php');
    }
}

// Now try to run composer install
if (file_exists('composer.phar')) {
    echo "Running composer.phar install...<br>";
    
    // Set environment variables
    putenv('COMPOSER_ALLOW_SUPERUSER=1');
    putenv('COMPOSER_NO_INTERACTION=1');
    
    // Run composer install
    $output = shell_exec('php composer.phar install --no-dev --optimize-autoloader 2>&1');
    echo "<pre>$output</pre>";
    
    // Check if autoload.php was created
    if (file_exists('vendor/autoload.php')) {
        echo "✅ vendor/autoload.php created successfully!<br>";
    } else {
        echo "❌ vendor/autoload.php still missing<br>";
    }
} else {
    echo "❌ composer.phar not found<br>";
    
    // Try alternative: use system composer if available
    $output = shell_exec('composer install --no-dev --optimize-autoloader 2>&1');
    if ($output) {
        echo "<h3>System composer output:</h3>";
        echo "<pre>$output</pre>";
        
        if (file_exists('vendor/autoload.php')) {
            echo "✅ vendor/autoload.php created successfully!<br>";
        } else {
            echo "❌ vendor/autoload.php still missing<br>";
        }
    } else {
        echo "❌ No composer available<br>";
    }
}

echo "<h3>Final Check:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "✅ vendor/autoload.php exists<br>";
    try {
        require_once 'vendor/autoload.php';
        echo "✅ Autoloader loaded successfully<br>";
        echo "✅ Laravel should work now!<br>";
    } catch (Exception $e) {
        echo "❌ Error loading autoloader: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ vendor/autoload.php still missing<br>";
}
?>