#!/bin/bash
set -e

echo "🚀 Starting COPRRA deployment..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}❌ .env file not found${NC}"
    echo "Creating .env from .env.example..."
    cp .env.example .env
    echo -e "${YELLOW}⚠️  Please configure .env before continuing${NC}"
    exit 1
fi

# Pull latest code (if git repo)
if [ -d .git ]; then
    echo "📦 Pulling latest code..."
    git pull origin main || echo "⚠️  Not in a git repository or pull failed"
fi

# Build Docker images
echo "🔨 Building Docker images..."
docker-compose build --no-cache app

# Stop running containers
echo "⏹️  Stopping old containers..."
docker-compose down

# Start new containers
echo "▶️  Starting new containers..."
docker-compose up -d

# Wait for services to be healthy
echo "⏳ Waiting for services to be healthy..."
sleep 15

# Check if app container is running
if ! docker-compose ps | grep -q "coprra-app.*Up"; then
    echo -e "${RED}❌ App container failed to start${NC}"
    docker-compose logs app
    exit 1
fi

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear

# Optimize for production
echo "⚡ Optimizing for production..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache
docker-compose exec -T app composer dump-autoload --optimize

# Check health
echo "🏥 Checking application health..."
sleep 5

# Try to get health status from nginx container
HEALTH_CHECK=$(docker-compose exec -T nginx curl -s http://app:9000/health/ping 2>/dev/null || echo "failed")

if echo "$HEALTH_CHECK" | grep -q '"status":"ok"'; then
    echo -e "${GREEN}✅ Deployment successful!${NC}"
    echo "🎉 Application is running"
    echo "📊 Access via: http://localhost"
else
    echo -e "${YELLOW}⚠️  Health check inconclusive${NC}"
    echo "Checking container status..."
fi

# Show status
echo ""
echo "📊 Container status:"
docker-compose ps

echo ""
echo -e "${GREEN}Deployment complete!${NC}"
echo "View logs with: docker-compose logs -f app"
