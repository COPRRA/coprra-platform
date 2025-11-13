# PowerShell deployment script for affiliate foundation
$SSH_HOST = "45.87.81.218"
$SSH_PORT = "65002"
$SSH_USER = "u990109832"
$SSH_PASSWORD = "Hamo1510@Rayan146"
$PROJECT_PATH = "/home/u990109832/domains/coprra.com/public_html"
$BRANCH = "feature/build-affiliate-store-foundation"

# Create SSH commands
$commands = @"
cd $PROJECT_PATH
git fetch origin
git checkout $BRANCH
git pull origin $BRANCH
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache
echo 'Deployment completed successfully'
exit
"@

# Use plink (PuTTY Link) if available, otherwise use ssh
$sshCommand = "ssh -p $SSH_PORT $SSH_USER@$SSH_HOST `"$commands`""

Write-Host "🚀 Starting deployment..." -ForegroundColor Green
Write-Host "Connecting to: $SSH_HOST:$SSH_PORT" -ForegroundColor Yellow

# Execute SSH commands
$commands | ssh -p $SSH_PORT $SSH_USER@$SSH_HOST

Write-Host "✅ Deployment completed!" -ForegroundColor Green

