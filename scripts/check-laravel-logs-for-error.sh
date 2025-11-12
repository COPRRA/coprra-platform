#!/bin/bash
# Check Laravel logs for the actual error

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
        puts "Checking Laravel Logs for Error"
        puts "============================================================"
        puts ""
        
        puts "Fetching latest error from logs..."
        send "tail -100 storage/logs/laravel.log | grep -A 30 \"debug-sentry\\|Exception\\|Error\" | tail -50\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "============================================================"
        puts "Testing route directly via artisan..."
        puts "============================================================"
        puts ""
        
        send "php artisan route:list --name=debug.sentry\r"
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

