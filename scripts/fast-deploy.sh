#!/bin/bash
# Fast deployment with clear output

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"

echo "🚀 Starting fast deployment..."

expect << 'EXPECT_SCRIPT'
set timeout 300
set ssh_host "45.87.81.218"
set ssh_port "65002"
set ssh_user "u990109832"
set ssh_password "Hamo1510@Rayan146"
set project_path "/home/u990109832/domains/coprra.com/public_html"

spawn ssh -p $ssh_port -o StrictHostKeyChecking=no $ssh_user@$ssh_host

expect {
    "password:" { send "$ssh_password\r"; exp_continue }
    "Password:" { send "$ssh_password\r"; exp_continue }
    -re "\\\$ |# " {
        puts "✅ Connected to server"
        
        send "cd $project_path\r"
        expect -re "\\\$ |# "
        
        puts "📦 Running composer install..."
        send "composer install --no-dev --optimize-autoloader 2>&1 | head -20\r"
        expect -re "\\\$ |# "
        
        puts "🔧 Clearing config cache..."
        send "php artisan config:clear\r"
        expect -re "\\\$ |# "
        
        puts "💾 Caching config..."
        send "php artisan config:cache\r"
        expect -re "\\\$ |# "
        
        puts "🛣️  Clearing route cache..."
        send "php artisan route:clear\r"
        expect -re "\\\$ |# "
        
        puts "💾 Caching routes..."
        send "php artisan route:cache\r"
        expect -re "\\\$ |# "
        
        puts "👁️  Clearing view cache..."
        send "php artisan view:clear\r"
        expect -re "\\\$ |# "
        
        puts "💾 Caching views..."
        send "php artisan view:cache\r"
        expect -re "\\\$ |# "
        
        puts "✅ Deployment complete!"
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
EXPECT_SCRIPT

echo "✅ Deployment script finished!"

