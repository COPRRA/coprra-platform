#!/bin/bash
# Upload sentry_transport_test.php to server

expect << 'EXPECT_EOF'
set timeout 60
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set project_path "/home/u990109832/domains/coprra.com/public_html"

spawn scp -P $ssh_port -o StrictHostKeyChecking=no public/sentry_transport_test.php $ssh_user@$ssh_host:$project_path/public/

expect {
    "password:" {
        send "$ssh_password\r"
        exp_continue
    }
    "Password:" {
        send "$ssh_password\r"
        exp_continue
    }
    eof {
        puts "File uploaded successfully"
    }
    timeout {
        puts "Upload timeout"
        exit 1
    }
}
EXPECT_EOF

echo ""
echo "Testing the script..."
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
        
        puts "Testing sentry_transport_test.php via curl..."
        send "curl -s https://coprra.com/sentry_transport_test.php\r"
        expect -re "\\\$ |# "
        
        send "exit\r"
        expect eof
    }
}
EXPECT_EOF

