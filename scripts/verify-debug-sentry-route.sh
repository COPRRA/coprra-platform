#!/bin/bash
# Verify /debug-sentry route by fetching actual content

set -e

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"

expect << 'EXPECT_EOF'
set timeout 60
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set project_path "/home/u990109832/domains/coprra.com/public_html"

puts "============================================================"
puts "Verifying /debug-sentry Route - Full Content Check"
puts "============================================================"
puts ""

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
        
        puts "🔍 Checking route in route list..."
        send "php artisan route:list | grep debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "🌐 Fetching actual page content..."
        puts "----------------------------------------"
        
        send "curl -s https://coprra.com/debug-sentry | head -50\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "----------------------------------------"
        puts ""
        
        puts "📊 Checking HTTP status code..."
        send "curl -o /dev/null -s -w \"HTTP Status Code: %{http_code}\\n\" https://coprra.com/debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "🔍 Checking if response is JSON..."
        send "curl -s https://coprra.com/debug-sentry | head -1 | grep -q '{' && echo '✅ Response is JSON' || echo '❌ Response is not JSON'\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "📋 Full response (first 100 lines)..."
        puts "----------------------------------------"
        send "curl -s https://coprra.com/debug-sentry 2>&1 | head -100\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "----------------------------------------"
        
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
echo "✅ Verification Complete"
echo "============================================================"

