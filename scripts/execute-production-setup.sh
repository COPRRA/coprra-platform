#!/bin/bash
# Execute production setup script on remote server via SSH
# Uses expect for automated password entry

set -e

# SSH Configuration
export SSH_HOST="45.87.81.218"
export SSH_PORT="65002"
export SSH_USER="u990109832"
export SSH_PASSWORD="Hamo1510@Rayan146"
export PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"
export LOCAL_SCRIPT_PATH="$(dirname "$0")/production-setup-complete.sh"
export REMOTE_SCRIPT_PATH="${PROJECT_PATH}/scripts/production-setup-complete.sh"

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

# Check if expect is available
if ! command -v expect &> /dev/null; then
    echo "⚠️  Warning: expect not found. Attempting SSH without expect..."
    echo ""
    echo "You may need to enter password manually"
    echo ""
    
    # Try SSH without expect (will prompt for password)
    ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" << EOF
mkdir -p $PROJECT_PATH/scripts
cat > $REMOTE_SCRIPT_PATH << 'SCRIPTEOF'
$(cat "$LOCAL_SCRIPT_PATH")
SCRIPTEOF
chmod +x $REMOTE_SCRIPT_PATH
cd $PROJECT_PATH && bash $REMOTE_SCRIPT_PATH
EOF
    
    exit $?
fi

echo "🔌 Establishing SSH connection with expect..."
echo ""

# Use expect to automate SSH login and command execution
expect << EXPECT_SCRIPT
set timeout 1800
set script_path "$LOCAL_SCRIPT_PATH"
set project_path "$PROJECT_PATH"
set remote_script_path "$REMOTE_SCRIPT_PATH"
set ssh_port "$SSH_PORT"
set ssh_user "$SSH_USER"
set ssh_host "$SSH_HOST"
set ssh_password "$SSH_PASSWORD"

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
        set script_file [open "$script_path" r]
        while {[gets $script_file line] >= 0} {
            send "$line\r"
            expect "> "
        }
        close $script_file
        
        send "SCRIPTEOF\r"
        expect -re "\\\$ |# "
        
        send "chmod +x $remote_script_path\r"
        expect -re "\\\$ |# "
        
        # Execute script and capture output
        send "cd $project_path && bash $remote_script_path; echo \"EXIT_CODE:\$?\"\r"
        
        # Wait for completion - look for exit code marker
        expect {
            -re "EXIT_CODE:(\[0-9\]+)" {
                set exit_code $expect_out(1,string)
            }
            -re ".*PRODUCTION SETUP.*COMPLETE.*" {
                # Try to get exit code
                send "echo \"EXIT_CODE:\$?\"\r"
                expect -re "EXIT_CODE:(\[0-9\]+)" {
                    set exit_code $expect_out(1,string)
                }
            }
            timeout {
                puts "Script execution timeout after 30 minutes"
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
EXPECT_SCRIPT

EXIT_CODE=$?

echo ""
echo "============================================================"
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ Execution Complete"
else
    echo "⚠️  Execution completed with exit code: $EXIT_CODE"
fi
echo "============================================================"

exit $EXIT_CODE


