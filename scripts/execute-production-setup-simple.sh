#!/bin/bash
# Execute production setup script on remote server via SSH
# Simple version that properly handles completion

set -e

# SSH Configuration
SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"
LOCAL_SCRIPT_PATH="$(dirname "$0")/production-setup-complete.sh"
REMOTE_SCRIPT_PATH="${PROJECT_PATH}/scripts/production-setup-complete.sh"

echo "============================================================"
echo "Production Setup Script Executor"
echo "============================================================"
echo "Connecting to: ${SSH_HOST}:${SSH_PORT}"
echo "User: ${SSH_USER}"
echo "Project Path: ${PROJECT_PATH}"
echo "============================================================"
echo ""

# Check if local script exists
if [ ! -f "$LOCAL_SCRIPT_PATH" ]; then
    echo "❌ ERROR: Local script not found: $LOCAL_SCRIPT_PATH"
    exit 1
fi

echo "✅ Local script found: $LOCAL_SCRIPT_PATH"
echo ""

# Use expect to automate SSH login and command execution
expect << 'EXPECT_EOF'
set timeout 1800

set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set project_path "/home/u990109832/domains/coprra.com/public_html"
set remote_script_path "/home/u990109832/domains/coprra.com/public_html/scripts/production-setup-complete.sh"
set local_script_path "scripts/production-setup-complete.sh"

puts "Connecting to $ssh_host:$ssh_port as $ssh_user"
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
    "yes/no" {
        send "yes\r"
        exp_continue
    }
    -re "\\\$ |# " {
        # Upload script
        send "mkdir -p $project_path/scripts\r"
        expect -re "\\\$ |# "
        
        send "cat > $remote_script_path << 'SCRIPTEOF'\r"
        expect "> "
        
        # Send script content line by line
        set script_file [open "$local_script_path" r]
        while {[gets $script_file line] >= 0} {
            send "$line\r"
            expect "> "
        }
        close $script_file
        
        send "SCRIPTEOF\r"
        expect -re "\\\$ |# "
        
        send "chmod +x $remote_script_path\r"
        expect -re "\\\$ |# "
        
        # Execute script and capture exit code
        send "cd $project_path && bash $remote_script_path; EXIT_CODE=\$?; echo \"SCRIPT_EXIT_CODE:\$EXIT_CODE\"\r"
        
        # Wait for completion - look for exit code marker
        expect {
            -re "SCRIPT_EXIT_CODE:(\[0-9\]+)" {
                set exit_code $expect_out(1,string)
                puts "\n\nScript completed with exit code: $exit_code"
            }
            timeout {
                puts "\n\nScript execution timeout after 30 minutes"
                set exit_code 1
            }
        }
        
        # Close connection
        send "exit\r"
        expect eof
        
        exit [expr {$exit_code}]
    }
    timeout {
        puts "Connection timeout"
        exit 1
    }
    eof {
        puts "Connection closed unexpectedly"
        exit 1
    }
}
EXPECT_EOF

EXIT_CODE=$?

echo ""
echo "============================================================"
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ Execution Complete - SUCCESS"
else
    echo "⚠️  Execution completed with exit code: $EXIT_CODE"
fi
echo "============================================================"

exit $EXIT_CODE

