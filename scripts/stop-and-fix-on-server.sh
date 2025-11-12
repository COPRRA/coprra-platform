#!/bin/bash
# Stop running processes and upload fixed script

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
        # Stop any running scan-git-secrets.sh or production-setup processes
        send "pkill -f scan-git-secrets.sh 2>/dev/null; pkill -f production-setup-complete.sh 2>/dev/null; sleep 2\r"
        expect -re "\\\$ |# "
        
        puts "✅ Stopped running processes"
        
        send "exit\r"
        expect eof
    }
    timeout {
        puts "Connection timeout"
        exit 1
    }
    eof {
        puts "Connection closed"
        exit 1
    }
}
EXPECT_EOF

echo "✅ Processes stopped. Now uploading fixed script..."

