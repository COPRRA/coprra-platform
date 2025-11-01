<?php
/**
 * COPRRA Database Setup Script for Hostinger
 * This script tests database connection and sets up the database
 */

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $env = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $env);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_DATABASE'] ?? 'u990109832_coprra_db';
$username = $_ENV['DB_USERNAME'] ?? 'u990109832_gasser';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "🔧 COPRRA Database Setup\n";
echo "========================\n";
echo "Host: $host\n";
echo "Database: $database\n";
echo "Username: $username\n";
echo "Password: " . (empty($password) ? "❌ NOT SET" : "✅ SET") . "\n";
echo "------------------------\n";

if (empty($password)) {
    echo "❌ Error: Database password is not set in .env file\n";
    echo "Please update the DB_PASSWORD in your .env file\n";
    exit(1);
}

try {
    // Test database connection
    echo "🔌 Testing database connection...\n";
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "✅ Database connection successful!\n";
    
    // Check if tables exist
    echo "📋 Checking existing tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "📝 No tables found. Database is empty.\n";
        echo "🔄 You need to run migrations:\n";
        echo "   php artisan migrate --force\n";
    } else {
        echo "📊 Found " . count($tables) . " tables:\n";
        foreach ($tables as $table) {
            echo "   - $table\n";
        }
    }
    
    // Test basic query
    echo "🧪 Testing basic query...\n";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "✅ MySQL Version: " . $result['version'] . "\n";
    
    // Check Laravel specific tables
    echo "🔍 Checking Laravel tables...\n";
    $laravelTables = ['migrations', 'users', 'password_reset_tokens', 'failed_jobs'];
    $missingTables = [];
    
    foreach ($laravelTables as $table) {
        if (!in_array($table, $tables)) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "⚠️ Missing Laravel tables: " . implode(', ', $missingTables) . "\n";
        echo "🔄 Run migrations to create missing tables\n";
    } else {
        echo "✅ All basic Laravel tables exist\n";
    }
    
    echo "\n🎉 Database setup check completed!\n";
    echo "📝 Next steps:\n";
    echo "   1. If tables are missing, run: php artisan migrate --force\n";
    echo "   2. If you need sample data, run: php artisan db:seed --force\n";
    echo "   3. Clear cache: php artisan cache:clear\n";
    echo "   4. Test your website: https://coprra.com\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "   1. Check if database password is correct\n";
    echo "   2. Verify database name and username\n";
    echo "   3. Ensure database server is accessible\n";
    echo "   4. Check if database exists in hosting panel\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
    exit(1);
}
?>