#!/bin/bash
# Mission 1: Verify Environment

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
        puts "MISSION 1: Environment Verification"
        puts "============================================================"
        puts ""
        
        puts "1. PHP Artisan Version:"
        send "php artisan --version\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. PHP Version:"
        send "php --version\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. SENTRY_LARAVEL_DSN Verification:"
        send "grep \"SENTRY_LARAVEL_DSN\" .env | head -1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "4. SENTRY_TRACES_SAMPLE_RATE:"
        send "grep \"SENTRY_TRACES_SAMPLE_RATE\" .env | head -1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "5. Checking Sentry package installation:"
        send "composer show sentry/sentry-laravel 2>/dev/null | head -5 || echo 'Package not found'\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "6. Checking PHP cURL extension:"
        send "php -m | grep -i curl || echo 'cURL extension not found'\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "============================================================"
        puts "✅ Mission 1 Complete"
        puts "============================================================"
        
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

