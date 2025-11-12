#!/bin/bash
# Mission 3: Test Server Outgoing Connectivity

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
        puts "MISSION 3: Test Server Outgoing Connectivity"
        puts "============================================================"
        puts ""
        
        puts "1. Testing DNS resolution for Sentry endpoint..."
        send "nslookup o4510335302696960.ingest.de.sentry.io 2>&1 || host o4510335302696960.ingest.de.sentry.io 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. Testing direct curl connection to Sentry ingestion endpoint..."
        puts "   (Full verbose output will be shown)"
        send "curl -v --max-time 10 https://o4510335302696960.ingest.de.sentry.io 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. Testing with POST request (simulating actual Sentry event)..."
        send "curl -v -X POST --max-time 10 -H \"Content-Type: application/json\" -d '{\"test\":\"data\"}' https://o4510335302696960.ingest.de.sentry.io/api/4510335304859728/envelope/ 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "4. Checking firewall/iptables rules..."
        send "iptables -L -n 2>/dev/null | head -20 || echo 'iptables not accessible or not configured'\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "5. Checking PHP cURL configuration..."
        send "php -r \"print_r(curl_version());\" 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "============================================================"
        puts "✅ Mission 3 Complete"
        puts "============================================================"
        
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

