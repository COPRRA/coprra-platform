#!/bin/bash
# Complete deployment steps

SSH_HOST="45.87.81.218"
SSH_PORT="65002"
SSH_USER="u990109832"
SSH_PASSWORD="Hamo1510@Rayan146"
PROJECT_PATH="/home/u990109832/domains/coprra.com/public_html"

expect -c "
set timeout 600
spawn ssh -p $SSH_PORT -o StrictHostKeyChecking=no $SSH_USER@$SSH_HOST
expect {
    \"password:\" { send \"$SSH_PASSWORD\r\" }
    \"Password:\" { send \"$SSH_PASSWORD\r\" }
}
expect \"\$ \"
send \"cd $PROJECT_PATH\r\"
expect \"\$ \"
send \"composer install --no-dev --optimize-autoloader\r\"
expect \"\$ \"
send \"php artisan config:clear\r\"
expect \"\$ \"
send \"php artisan config:cache\r\"
expect \"\$ \"
send \"php artisan route:clear\r\"
expect \"\$ \"
send \"php artisan route:cache\r\"
expect \"\$ \"
send \"php artisan view:clear\r\"
expect \"\$ \"
send \"php artisan view:cache\r\"
expect \"\$ \"
send \"echo 'DEPLOYMENT_COMPLETE'\r\"
expect \"DEPLOYMENT_COMPLETE\"
send \"exit\r\"
expect eof
"

echo "✅ Deployment steps completed!"

