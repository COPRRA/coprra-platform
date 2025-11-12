#!/bin/bash
# Upload fixed production-setup-complete.sh and execute it

set -e

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
LOCAL_SCRIPT="scripts/production-setup-complete.sh"
REMOTE_SCRIPT="/home/u990109832/domains/coprra.com/public_html/scripts/production-setup-complete.sh"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"

expect << 'EXPECT_EOF'
set timeout 1800
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set local_script "scripts/production-setup-complete.sh"
set remote_script "/home/u990109832/domains/coprra.com/public_html/scripts/production-setup-complete.sh"
set project_path "/home/u990109832/domains/coprra.com/public_html"

puts "============================================================"
puts "Uploading and Executing Fixed Production Setup Script"
puts "============================================================"
puts ""

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
        puts "✅ Connected to server"
        puts "📤 Uploading fixed script..."
        
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
        
        puts "✅ Script uploaded successfully"
        puts "🚀 Executing fixed script..."
        puts ""
        
        # Execute script and capture exit code
        send "cd $project_path && bash $remote_script; EXIT_CODE=\$?; echo \"SCRIPT_EXIT_CODE:\$EXIT_CODE\"\r"
        
        # Wait for completion
        expect {
            -re "SCRIPT_EXIT_CODE:(\[0-9\]+)" {
                set exit_code $expect_out(1,string)
                puts "\n\n✅ Script completed with exit code: $exit_code"
            }
            timeout {
                puts "\n\n⚠️ Script execution timeout after 30 minutes"
                set exit_code 1
            }
        }
        
        # Close connection
        send "exit\r"
        expect eof
        
        exit [expr {$exit_code}]
    }
    timeout {
        puts "❌ Connection timeout"
        exit 1
    }
    eof {
        puts "❌ Connection closed unexpectedly"
        exit 1
    }
}
EXPECT_EOF

EXIT_CODE=$?

echo ""
echo "============================================================"
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ SUCCESS: Production setup completed successfully!"
else
    echo "⚠️  Execution completed with exit code: $EXIT_CODE"
fi
echo "============================================================"

exit $EXIT_CODE

