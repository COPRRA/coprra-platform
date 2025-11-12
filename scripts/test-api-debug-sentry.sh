#!/bin/bash
# Test API debug-sentry route

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
        
        puts "Testing /api/debug-sentry route..."
        send "curl -s https://coprra.com/api/debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        send "curl -s -o /dev/null -w \"HTTP Status: %{http_code}\\n\" https://coprra.com/api/debug-sentry\r"
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

