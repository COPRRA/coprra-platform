# 🐳 Docker Setup Guide

## Table of Contents
- [Quick Start](#quick-start)
- [Environment Configurations](#environment-configurations)
- [Docker Compose Files](#docker-compose-files)
- [First Time Setup](#first-time-setup)
- [Common Commands](#common-commands)
- [Health Checks](#health-checks)
- [Scaling](#scaling)
- [Monitoring](#monitoring)
- [Troubleshooting](#troubleshooting)

---

## Quick Start

### Prerequisites
- Docker Engine 24.0+ ([Install Docker](https://docs.docker.com/get-docker/))
- Docker Compose 2.20+ (included with Docker Desktop)
- 4GB+ RAM available
- 10GB+ free disk space

### Development Environment (Fastest)

```bash
# 1. Clone the repository
git clone <repository-url>
cd coprra

# 2. Start all services
docker-compose up -d

# 3. Run migrations
docker-compose exec app php artisan migrate

# 4. Access the application
open http://localhost
```

**That's it!** 🎉 The application is now running.

---

## Environment Configurations

COPRRA supports multiple Docker environments for different use cases:

### Available Environments

| Environment | Use Case | Docker Compose File | When to Use |
|-------------|----------|---------------------|-------------|
| **Local Development** | Default dev setup | `docker-compose.yml` | Daily development |
| **Advanced Dev** | Dev with hot reload | `docker-compose.dev.yml` | Frontend development |
| **Production (Single)** | Single-server prod | `docker-compose.prod.yml` | Small deployments |
| **Production (Scaled)** | Multi-instance prod | `docker/docker-compose.scale.yml` | High traffic |
| **Docker Swarm** | Orchestrated cluster | `docker-compose.swarm.yml` | Enterprise deployments |
| **Local (Simple)** | Minimal local setup | `docker-compose.local.yml` | Quick testing |
| **Enhanced** | Full feature set | `docker-compose.enhanced.yml` | Feature testing |

---

## Docker Compose Files

### 1. `docker-compose.yml` - Default Development

**Services:**
- ✅ PHP-FPM 8.4 (app)
- ✅ Nginx (web server)
- ✅ MySQL 8.0 (database)
- ✅ Redis 7 (cache)
- ✅ Mailhog (email testing)

**Features:**
- Volume mount for live code reload
- Debug mode enabled
- Port 3307 for direct MySQL access
- Port 6379 for Redis access
- Port 8026 for Mailhog UI

**Start:**
```bash
docker-compose up -d
```

**Access:**
- Application: http://localhost
- Mailhog UI: http://localhost:8026
- MySQL: `localhost:3307`
- Redis: `localhost:6379`

---

### 2. `docker-compose.dev.yml` - Advanced Development

**Additional Features:**
- Vite HMR (Hot Module Replacement)
- Advanced debugging tools
- Custom PHP configuration

**Start:**
```bash
docker-compose -f docker-compose.dev.yml up -d
```

**Access:**
- Vite Dev Server: http://localhost:5173
- Vite HMR: http://localhost:5174

---

### 3. `docker-compose.prod.yml` - Production (Single Node)

**Optimizations:**
- ✅ Production Dockerfile (optimized build)
- ✅ No debug mode
- ✅ Resource limits (CPU/memory)
- ✅ Restart policies (`unless-stopped`)
- ✅ Health checks enabled
- ✅ Docker secrets for passwords
- ✅ Localhost-only database ports

**Environment Variables Required:**
```bash
DB_ROOT_PASSWORD=<strong-password>
DB_DATABASE=coprra
DB_USERNAME=coprra
DB_PASSWORD=<strong-password>
```

**Start:**
```bash
docker-compose -f docker-compose.prod.yml up -d
```

---

### 4. `docker/docker-compose.scale.yml` - Production (Scaled)

**Architecture:**
- ✅ Nginx Load Balancer (with SSL support)
- ✅ 3× App Instances (scalable)
- ✅ 2× Queue Workers (scalable)
- ✅ Dedicated Scheduler
- ✅ MySQL with performance tuning
- ✅ Redis with persistence
- ✅ Prometheus + Grafana (monitoring)
- ✅ Elasticsearch + Kibana (logging)
- ✅ MinIO (S3-compatible storage)
- ✅ Automated Backup Service

**Resource Allocation:**
```
Total Resources:
- CPU: ~11.5 cores
- Memory: ~15GB RAM
- Disk: 500GB recommended
```

**Start:**
```bash
cd docker
docker-compose -f docker-compose.scale.yml up -d
```

**Scale Application:**
```bash
# Scale to 5 app instances
docker-compose -f docker-compose.scale.yml up -d --scale app=5

# Scale to 10 queue workers
docker-compose -f docker-compose.scale.yml up -d --scale queue_worker=10
```

**Access:**
- Application: http://localhost
- Prometheus: http://localhost:9090
- Grafana: http://localhost:3000
- Kibana: http://localhost:5601
- MinIO Console: http://localhost:9001

---

### 5. `docker-compose.swarm.yml` - Docker Swarm

**For production clusters with multiple servers.**

**Deploy:**
```bash
# Initialize Swarm (on manager node)
docker swarm init

# Deploy stack
docker stack deploy -c docker-compose.swarm.yml coprra

# Check status
docker stack services coprra

# Scale services
docker service scale coprra_app=5
```

**Features:**
- ✅ Rolling updates
- ✅ Automatic rollback on failure
- ✅ Health-based routing
- ✅ Load balancing across nodes
- ✅ Secret management

---

## First Time Setup

### Step-by-Step Guide

#### 1. Clone Repository
```bash
git clone <repository-url>
cd coprra
```

#### 2. Create Environment File
```bash
cp .env.example .env
```

**Edit `.env` and configure:**
```bash
APP_NAME=COPRRA
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=coprra
DB_USERNAME=coprra
DB_PASSWORD=coprra

REDIS_HOST=redis
REDIS_PORT=6379

MAIL_HOST=mailhog
MAIL_PORT=1025
```

#### 3. Build Docker Images
```bash
docker-compose build
```

#### 4. Start Services
```bash
docker-compose up -d
```

#### 5. Install Dependencies (if needed)
```bash
# PHP dependencies
docker-compose exec app composer install

# Node dependencies
docker-compose exec app npm install

# Build frontend assets
docker-compose exec app npm run build
```

#### 6. Generate Application Key
```bash
docker-compose exec app php artisan key:generate
```

#### 7. Run Database Migrations
```bash
docker-compose exec app php artisan migrate
```

#### 8. Seed Database (optional)
```bash
docker-compose exec app php artisan db:seed
```

#### 9. Create Storage Link
```bash
docker-compose exec app php artisan storage:link
```

#### 10. Verify Installation
```bash
# Check container status
docker-compose ps

# Check health
curl http://localhost/api/health

# Run tests
docker-compose exec app php artisan test
```

**Expected Output:**
```
NAME                IMAGE               STATUS              PORTS
coprra-app          coprra:latest       Up (healthy)        9000/tcp
coprra-nginx        nginx:stable        Up                  0.0.0.0:80->80/tcp
coprra-mysql        mysql:8.0           Up (healthy)        0.0.0.0:3307->3306/tcp
coprra-redis        redis:7-alpine      Up (healthy)        0.0.0.0:6379->6379/tcp
coprra-mailhog      mailhog:latest      Up                  0.0.0.0:8026->8025/tcp
```

---

## Common Commands

### Container Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart a specific service
docker-compose restart app

# View logs (all services)
docker-compose logs

# View logs (specific service)
docker-compose logs -f app

# View logs (last 100 lines)
docker-compose logs --tail=100 app

# Check container status
docker-compose ps

# Execute command in container
docker-compose exec app bash

# Execute command without TTY (CI/CD)
docker-compose exec -T app php artisan migrate
```

### Laravel Artisan Commands

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Rollback migrations
docker-compose exec app php artisan migrate:rollback

# Seed database
docker-compose exec app php artisan db:seed

# Clear cache
docker-compose exec app php artisan cache:clear

# Clear config cache
docker-compose exec app php artisan config:clear

# Optimize for production
docker-compose exec app php artisan optimize

# Run queue worker
docker-compose exec app php artisan queue:work

# Run tests
docker-compose exec app php artisan test

# Generate IDE helper
docker-compose exec app php artisan ide-helper:generate
```

### Database Operations

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u coprra -p

# Create database backup
docker-compose exec mysql mysqldump -u root -p coprra > backup.sql

# Restore database backup
docker-compose exec -T mysql mysql -u root -p coprra < backup.sql

# Laravel backup
docker-compose exec app php artisan backup:run

# List backups
docker-compose exec app php artisan backup:list

# Clean old backups
docker-compose exec app php artisan backup:clean
```

### Redis Operations

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Flush cache
docker-compose exec redis redis-cli FLUSHALL

# Monitor Redis commands
docker-compose exec redis redis-cli MONITOR

# Check Redis memory usage
docker-compose exec redis redis-cli INFO memory
```

### Maintenance Commands

```bash
# Rebuild containers (clean build)
docker-compose build --no-cache

# Remove all containers and volumes (⚠️ DATA LOSS)
docker-compose down -v

# Prune unused Docker resources
docker system prune -a

# Check disk usage
docker system df

# Update images
docker-compose pull
docker-compose up -d
```

---

## Health Checks

### Application Health Endpoints

COPRRA includes comprehensive health check endpoints:

#### 1. Quick Ping
```bash
curl http://localhost/api/health/ping
```

**Response:**
```json
{
  "status": "ok",
  "timestamp": "2025-10-30T12:00:00Z"
}
```

#### 2. Comprehensive Health Check
```bash
curl http://localhost/api/health
```

**Response:**
```json
{
  "status": "healthy",
  "checks": {
    "database": "ok",
    "redis": "ok",
    "queue": "ok",
    "storage": "ok"
  },
  "timestamp": "2025-10-30T12:00:00Z"
}
```

#### 3. System Health
```bash
curl http://localhost/api/settings/system-health
```

### Docker Health Checks

All services include Docker-native health checks:

```bash
# Check health status
docker-compose ps

# Expected output:
# NAME            STATUS
# coprra-app      Up (healthy)
# coprra-mysql    Up (healthy)
# coprra-redis    Up (healthy)
```

**Health Check Configuration:**

| Service | Command | Interval | Timeout | Retries | Start Period |
|---------|---------|----------|---------|---------|--------------|
| App | `php artisan health:ping` | 30s | 5s | 3 | 60s |
| MySQL | `mysqladmin ping` | 10s | 5s | 5 | 30s |
| Redis | `redis-cli ping` | 10s | 3s | 3 | - |

---

## Scaling

### Horizontal Scaling

#### Application Instances

```bash
# Scale to 3 instances
docker-compose -f docker/docker-compose.scale.yml up -d --scale app=3

# Scale to 5 instances
docker-compose -f docker/docker-compose.scale.yml up -d --scale app=5

# Scale down to 1 instance
docker-compose -f docker/docker-compose.scale.yml up -d --scale app=1
```

#### Queue Workers

```bash
# Scale to 5 workers
docker-compose -f docker/docker-compose.scale.yml up -d --scale queue_worker=5

# Scale to 10 workers
docker-compose -f docker/docker-compose.scale.yml up -d --scale queue_worker=10
```

### Vertical Scaling (Resource Limits)

Edit `docker-compose.prod.yml`:

```yaml
app:
  deploy:
    resources:
      limits:
        cpus: '4'        # Increase CPU
        memory: 4G       # Increase memory
      reservations:
        cpus: '2'
        memory: 2G
```

Then restart:
```bash
docker-compose -f docker-compose.prod.yml up -d
```

---

## Monitoring

### Built-in Monitoring Stack

Available in `docker/docker-compose.scale.yml`:

#### Prometheus (Metrics Collection)

```bash
# Access Prometheus
open http://localhost:9090

# Query examples:
# - container_memory_usage_bytes
# - container_cpu_usage_seconds_total
# - mysql_global_status_threads_connected
```

#### Grafana (Visualization)

```bash
# Access Grafana
open http://localhost:3000

# Default credentials:
# Username: admin
# Password: Set via GRAFANA_PASSWORD env var
```

**Pre-configured Dashboards:**
- Docker Container Metrics
- MySQL Performance
- Redis Monitoring
- Application Response Times

#### Elasticsearch + Kibana (Logs)

```bash
# Access Kibana
open http://localhost:5601

# View logs:
# 1. Create index pattern: "logstash-*"
# 2. Navigate to Discover
# 3. Filter by container name
```

### Manual Monitoring

```bash
# Container stats (real-time)
docker stats

# Container resource usage
docker-compose top

# Application logs
docker-compose logs -f app

# Database slow query log
docker-compose exec mysql tail -f /var/log/mysql/slow.log

# Redis monitor
docker-compose exec redis redis-cli MONITOR
```

---

## Troubleshooting

### Common Issues

#### 1. Port Already in Use

**Problem:**
```
Error: bind: address already in use
```

**Solution:**
```bash
# Check what's using the port
sudo lsof -i :80
sudo lsof -i :3306

# Stop conflicting service or change port
# Edit docker-compose.yml:
ports:
  - "8080:80"  # Use port 8080 instead
```

#### 2. Containers Won't Start

**Problem:**
```
Container exits immediately
```

**Solution:**
```bash
# Check logs for errors
docker-compose logs app

# Try building without cache
docker-compose build --no-cache

# Check disk space
docker system df
```

#### 3. Permission Denied Errors

**Problem:**
```
Permission denied: /var/www/html/storage/logs/laravel.log
```

**Solution:**
```bash
# Fix permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

# Verify permissions
docker-compose exec app ls -la storage/
```

#### 4. Database Connection Failed

**Problem:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Solution:**
```bash
# Check if MySQL is healthy
docker-compose ps mysql

# Wait for MySQL to be ready
docker-compose exec app php artisan migrate --pretend

# Verify .env settings
cat .env | grep DB_
```

#### 5. Out of Memory

**Problem:**
```
Killed (exit code 137)
```

**Solution:**
```bash
# Increase Docker memory limit
# Docker Desktop > Settings > Resources > Memory: 8GB

# Or reduce resource allocation
# Edit docker-compose.yml and reduce memory limits
```

#### 6. Slow Performance

**Problem:**
```
Application is slow
```

**Solution:**
```bash
# Enable OPcache
docker-compose exec app php -i | grep opcache

# Clear and warm cache
docker-compose exec app php artisan optimize

# Check resource usage
docker stats

# Increase resource limits if needed
```

### Debug Mode

Enable debug mode for detailed error messages:

```bash
# Edit .env
APP_DEBUG=true
APP_LOG_LEVEL=debug

# Restart containers
docker-compose restart app

# View detailed logs
docker-compose logs -f app
```

### Clean Slate Reset

If all else fails, start fresh:

```bash
# ⚠️ WARNING: This will delete all data!

# Stop and remove everything
docker-compose down -v

# Remove images
docker-compose down --rmi all

# Clean Docker system
docker system prune -a --volumes

# Rebuild from scratch
docker-compose build --no-cache
docker-compose up -d
```

---

## Best Practices

### Development

1. ✅ **Use volume mounts** for live code reload
2. ✅ **Enable debug mode** (`APP_DEBUG=true`)
3. ✅ **Use Mailhog** for email testing
4. ✅ **Run tests frequently** (`docker-compose exec app php artisan test`)
5. ✅ **Check logs regularly** (`docker-compose logs -f app`)

### Production

1. ✅ **Disable debug mode** (`APP_DEBUG=false`)
2. ✅ **Set strong passwords** (use Docker secrets)
3. ✅ **Enable health checks** (already configured)
4. ✅ **Set resource limits** (already configured)
5. ✅ **Use restart policies** (`restart: unless-stopped`)
6. ✅ **Enable monitoring** (Prometheus + Grafana)
7. ✅ **Automate backups** (already configured)
8. ✅ **Use SSL/TLS** (configure Nginx with certificates)

### Security

1. ✅ **Run as non-root user** (already configured: `www-data`)
2. ✅ **Bind databases to localhost** (already configured)
3. ✅ **Use Docker secrets** for sensitive data
4. ✅ **Keep images updated** (`docker-compose pull`)
5. ✅ **Scan for vulnerabilities** (Trivy in CI/CD)
6. ✅ **Enable security headers** (already configured in Nginx)
7. ✅ **Rate limit APIs** (already configured)

---

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [Laravel Docker Best Practices](https://laravel.com/docs/deployment)
- [Docker Security Best Practices](https://docs.docker.com/develop/security-best-practices/)
- [Troubleshooting Guide](./DOCKER_TROUBLESHOOTING.md)

---

## Getting Help

If you encounter issues:

1. Check this documentation
2. Review [DOCKER_TROUBLESHOOTING.md](./DOCKER_TROUBLESHOOTING.md)
3. Check container logs: `docker-compose logs`
4. Verify health: `docker-compose ps`
5. Open an issue on GitHub

---

**Last Updated:** October 30, 2025
**Docker Version:** 24.0+
**Docker Compose Version:** 2.20+
