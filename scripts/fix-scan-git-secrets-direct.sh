#!/bin/bash
# Fix scan-git-secrets.sh directly on server by replacing problematic line

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
        # Kill any running scan-git-secrets.sh processes
        send "pkill -f scan-git-secrets.sh 2>/dev/null || true\r"
        expect -re "\\\$ |# "
        
        # Fix line 131 - replace the jq command with simple empty array
        send "sed -i '131s|.*|            echo \"      \\\\\\\"matches\\\\\\\": []\" # jq not available|' $remote_script\r"
        expect -re "\\\$ |# "
        
        # Verify the fix
        send "grep -n 'matches' $remote_script | head -1\r"
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

