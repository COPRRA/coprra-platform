<?php
// 🔥 COPRRA - Advanced Database Setup Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔥 COPRRA - Advanced Database Setup</h1>";
echo "<style>body{font-family:Arial;background:#f0f0f0;padding:20px;}</style>";

// Database credentials
$host = 'localhost';
$username = 'u574849695_coprra';
$password = 'Hamo1510@Rayan146';
$database = 'u574849695_coprra';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ اتصال MySQL ناجح</p>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ تم إنشاء قاعدة البيانات</p>";
    
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ اتصال قاعدة البيانات ناجح</p>";
    
    // Create essential tables
    $tables = [
        "users" => "
            CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                email_verified_at TIMESTAMP NULL,
                password VARCHAR(255) NOT NULL,
                remember_token VARCHAR(100) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        "migrations" => "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        "sessions" => "
            CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                payload LONGTEXT NOT NULL,
                last_activity INT NOT NULL,
                INDEX sessions_user_id_index (user_id),
                INDEX sessions_last_activity_index (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    foreach ($tables as $table => $sql) {
        $pdo->exec($sql);
        echo "<p>✅ تم إنشاء جدول $table</p>";
    }
    
    // Insert sample data
    $pdo->exec("
        INSERT IGNORE INTO users (id, name, email, password) VALUES 
        (1, 'Admin', 'admin@coprra.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
    ");
    echo "<p>✅ تم إدراج البيانات الأساسية</p>";
    
    echo "<h2>🎉 تم إعداد قاعدة البيانات بنجاح!</h2>";
    echo "<p><a href='/' style='background:#007cba;color:white;padding:10px;text-decoration:none;border-radius:5px;'>🌐 زيارة الموقع</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ خطأ: " . $e->getMessage() . "</p>";
    
    // Try alternative connection
    echo "<h3>🔄 محاولة اتصال بديل...</h3>";
    try {
        $alt_pdo = new PDO("mysql:host=localhost;dbname=u574849695_coprra", "u574849695_coprra", "Hamo1510@Rayan146");
        echo "<p>✅ الاتصال البديل ناجح!</p>";
    } catch (PDOException $e2) {
        echo "<p style='color:red;'>❌ الاتصال البديل فشل: " . $e2->getMessage() . "</p>";
    }
}
?>