#!/bin/bash
# Fix scan-git-secrets.sh on production server to remove jq dependency

set -e

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
REMOTE_SCRIPT="/home/u990109832/domains/coprra.com/public_html/scripts/scan-git-secrets.sh"

expect << 'EXPECT_EOF'
set timeout 60
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
        # Fix line 131 to check for jq first
        send "sed -i '131s/.*/            # Convert matches to JSON array (without jq dependency)/' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '132i\\            if command -v jq \\&> /dev/null; then' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '133i\\                echo \"      \\\\\\\"matches\\\\\\\": \\\\$(echo \"\\\\\\\$MATCHES\" | jq -R -s -c '\''split(\"\\\\n\") | map(select(. != \"\"))'\'')' >> \"\\\\\\\$JSON_OUTPUT\"' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '134i\\            else' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '135i\\                # Manual JSON array conversion without jq' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '136i\\                echo -n \"      \\\\\\\"matches\\\\\\\": [\" >> \"\\\\\\\$JSON_OUTPUT\"' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '137i\\                FIRST_MATCH=1' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '138i\\                echo \"\\\\\\\$MATCHES\" | while IFS= read -r line; do' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '139i\\                    if [ -n \"\\\\\\\$line\" ]; then' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '140i\\                        if [ \\\\\\\$FIRST_MATCH -eq 1 ]; then' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '141i\\                            FIRST_MATCH=0' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '142i\\                        else' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '143i\\                            echo -n \",\" >> \"\\\\\\\$JSON_OUTPUT\"' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '144i\\                        fi' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '145i\\                        ESCAPED_LINE=\\\\$(echo \"\\\\\\\$line\" | sed '\''s/\\\\\\\\/\\\\\\\\\\\\\\\\/g'\'' | sed '\''s/\"/\\\\\\\\\"/g'\'' | sed '\''s/\\\\t/\\\\\\\\t/g'\'' | sed '\''s/\\\\r/\\\\\\\\r/g'\'' | sed '\''s/\\\\n/\\\\\\\\n/g'\'')' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '146i\\                        echo -n \"\\\\\\\"\\\\\\\$ESCAPED_LINE\\\\\\\"\" >> \"\\\\\\\$JSON_OUTPUT\"' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '147i\\                    fi' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '148i\\                done' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '149i\\                echo \"]\" >> \"\\\\\\\$JSON_OUTPUT\"' $remote_script\r"
        expect -re "\\\$ |# "
        
        send "sed -i '150i\\            fi' $remote_script\r"
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

echo "✅ Script fixed on server"

