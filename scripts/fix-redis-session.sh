#!/bin/bash
# Fix Redis/Session configuration

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
        puts "Fixing Redis/Session Configuration"
        puts "============================================================"
        puts ""
        
        puts "1. Checking current SESSION_DRIVER..."
        send "grep '^SESSION_DRIVER=' .env | head -1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "2. Checking REDIS_HOST..."
        send "grep '^REDIS_HOST=' .env | head -1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "3. Testing Redis connection..."
        send "php -r \"try { \\\$redis = new Redis(); \\\$redis->connect('127.0.0.1', 6379); echo 'Redis connected'; } catch (Exception \\\$e) { echo 'Redis error: ' . \\\$e->getMessage(); }\" 2>&1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "4. Changing SESSION_DRIVER to file if Redis is not available..."
        send "sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "5. Verifying change..."
        send "grep '^SESSION_DRIVER=' .env | head -1\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "6. Clearing config cache..."
        send "php artisan config:clear\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "7. Testing route again..."
        send "curl -s -o /dev/null -w \"HTTP Status: %{http_code}\\n\" https://coprra.com/debug-sentry\r"
        expect -re "\\\$ |# "
        
        puts ""
        puts "8. Getting route response..."
        send "curl -s https://coprra.com/debug-sentry | head -10\r"
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

