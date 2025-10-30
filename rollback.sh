#!/bin/bash
set -e

echo "↩️  Rolling back COPRRA deployment..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if we're in a git repository
if [ ! -d .git ]; then
    echo -e "${RED}❌ Not a git repository. Cannot rollback.${NC}"
    exit 1
fi

# Get current and previous commit
CURRENT_COMMIT=$(git rev-parse HEAD)
PREVIOUS_COMMIT=$(git rev-parse HEAD~1)

echo "Current commit: $CURRENT_COMMIT"
echo "Rolling back to: $PREVIOUS_COMMIT"

# Confirm rollback
read -p "Are you sure you want to rollback? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Rollback cancelled."
    exit 0
fi

# Checkout previous commit
echo "📦 Checking out previous commit..."
git checkout $PREVIOUS_COMMIT

# Rebuild and restart containers
echo "🔨 Rebuilding Docker images..."
docker-compose build --no-cache app

echo "⏹️  Stopping containers..."
docker-compose down

echo "▶️  Starting containers with previous version..."
docker-compose up -d

# Wait for services
echo "⏳ Waiting for services..."
sleep 15

# Run migrations rollback (one step)
echo "🗄️  Rolling back last migration batch..."
docker-compose exec -T app php artisan migrate:rollback --force --step=1

# Clear and rebuild caches
echo "🧹 Clearing caches..."
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear

echo "⚡ Rebuilding caches..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Check health
echo "🏥 Checking application health..."
sleep 5

HEALTH_CHECK=$(docker-compose exec -T nginx curl -s http://app:9000/health/ping 2>/dev/null || echo "failed")

if echo "$HEALTH_CHECK" | grep -q '"status":"ok"'; then
    echo -e "${GREEN}✅ Rollback successful!${NC}"
    echo "📊 Rolled back to commit: $PREVIOUS_COMMIT"
else
    echo -e "${YELLOW}⚠️  Health check inconclusive after rollback${NC}"
fi

# Show status
echo ""
echo "📊 Container status:"
docker-compose ps

echo ""
echo -e "${YELLOW}⚠️  Remember: You are now on a detached HEAD at $PREVIOUS_COMMIT${NC}"
echo "To return to main branch: git checkout main"
