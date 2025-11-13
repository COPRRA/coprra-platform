#!/bin/bash
# Check Laravel logs and .htaccess for routing issues

expect << 'EXPECT_EOF'
set timeout 60
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set project_path "/home/u990109832/domains/coprra.com/public_html"

spawn ssh -p $ssh_port -o StrictHostKeyChecking=no $ssh_user@$ssh_host

expect {
    "password:" {
        send "$ssh_password\r"
        exp_continue
    }
    "Password:" {
        send "$ssh_password\r"
        exp_continue
    }
    -re "\\\$ |# " {
        send "cd $project_path\r"
        expect -re "\\\$ |# "
        
        puts "============================================================"
        puts "Checking Laravel Logs and .htaccess"
        puts "============================================================"
        puts ""
        
        puts "1. Checking latest Laravel log errors..."
        send "tail -50 storage/logs/laravel.log | grep -i \"404\\|NotFound\\|debug-sentry\" | tail -10\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. Checking .htaccess file..."
        send "cat public/.htaccess | head -30\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. Testing route via artisan tinker..."
        send "php artisan tinker --execute=\"echo Route::has('debug.sentry') ? 'Route exists' : 'Route NOT found';\" 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "4. Testing route directly via PHP..."
        send "php -r \"require 'vendor/autoload.php'; \\\$app = require_once 'bootstrap/app.php'; \\\$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap(); echo Route::has('debug.sentry') ? 'Route exists' : 'Route NOT found';\" 2>&1 | head -5\r"
        expect -re "\\\$ |# "
        
        send "exit\r"
        expect eof
    }
    timeout {
        puts "❌ Connection timeout"
        exit 1
    }
    eof {
        puts "❌ Connection closed"
        exit 1
    }
}
EXPECT_EOF

echo ""
echo "============================================================"
echo "✅ Check Complete"
echo "============================================================"

