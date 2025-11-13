#!/bin/bash
# Final test of debug-sentry route

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
        puts "Final Test of /debug-sentry Route"
        puts "============================================================"
        puts ""
        
        puts "1. Clearing all caches..."
        send "php artisan config:clear && php artisan route:clear && php artisan cache:clear\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. Rebuilding route cache..."
        send "php artisan route:cache\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. Testing route..."
        send "curl -s https://coprra.com/debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "4. Checking HTTP status..."
        send "curl -s -o /dev/null -w \"HTTP Status: %{http_code}\\n\" https://coprra.com/debug-sentry\r"
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
echo "✅ Test Complete"
echo "============================================================"

