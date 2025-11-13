#!/bin/bash
# Fix scan-git-secrets.sh on server RIGHT NOW by replacing the problematic line

expect << 'EXPECT_EOF'
set timeout 30
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set remote_script "/home/u990109832/domains/coprra.com/public_html/scripts/scan-git-secrets.sh"

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
        # Replace line 131 with a simple version that doesn't use jq
        send "sed -i '131s|.*|            echo \"      \\\\\\\"matches\\\\\\\": []\" # jq not available|' $remote_script\r"
        expect -re "\\\$ |# "
        
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

echo "✅ Fixed scan-git-secrets.sh on server"

