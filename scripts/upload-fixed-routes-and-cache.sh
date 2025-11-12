#!/bin/bash
# Upload fixed routes/web.php and rebuild route cache

set -e

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
LOCAL_ROUTES="routes/web.php"
REMOTE_ROUTES="/home/u990109832/domains/coprra.com/public_html/routes/web.php"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"

expect << 'EXPECT_EOF'
set timeout 300
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set local_routes "routes/web.php"
set remote_routes "/home/u990109832/domains/coprra.com/public_html/routes/web.php"
set project_path "/home/u990109832/domains/coprra.com/public_html"

puts "============================================================"
puts "Upload Fixed Routes and Rebuild Cache"
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
        
        send "cd $project_path\r"
        expect -re "\\\$ |# "
        
        puts "📤 Uploading fixed routes/web.php..."
        
        # Backup original
        send "cp $remote_routes ${remote_routes}.backup\r"
        expect -re "\\\$ |# "
        
        # Upload fixed routes file
        send "cat > $remote_routes << 'ROUTESEOF'\r"
        expect "> "
        
        # Read and send routes file line by line
        set routes_file [open "$local_routes" r]
        while {[gets $routes_file line] >= 0} {
            send "$line\r"
            expect "> "
        }
        close $routes_file
        
        send "ROUTESEOF\r"
        expect -re "\\\$ |# "
        
        puts "✅ Routes file uploaded"
        puts "🔄 Rebuilding route cache..."
        
        send "php artisan route:clear\r"
        expect -re "\\\$ |# "
        
        send "php artisan route:cache\r"
        expect {
            -re "Routes cached successfully" {
                puts "✅ Routes cached successfully"
            }
            -re "\\\$ |# " {
            }
        }
        
        puts ""
        puts "🔍 Verifying route..."
        
        send "php artisan route:list | grep -i debug-sentry || echo 'Route not found'\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "🌐 Testing route with curl..."
        
        send "curl -o /dev/null -s -w \"HTTP Status: %{http_code}\\n\" https://coprra.com/debug-sentry\r"
        expect -re "\\\$ |# "
        
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

echo ""
echo "============================================================"
echo "✅ Fix Complete"
echo "============================================================"

