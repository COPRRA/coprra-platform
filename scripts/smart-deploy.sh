#!/bin/bash
# Smart deployment: Upload script to server and execute it directly

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"
BRANCH="feature/build-affiliate-store-foundation"

echo "🚀 Smart Deployment Starting..."
echo "📋 Strategy: Upload deployment script to server and execute it"
echo ""

# Create deployment script content
DEPLOY_SCRIPT=$(cat << 'DEPLOY_EOF'
#!/bin/bash
set -e
cd /home/u990109832/domains/coprra.com/public_html

echo "📦 Step 1: Pulling latest changes..."
git fetch origin
git checkout feature/build-affiliate-store-foundation
git pull origin feature/build-affiliate-store-foundation

echo "📦 Step 2: Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "🔧 Step 3: Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "💾 Step 4: Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Deployment completed successfully!"
DEPLOY_EOF
)

# Upload and execute script in one SSH session
expect << EXPECT_EOF
set timeout 300
spawn ssh -p $SSH_PORT -o StrictHostKeyChecking=no $SSH_USER@$SSH_HOST

expect {
    "password:" { send "$SSH_PASSWORD\r"; exp_continue }
    "Password:" { send "$SSH_PASSWORD\r"; exp_continue }
    -re "\\\$ |# " {
        # Create script file on server
        send "cat > /tmp/deploy-now.sh << 'SCRIPT_END'\r"
        expect "> "
        
        # Send script content line by line
        set lines [split "$DEPLOY_SCRIPT" "\n"]
        foreach line \$lines {
            send "\$line\r"
            expect "> "
        }
        
        send "SCRIPT_END\r"
        expect -re "\\\$ |# "
        
        # Make executable and run
        send "chmod +x /tmp/deploy-now.sh && bash /tmp/deploy-now.sh\r"
        expect -re "\\\$ |# "
        
        # Cleanup
        send "rm -f /tmp/deploy-now.sh\r"
        expect -re "\\\$ |# "
        
        send "exit\r"
        expect eof
    }
    timeout { exit 1 }
    eof { exit 1 }
}
EXPECT_EOF

echo ""
echo "✅ Smart deployment completed!"

