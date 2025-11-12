#!/bin/bash
# Fix APP_KEY issue

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
        puts "Fixing APP_KEY Issue"
        puts "============================================================"
        puts ""
        
        puts "1. Checking if APP_KEY exists in .env..."
        send "grep -q '^APP_KEY=' .env && echo 'APP_KEY exists' || echo 'APP_KEY NOT found'\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. Generating APP_KEY..."
        send "php artisan key:generate --force\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. Verifying APP_KEY was set..."
        send "grep '^APP_KEY=' .env | head -1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "4. Clearing config cache..."
        send "php artisan config:clear\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "5. Testing route again..."
        send "curl -s -o /dev/null -w \"HTTP Status: %{http_code}\\n\" https://coprra.com/debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "6. Getting route response..."
        send "curl -s https://coprra.com/debug-sentry | head -5\r"
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
echo "✅ Fix Complete"
echo "============================================================"

