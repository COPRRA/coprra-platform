#!/bin/bash
# Get latest error from Laravel logs

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
        puts "Getting Latest Error"
        puts "============================================================"
        puts ""
        
        puts "Triggering route..."
        send "curl -s https://coprra.com/debug-sentry > /dev/null 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "Getting latest error entry..."
        send "tail -200 storage/logs/laravel.log | grep -A 100 \"production.ERROR\" | tail -100\r"
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

