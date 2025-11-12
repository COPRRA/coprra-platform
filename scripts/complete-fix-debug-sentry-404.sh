#!/bin/bash
# Complete fix for debug-sentry 404 error

expect << 'EXPECT_EOF'
set timeout 120
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
        puts "Complete Fix for /debug-sentry 404 Error"
        puts "============================================================"
        puts ""
        
        puts "Step 1: Checking current route status..."
        send "php artisan route:list | grep debug-sentry || echo 'Route NOT found'\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "Step 2: Clearing ALL caches..."
        send "php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "Step 3: Verifying routes/web.php exists and contains debug-sentry..."
        send "grep -n 'debug-sentry' routes/web.php | head -3\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "Step 4: Rebuilding route cache..."
        send "php artisan route:cache\r"
        expect {
            -re "Routes cached successfully" {
                puts "✅ Routes cached successfully"
            }
            -re "\\\$ |# " {
            }
        }
        
        puts ""
        puts "Step 5: Verifying route is now registered..."
        send "php artisan route:list | grep debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "Step 6: Testing route with curl..."
        send "curl -s -o /dev/null -w \"HTTP Status: %{http_code}\\n\" https://coprra.com/debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "Step 7: Getting actual response content..."
        send "curl -s https://coprra.com/debug-sentry | head -20\r"
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
echo "✅ Complete Fix Applied"
echo "============================================================"

