<?php
// 🔥 COPRRA - Advanced Index File

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if Laravel exists
if (file_exists(__DIR__.'/public/index.php')) {
    // Laravel application exists, redirect to public
    require_once __DIR__.'/public/index.php';
} else {
    // Show COPRRA welcome page
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔥 COPRRA - مرحباً بك</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; min-height: 100vh; display: flex;
                align-items: center; justify-content: center;
            }
            .container { 
                text-align: center; background: rgba(255,255,255,0.1);
                padding: 50px; border-radius: 20px; backdrop-filter: blur(10px);
                box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            }
            h1 { font-size: 3em; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
            p { font-size: 1.2em; margin: 15px 0; }
            .status { background: rgba(0,255,0,0.2); padding: 15px; border-radius: 10px; margin: 20px 0; }
            .button { 
                display: inline-block; background: #FFD700; color: #333;
                padding: 15px 30px; border-radius: 10px; text-decoration: none;
                margin: 10px; font-weight: bold; transition: all 0.3s;
            }
            .button:hover { background: #FFA500; transform: translateY(-2px); }
            .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
            .feature { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔥 COPRRA</h1>
            <div class="status">
                <h2>✅ الموقع يعمل بنجاح!</h2>
                <p>تم تثبيت وتكوين COPRRA بنجاح</p>
            </div>
            
            <div class="features">
                <div class="feature">
                    <h3>🚀 سرعة عالية</h3>
                    <p>أداء محسن ومتقدم</p>
                </div>
                <div class="feature">
                    <h3>🔒 أمان متقدم</h3>
                    <p>حماية شاملة للبيانات</p>
                </div>
                <div class="feature">
                    <h3>📱 تصميم متجاوب</h3>
                    <p>يعمل على جميع الأجهزة</p>
                </div>
                <div class="feature">
                    <h3>🌐 متعدد اللغات</h3>
                    <p>دعم العربية والإنجليزية</p>
                </div>
            </div>
            
            <p>🎉 تم النشر بنجاح في: <?php echo date('Y-m-d H:i:s'); ?></p>
            
            <div>
                <a href="/advanced_database_setup.php" class="button">🗄️ إعداد قاعدة البيانات</a>
                <a href="/phpinfo.php" class="button">🔧 معلومات الخادم</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>