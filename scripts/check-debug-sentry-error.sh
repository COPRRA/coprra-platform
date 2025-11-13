#!/bin/bash
# Check Laravel logs for debug-sentry route error

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
puts "Checking Laravel Logs for debug-sentry Error"
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
        
        puts "📋 Checking latest Laravel log entries..."
        puts "----------------------------------------"
        send "tail -100 storage/logs/laravel.log | grep -A 20 -B 5 'debug-sentry' || tail -50 storage/logs/laravel.log\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "🔍 Testing route directly with PHP..."
        puts "----------------------------------------"
        send "php artisan tinker --execute=\"\\\$response = app()->make('Illuminate\\\\Http\\\\Request')->create('/debug-sentry', 'GET'); echo 'Route test';\" 2>&1 | head -20\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "🔍 Checking if Sentry is configured..."
        send "php artisan tinker --execute=\"echo config('sentry.dsn') ? 'Sentry DSN configured' : 'Sentry DSN not configured';\" 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "🔍 Checking if Sentry service is bound..."
        send "php artisan tinker --execute=\"echo app()->bound('sentry') ? 'Sentry is bound' : 'Sentry is NOT bound';\" 2>&1\r"
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

