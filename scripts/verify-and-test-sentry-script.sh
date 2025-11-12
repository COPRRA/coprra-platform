#!/bin/bash
# Verify and test sentry script

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
        puts "Verifying sentry_transport_test.php"
        puts "============================================================"
        puts ""
        
        puts "1. Checking if file exists..."
        send "ls -la public/sentry_transport_test.php 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. Checking file content..."
        send "head -5 public/sentry_transport_test.php\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. Testing via PHP CLI directly..."
        send "cd public && php sentry_transport_test.php 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "4. Testing via curl with full path..."
        send "curl -s http://localhost/sentry_transport_test.php 2>&1 | head -20\r"
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

