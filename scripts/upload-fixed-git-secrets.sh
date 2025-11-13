#!/bin/bash
# Upload fixed scan-git-secrets.sh to production server

set -e

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
LOCAL_SCRIPT="scripts/scan-git-secrets.sh"
REMOTE_SCRIPT="/home/u990109832/domains/coprra.com/public_html/scripts/scan-git-secrets.sh"

expect << 'EXPECT_EOF'
set timeout 60
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set local_script "scripts/scan-git-secrets.sh"
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
        # Upload fixed script
        send "cat > $remote_script << 'SCRIPTEOF'\r"
        expect "> "
        
        # Read and send script content line by line
        set script_file [open "$local_script" r]
        while {[gets $script_file line] >= 0} {
            send "$line\r"
            expect "> "
        }
        close $script_file
        
        send "SCRIPTEOF\r"
        expect -re "\\\$ |# "
        
        send "chmod +x $remote_script\r"
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

echo "✅ Fixed script uploaded to server"

