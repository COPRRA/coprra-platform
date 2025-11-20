# 🔧 Docker Troubleshooting Guide

## Table of Contents
- [Quick Diagnostics](#quick-diagnostics)
- [Container Issues](#container-issues)
- [Networking Issues](#networking-issues)
- [Database Issues](#database-issues)
- [Performance Issues](#performance-issues)
- [Storage Issues](#storage-issues)
- [Build Issues](#build-issues)
- [Security Issues](#security-issues)
- [Emergency Procedures](#emergency-procedures)

---

## Quick Diagnostics

### Health Check Commands

Run these commands first to diagnose issues:

```bash
# 1. Check container status
docker-compose ps

# 2. Check container health
docker-compose ps | grep -E "(healthy|unhealthy)"

# 3. Check recent logs
docker-compose logs --tail=50

# 4. Check disk space
docker system df

# 5. Check Docker version
docker --version
docker-compose --version

# 6. Check resource usage
docker stats --no-stream
```

### Quick Fixes

Try these in order:

```bash
# 1. Restart containers
docker-compose restart

# 2. Recreate containers
docker-compose up -d --force-recreate

# 3. Rebuild and restart
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# 4. Clean slate (⚠️ destroys data)
docker-compose down -v
docker system prune -a
docker-compose up -d
```

---

## Container Issues

### Issue: Container Exits Immediately

**Symptoms:**
```
coprra-app exited with code 1
```

**Diagnosis:**
```bash
# Check logs for errors
docker-compose logs app

# Check last exit code
docker-compose ps app

# Inspect container
docker inspect coprra-app
```

**Common Causes & Solutions:**

#### 1. Missing Environment Variables

**Error:**
```
RuntimeException: No application encryption key has been specified.
```

**Solution:**
```bash
# Generate application key
docker-compose exec app php artisan key:generate

# Or manually set in .env:
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXX
```

#### 2. Port Already in Use

**Error:**
```
bind: address already in use
```

**Solution:**
```bash
# Find process using port
sudo lsof -i :80
sudo lsof -i :3307

# Kill process or change port
# Edit docker-compose.yml:
ports:
  - "8080:80"
```

#### 3. Missing Dependencies

**Error:**
```
Class 'Vendor\Package\Class' not found
```

**Solution:**
```bash
# Install dependencies
docker-compose exec app composer install

# Rebuild container
docker-compose build app
docker-compose up -d
```

---

### Issue: Container is Unhealthy

**Symptoms:**
```
NAME         STATUS
coprra-app   Up (unhealthy)
```

**Diagnosis:**
```bash
# Check health check logs
docker inspect coprra-app | jq '.[0].State.Health'

# View health check output
docker inspect coprra-app | jq '.[0].State.Health.Log[-1]'
```

**Solutions:**

#### 1. Health Endpoint Not Responding

```bash
# Check if Laravel is running
docker-compose exec app ps aux | grep php

# Manually test health endpoint
docker-compose exec app php artisan health:ping

# Check logs for PHP errors
docker-compose logs app | grep -i error
```

#### 2. Increase Health Check Timeout

**Edit `docker-compose.yml`:**
```yaml
healthcheck:
  timeout: 10s        # Increase from 5s
  start_period: 120s  # Increase from 60s
```

#### 3. Database Not Ready

```bash
# Check if database is healthy
docker-compose ps mysql

# Wait for database
docker-compose exec app php artisan migrate:status
```

---

### Issue: Container Keeps Restarting

**Symptoms:**
```
coprra-app   Restarting (1) 5 seconds ago
```

**Diagnosis:**
```bash
# Check logs
docker-compose logs app

# Check restart count
docker inspect coprra-app | jq '.[0].RestartCount'

# Check OOM killer
dmesg | grep -i "out of memory"
```

**Solutions:**

#### 1. Out of Memory

```bash
# Check memory usage
docker stats --no-stream coprra-app

# Increase memory limit in docker-compose.yml:
deploy:
  resources:
    limits:
      memory: 2G  # Increase

# Or increase Docker Desktop memory:
# Docker Desktop > Settings > Resources > Memory: 8GB
```

#### 2. Application Error

```bash
# Check application logs
docker-compose logs app | tail -100

# Check PHP error log
docker-compose exec app tail -100 /var/www/html/storage/logs/laravel.log
```

#### 3. Configuration Error

```bash
# Validate configuration
docker-compose config

# Check .env file
docker-compose exec app cat .env

# Clear cache
docker-compose exec app php artisan config:clear
```

---

## Networking Issues

### Issue: Cannot Connect to Application

**Symptoms:**
```
curl: (7) Failed to connect to localhost port 80
```

**Diagnosis:**
```bash
# Check if Nginx is running
docker-compose ps nginx

# Check Nginx logs
docker-compose logs nginx

# Check port binding
docker-compose port nginx 80

# Test from inside network
docker-compose exec app curl http://nginx
```

**Solutions:**

#### 1. Nginx Not Running

```bash
# Restart Nginx
docker-compose restart nginx

# Check Nginx configuration
docker-compose exec nginx nginx -t
```

#### 2. Firewall Blocking

```bash
# Check firewall (Linux)
sudo ufw status
sudo ufw allow 80/tcp

# Check firewall (macOS)
sudo pfctl -sr | grep 80
```

#### 3. Wrong Port

```bash
# Check actual port
docker-compose ps

# Access on correct port
curl http://localhost:8080  # if mapped to 8080
```

---

### Issue: Cannot Connect to Database

**Symptoms:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Diagnosis:**
```bash
# Check if MySQL is running
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Check MySQL health
docker-compose exec mysql mysqladmin ping -h localhost
```

**Solutions:**

#### 1. MySQL Not Ready

```bash
# Wait for MySQL to be healthy
docker-compose ps mysql

# Check if ready
docker-compose exec mysql mysql -u root -p -e "SELECT 1"

# Increase start_period in healthcheck:
start_period: 60s  # Give more time
```

#### 2. Wrong Credentials

```bash
# Check .env file
cat .env | grep DB_

# Should match docker-compose.yml:
DB_HOST=mysql      # Service name, not localhost
DB_PORT=3306       # Internal port
DB_DATABASE=coprra
DB_USERNAME=coprra
DB_PASSWORD=coprra
```

#### 3. Network Issues

```bash
# Check if containers are on same network
docker network inspect coprra-net

# Restart containers
docker-compose down
docker-compose up -d
```

---

### Issue: Cannot Connect to Redis

**Symptoms:**
```
Connection refused: redis:6379
```

**Solutions:**

```bash
# Check Redis status
docker-compose ps redis

# Test Redis connection
docker-compose exec redis redis-cli ping

# Check if app can reach Redis
docker-compose exec app redis-cli -h redis ping

# Verify .env settings
REDIS_HOST=redis  # Not localhost
REDIS_PORT=6379
```

---

## Database Issues

### Issue: Database Connection Pool Exhausted

**Symptoms:**
```
Too many connections
```

**Diagnosis:**
```bash
# Check current connections
docker-compose exec mysql mysql -u root -p -e "SHOW PROCESSLIST"

# Check max connections
docker-compose exec mysql mysql -u root -p -e "SHOW VARIABLES LIKE 'max_connections'"
```

**Solutions:**

#### 1. Increase Max Connections

**Create/edit `docker/mysql/my.cnf`:**
```ini
[mysqld]
max_connections=200  # Increase from 100
```

**Restart MySQL:**
```bash
docker-compose restart mysql
```

#### 2. Fix Connection Leaks

```bash
# Check for long-running queries
docker-compose exec mysql mysql -u root -p -e "SHOW PROCESSLIST WHERE Time > 60"

# Kill stuck connection
docker-compose exec mysql mysql -u root -p -e "KILL <process_id>"
```

---

### Issue: Slow Database Queries

**Diagnosis:**
```bash
# Check slow query log
docker-compose exec mysql tail -100 /var/log/mysql/slow.log

# Check current queries
docker-compose exec mysql mysql -u root -p -e "SHOW FULL PROCESSLIST"
```

**Solutions:**

#### 1. Enable Query Caching

```bash
# Clear application cache
docker-compose exec app php artisan cache:clear

# Warm cache
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
```

#### 2. Optimize Database

```bash
# Analyze tables
docker-compose exec mysql mysqlcheck -u root -p --analyze --all-databases

# Optimize tables
docker-compose exec mysql mysqlcheck -u root -p --optimize --all-databases
```

#### 3. Increase Buffer Pool

**Edit `docker/mysql/my.cnf`:**
```ini
[mysqld]
innodb_buffer_pool_size=512M  # Increase from 256M
```

---

### Issue: Database Migrations Fail

**Symptoms:**
```
SQLSTATE[42000]: Syntax error or access violation
```

**Solutions:**

```bash
# Check migration status
docker-compose exec app php artisan migrate:status

# Reset database (⚠️ destroys data)
docker-compose exec app php artisan migrate:fresh

# Rollback one step
docker-compose exec app php artisan migrate:rollback --step=1

# Try migrating again
docker-compose exec app php artisan migrate

# Check database user permissions
docker-compose exec mysql mysql -u root -p -e "SHOW GRANTS FOR 'coprra'@'%'"
```

---

## Performance Issues

### Issue: Application is Slow

**Diagnosis:**
```bash
# Check resource usage
docker stats

# Check CPU usage
docker stats --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"

# Check logs for slow queries
docker-compose logs app | grep -i "slow"

# Check response times
time curl http://localhost/api/health
```

**Solutions:**

#### 1. Enable OPcache

```bash
# Check if OPcache is enabled
docker-compose exec app php -i | grep opcache.enable

# Verify OPcache configuration
docker-compose exec app php -i | grep opcache

# Clear OPcache
docker-compose exec app php artisan optimize:clear
```

#### 2. Optimize Caches

```bash
# Clear all caches
docker-compose exec app php artisan optimize:clear

# Rebuild caches
docker-compose exec app php artisan optimize

# Warm up caches
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

#### 3. Increase Resources

**Edit `docker-compose.yml`:**
```yaml
app:
  deploy:
    resources:
      limits:
        cpus: '2'     # Increase
        memory: 2G    # Increase
```

**Restart:**
```bash
docker-compose up -d
```

---

### Issue: High Memory Usage

**Diagnosis:**
```bash
# Check memory usage
docker stats --no-stream

# Check processes inside container
docker-compose exec app ps aux --sort=-%mem | head -10

# Check PHP memory limit
docker-compose exec app php -i | grep memory_limit
```

**Solutions:**

#### 1. Increase PHP Memory Limit

**Edit `docker/php.ini` or `custom-php.ini`:**
```ini
memory_limit = 512M  # Increase if needed
```

**Rebuild:**
```bash
docker-compose build app
docker-compose up -d
```

#### 2. Fix Memory Leaks

```bash
# Restart PHP-FPM periodically
docker-compose restart app

# Check for long-running processes
docker-compose exec app ps aux | grep php

# Reduce max requests per worker
# Edit php-fpm pool config:
pm.max_requests = 500
```

---

### Issue: High CPU Usage

**Diagnosis:**
```bash
# Identify high CPU containers
docker stats --format "table {{.Container}}\t{{.CPUPerc}}" | sort -k2 -r

# Check processes
docker-compose exec app top

# Check queue workers
docker-compose exec app ps aux | grep queue
```

**Solutions:**

```bash
# Restart queue workers
docker-compose exec app php artisan queue:restart

# Reduce queue worker count
# Edit docker-compose.yml and reduce instances

# Check for infinite loops in logs
docker-compose logs app | grep -i "loop\|infinite\|recursion"
```

---

## Storage Issues

### Issue: Disk Space Full

**Diagnosis:**
```bash
# Check Docker disk usage
docker system df

# Check volume sizes
docker system df -v

# Check host disk space
df -h
```

**Solutions:**

#### 1. Clean Docker Resources

```bash
# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune

# Remove unused containers
docker container prune

# Remove everything (⚠️ careful!)
docker system prune -a --volumes
```

#### 2. Clean Application Logs

```bash
# Clear Laravel logs
docker-compose exec app php artisan log:clear

# Or manually
docker-compose exec app truncate -s 0 storage/logs/laravel.log

# Clean old logs
docker-compose exec app find storage/logs -name "*.log" -mtime +30 -delete
```

#### 3. Rotate Logs

**Configure in `docker-compose.yml`:**
```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

---

### Issue: Permission Denied

**Symptoms:**
```
failed to open stream: Permission denied
storage/logs/laravel.log
```

**Solutions:**

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

# Fix permissions with proper modes
docker-compose exec app chmod -R 775 storage bootstrap/cache

# Verify permissions
docker-compose exec app ls -la storage/

# Check user
docker-compose exec app whoami
# Should be: www-data
```

---

## Build Issues

### Issue: Build Fails

**Symptoms:**
```
ERROR [internal] load metadata for docker.io/library/php:8.4-fpm
```

**Solutions:**

#### 1. Network Issues

```bash
# Check Docker Hub connectivity
ping registry-1.docker.io

# Use different registry (if in China)
# Add to daemon.json:
"registry-mirrors": ["https://registry.docker-cn.com"]

# Restart Docker
sudo systemctl restart docker
```

#### 2. Cache Issues

```bash
# Build without cache
docker-compose build --no-cache

# Pull latest base image
docker pull php:8.4-fpm
```

#### 3. Dockerfile Errors

```bash
# Validate Dockerfile
docker run --rm -i hadolint/hadolint < Dockerfile

# Check syntax
docker-compose config
```

---

### Issue: Build is Slow

**Solutions:**

```bash
# Enable BuildKit
export DOCKER_BUILDKIT=1

# Use layer caching
docker-compose build

# Build specific service
docker-compose build app

# Parallelize builds
docker-compose build --parallel
```

---

## Security Issues

### Issue: Container Running as Root

**Diagnosis:**
```bash
# Check user
docker-compose exec app whoami

# Should be: www-data (not root)
```

**Solution:**

Already fixed in `Dockerfile`:
```dockerfile
USER www-data
```

If still running as root, rebuild:
```bash
docker-compose build --no-cache app
docker-compose up -d
```

---

### Issue: Secrets in Logs

**Diagnosis:**
```bash
# Search logs for secrets
docker-compose logs | grep -i "password\|secret\|key"
```

**Solutions:**

```bash
# Never log secrets
# Use Docker secrets for production

# Create secrets directory
mkdir -p secrets
echo "strong_password" > secrets/db_password.txt

# Reference in docker-compose.prod.yml:
secrets:
  db_password:
    file: ./secrets/db_password.txt
```

---

## Emergency Procedures

### Procedure 1: Immediate Rollback

```bash
# 1. Stop current version
docker-compose down

# 2. Restore previous image
docker tag coprra:backup coprra:latest

# 3. Start containers
docker-compose up -d

# 4. Verify health
docker-compose ps
curl http://localhost/api/health
```

---

### Procedure 2: Database Corruption Recovery

```bash
# 1. Stop application
docker-compose stop app

# 2. Backup current database
docker-compose exec mysql mysqldump -u root -p --all-databases > emergency_backup.sql

# 3. Stop database
docker-compose stop mysql

# 4. Remove corrupted data
docker volume rm coprra_mysql_data

# 5. Start fresh database
docker-compose up -d mysql

# 6. Wait for MySQL to be ready
docker-compose exec mysql mysqladmin ping

# 7. Restore from backup
docker-compose exec -T mysql mysql -u root -p < latest_backup.sql

# 8. Start application
docker-compose up -d app

# 9. Run migrations if needed
docker-compose exec app php artisan migrate --force
```

---

### Procedure 3: Complete System Reset

**⚠️ WARNING: This will destroy ALL data!**

```bash
# 1. Stop all services
docker-compose down

# 2. Remove all volumes
docker-compose down -v

# 3. Remove all images
docker-compose down --rmi all

# 4. Clean system
docker system prune -a --volumes

# 5. Rebuild from scratch
docker-compose build --no-cache

# 6. Start fresh
docker-compose up -d

# 7. Restore from backup
docker-compose exec -T mysql mysql -u root -p < backup.sql

# 8. Run migrations
docker-compose exec app php artisan migrate --force

# 9. Verify
docker-compose ps
curl http://localhost/api/health
```

---

## Diagnostic Script

Save this as `scripts/diagnose.sh`:

```bash
#!/bin/bash
set -e

echo "🔍 COPRRA Docker Diagnostics"
echo "=============================="
echo ""

echo "1. Docker Version:"
docker --version
docker-compose --version
echo ""

echo "2. Container Status:"
docker-compose ps
echo ""

echo "3. Health Checks:"
docker-compose ps | grep -E "(healthy|unhealthy)"
echo ""

echo "4. Disk Usage:"
docker system df
echo ""

echo "5. Resource Usage:"
docker stats --no-stream
echo ""

echo "6. Recent Errors (last 50 lines):"
docker-compose logs --tail=50 | grep -i "error\|fatal\|exception" || echo "No errors found"
echo ""

echo "7. Network Connectivity:"
docker-compose exec -T app curl -s http://nginx > /dev/null && echo "✅ Nginx: OK" || echo "❌ Nginx: FAIL"
docker-compose exec -T app php artisan health:ping > /dev/null && echo "✅ App: OK" || echo "❌ App: FAIL"
docker-compose exec -T mysql mysqladmin ping -h localhost > /dev/null 2>&1 && echo "✅ MySQL: OK" || echo "❌ MySQL: FAIL"
docker-compose exec -T redis redis-cli ping > /dev/null && echo "✅ Redis: OK" || echo "❌ Redis: FAIL"
echo ""

echo "8. Storage Permissions:"
docker-compose exec app ls -ld storage/ bootstrap/cache/
echo ""

echo "✅ Diagnostic complete!"
```

**Run:**
```bash
chmod +x scripts/diagnose.sh
./scripts/diagnose.sh
```

---

## Getting Help

If issues persist:

1. ✅ Run diagnostic script: `./scripts/diagnose.sh`
2. ✅ Check logs: `docker-compose logs`
3. ✅ Review [DOCKER_SETUP.md](./DOCKER_SETUP.md)
4. ✅ Search GitHub issues
5. ✅ Open new issue with diagnostic output

---

**Last Updated:** October 30, 2025
**Maintainer:** COPRRA Team
