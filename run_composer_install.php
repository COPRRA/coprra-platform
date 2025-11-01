<?php
echo "<h2>Running Composer Install</h2>";

// Change to the correct directory
chdir(__DIR__);
echo "Current directory: " . getcwd() . "<br>";

// Check if composer.json exists
if (!file_exists('composer.json')) {
    echo "❌ composer.json not found<br>";
    exit;
}
echo "✅ composer.json found<br>";

// Download composer if not exists
if (!file_exists('composer.phar')) {
    echo "Downloading composer.phar...<br>";
    $composerInstaller = file_get_contents('https://getcomposer.org/installer');
    if ($composerInstaller) {
        file_put_contents('composer-setup.php', $composerInstaller);
        exec('php composer-setup.php', $output, $return_var);
        if ($return_var === 0) {
            echo "✅ Composer downloaded successfully<br>";
            unlink('composer-setup.php');
        } else {
            echo "❌ Failed to download composer<br>";
            echo "Output: " . implode('<br>', $output) . "<br>";
        }
    }
}

if (file_exists('composer.phar')) {
    echo "✅ composer.phar found<br>";
    
    // Run composer install
    echo "Running composer install...<br>";
    exec('php composer.phar install --no-dev --optimize-autoloader 2>&1', $output, $return_var);
    
    echo "Return code: $return_var<br>";
    echo "Output:<br>";
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
    
    // Check if autoload.php was created
    if (file_exists('vendor/autoload.php')) {
        echo "<h3>✅ SUCCESS: vendor/autoload.php created!</h3>";
        echo "File size: " . filesize('vendor/autoload.php') . " bytes<br>";
    } else {
        echo "<h3>❌ vendor/autoload.php still missing</h3>";
    }
} else {
    echo "❌ composer.phar not available<br>";
}
?>