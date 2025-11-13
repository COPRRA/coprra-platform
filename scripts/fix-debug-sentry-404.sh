#!/bin/bash
# Diagnose and fix 404 error on /debug-sentry route

set -e

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"

expect << 'EXPECT_EOF'
set timeout 300
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set project_path "/home/u990109832/domains/coprra.com/public_html"

puts "============================================================"
puts "Diagnose & Fix 404 Error on /debug-sentry Route"
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
        puts "✅ Connected to production server"
        puts ""
        
        # Mission 1: Navigate to project directory
        send "cd $project_path\r"
        expect -re "\\\$ |# "
        puts "✅ Navigated to project directory"
        puts ""
        
        # Mission 2: Diagnose the routing issue
        puts "============================================================"
        puts "Mission 2: Diagnose Routing Issue"
        puts "============================================================"
        puts ""
        
        # Check if route cache file exists
        send "ls -la bootstrap/cache/routes-v7.php 2>&1\r"
        expect -re "\\\$ |# "
        
        # Check route list for debug-sentry
        puts "Checking route list for debug-sentry..."
        send "php artisan route:list | grep -i debug-sentry || echo 'Route not found in list'\r"
        expect -re "\\\$ |# "
        
        puts ""
        
        # Mission 3: Apply the fix
        puts "============================================================"
        puts "Mission 3: Apply Fix - Rebuild Route Cache"
        puts "============================================================"
        puts ""
        
        send "php artisan route:cache\r"
        expect {
            -re "Route cache cleared successfully" {
                puts "✅ Route cache cleared"
            }
            -re "Routes cached successfully" {
                puts "✅ Routes cached successfully"
            }
            -re "\\\$ |# " {
                # Command completed
            }
            timeout {
                puts "⚠️ Command timeout"
            }
        }
        
        puts ""
        
        # Verify cache file was created
        send "ls -la bootstrap/cache/routes-v7.php 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        
        # Mission 4: Verify the fix
        puts "============================================================"
        puts "Mission 4: Verify Fix"
        puts "============================================================"
        puts ""
        
        # Check route list again
        puts "Checking route list again..."
        send "php artisan route:list | grep -i debug-sentry || echo 'Route still not found'\r"
        expect -re "\\\$ |# "
        
        puts ""
        
        # Use curl to verify HTTP status
        puts "Testing route with curl..."
        send "curl -o /dev/null -s -w \"HTTP Status: %{http_code}\\n\" https://coprra.com/debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        
        send "exit\r"
        expect eof
    }
    timeout {
        puts "❌ Connection timeout"
        exit 1
    }
    eof {
        puts "❌ Connection closed unexpectedly"
        exit 1
    }
}
EXPECT_EOF

echo ""
echo "============================================================"
echo "✅ Diagnosis and Fix Complete"
echo "============================================================"

