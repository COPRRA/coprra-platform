# COPRRA Deployment Guide

## Table of Contents
1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Environment Setup](#environment-setup)
4. [Deployment Strategies](#deployment-strategies)
5. [Infrastructure as Code](#infrastructure-as-code)
6. [CI/CD Pipeline](#cicd-pipeline)
7. [Security Considerations](#security-considerations)
8. [Performance Optimization](#performance-optimization)
9. [Monitoring & Logging](#monitoring--logging)
10. [Backup & Recovery](#backup--recovery)
11. [Scaling Strategies](#scaling-strategies)
12. [Troubleshooting](#troubleshooting)
13. [Best Practices](#best-practices)

## Overview

This guide provides comprehensive instructions for deploying COPRRA in production environments, covering everything from basic setup to advanced scaling strategies and security considerations.

### Deployment Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Load Balancer │────│  Web Servers    │────│   Database      │
│   (Nginx/HAProxy)│    │  (PHP-FPM)     │    │   (MySQL/Redis) │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         └──────────────│  File Storage   │──────────────┘
                        │  (S3/MinIO)     │
                        └─────────────────┘
```

## Prerequisites

### System Requirements
- **OS**: Ubuntu 20.04+ / CentOS 8+ / RHEL 8+
- **CPU**: 4+ cores (8+ recommended for production)
- **RAM**: 8GB minimum (16GB+ recommended)
- **Storage**: 100GB+ SSD storage
- **Network**: 1Gbps+ bandwidth

### Software Dependencies
- **PHP**: 8.1+ with required extensions
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache**: Redis 6.0+ or Memcached 1.6+
- **Queue**: Redis or Amazon SQS
- **Search**: Elasticsearch 7.0+ (optional)

### Required PHP Extensions
```bash
# Core extensions
php8.1-cli php8.1-fpm php8.1-mysql php8.1-redis
php8.1-curl php8.1-json php8.1-mbstring php8.1-xml
php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath

# Optional but recommended
php8.1-opcache php8.1-imagick php8.1-xdebug
```

## Environment Setup

### 1. Server Preparation

#### Ubuntu/Debian Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx mysql-server redis-server
sudo apt install -y php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis
sudo apt install -y php8.1-curl php8.1-json php8.1-mbstring php8.1-xml
sudo apt install -y php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### CentOS/RHEL Setup
```bash
# Enable EPEL and Remi repositories
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Install packages
sudo dnf module enable php:remi-8.1 -y
sudo dnf install -y nginx mysql-server redis
sudo dnf install -y php php-fpm php-mysql php-redis
sudo dnf install -y php-curl php-json php-mbstring php-xml
sudo dnf install -y php-zip php-gd php-intl php-bcmath
```

### 2. Database Configuration

#### MySQL Setup
```sql
-- Create database and user
CREATE DATABASE coprra_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'coprra_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON coprra_production.* TO 'coprra_user'@'localhost';
FLUSH PRIVILEGES;
```

#### MySQL Configuration (`/etc/mysql/mysql.conf.d/mysqld.cnf`)
```ini
[mysqld]
# Performance tuning
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Connection settings
max_connections = 200
max_connect_errors = 1000000

# Query cache
query_cache_type = 1
query_cache_size = 256M

# Logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### 3. Redis Configuration

#### Redis Setup (`/etc/redis/redis.conf`)
```ini
# Memory management
maxmemory 1gb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Security
requirepass your_redis_password
bind 127.0.0.1

# Performance
tcp-keepalive 300
timeout 0
```

### 4. Web Server Configuration

#### Nginx Configuration (`/etc/nginx/sites-available/coprra`)
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    root /var/www/coprra/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=1r/s;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API rate limiting
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Login rate limiting
    location /login {
        limit_req zone=login burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Security
        fastcgi_param HTTP_PROXY "";
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ /(vendor|storage|bootstrap/cache) {
        deny all;
        access_log off;
        log_not_found off;
    }
}
```

#### PHP-FPM Configuration (`/etc/php/8.1/fpm/pool.d/coprra.conf`)
```ini
[coprra]
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm-coprra.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000

; Performance tuning
request_terminate_timeout = 300
request_slowlog_timeout = 10
slowlog = /var/log/php8.1-fpm-slow.log

; Security
security.limit_extensions = .php
php_admin_value[disable_functions] = exec,passthru,shell_exec,system
php_admin_flag[allow_url_fopen] = off
```

## Deployment Strategies

### 1. Blue-Green Deployment

#### Setup
```bash
# Create deployment directories
sudo mkdir -p /var/www/coprra-blue
sudo mkdir -p /var/www/coprra-green
sudo ln -sf /var/www/coprra-blue /var/www/coprra
```

#### Deployment Script (`deploy.sh`)
```bash
#!/bin/bash

set -e

# Configuration
REPO_URL="https://github.com/your-org/coprra.git"
BRANCH="main"
CURRENT_LINK="/var/www/coprra"
BLUE_DIR="/var/www/coprra-blue"
GREEN_DIR="/var/www/coprra-green"

# Determine current and next environments
if [ "$(readlink $CURRENT_LINK)" = "$BLUE_DIR" ]; then
    CURRENT_ENV="blue"
    NEXT_ENV="green"
    NEXT_DIR="$GREEN_DIR"
else
    CURRENT_ENV="green"
    NEXT_ENV="blue"
    NEXT_DIR="$BLUE_DIR"
fi

echo "Deploying to $NEXT_ENV environment..."

# Clone/update code
if [ -d "$NEXT_DIR/.git" ]; then
    cd "$NEXT_DIR"
    git fetch origin
    git reset --hard "origin/$BRANCH"
else
    rm -rf "$NEXT_DIR"
    git clone -b "$BRANCH" "$REPO_URL" "$NEXT_DIR"
    cd "$NEXT_DIR"
fi

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# Build assets
npm run production

# Copy environment file
cp /var/www/shared/.env "$NEXT_DIR/.env"

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data "$NEXT_DIR"
sudo chmod -R 755 "$NEXT_DIR"
sudo chmod -R 775 "$NEXT_DIR/storage"
sudo chmod -R 775 "$NEXT_DIR/bootstrap/cache"

# Health check
if curl -f "http://localhost/health" > /dev/null 2>&1; then
    echo "Health check passed. Switching to $NEXT_ENV..."
    sudo ln -sfn "$NEXT_DIR" "$CURRENT_LINK"
    sudo systemctl reload nginx
    echo "Deployment completed successfully!"
else
    echo "Health check failed. Deployment aborted."
    exit 1
fi
```

### 2. Rolling Deployment

#### Using Ansible
```yaml
# deploy.yml
---
- hosts: web_servers
  serial: 1
  tasks:
    - name: Update code
      git:
        repo: "{{ repo_url }}"
        dest: /var/www/coprra
        version: "{{ branch | default('main') }}"
      
    - name: Install dependencies
      composer:
        command: install
        working_dir: /var/www/coprra
        no_dev: yes
        optimize_autoloader: yes
    
    - name: Build assets
      npm:
        path: /var/www/coprra
        production: yes
    
    - name: Run migrations
      command: php artisan migrate --force
      args:
        chdir: /var/www/coprra
    
    - name: Clear caches
      command: "{{ item }}"
      args:
        chdir: /var/www/coprra
      loop:
        - php artisan config:cache
        - php artisan route:cache
        - php artisan view:cache
    
    - name: Reload PHP-FPM
      systemd:
        name: php8.1-fpm
        state: reloaded
```

## Infrastructure as Code

### Terraform Configuration

#### Main Infrastructure (`main.tf`)
```hcl
# Provider configuration
terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

# VPC
resource "aws_vpc" "coprra_vpc" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name = "coprra-vpc"
  }
}

# Subnets
resource "aws_subnet" "public_subnet" {
  count             = 2
  vpc_id            = aws_vpc.coprra_vpc.id
  cidr_block        = "10.0.${count.index + 1}.0/24"
  availability_zone = data.aws_availability_zones.available.names[count.index]

  map_public_ip_on_launch = true

  tags = {
    Name = "coprra-public-subnet-${count.index + 1}"
  }
}

resource "aws_subnet" "private_subnet" {
  count             = 2
  vpc_id            = aws_vpc.coprra_vpc.id
  cidr_block        = "10.0.${count.index + 10}.0/24"
  availability_zone = data.aws_availability_zones.available.names[count.index]

  tags = {
    Name = "coprra-private-subnet-${count.index + 1}"
  }
}

# Internet Gateway
resource "aws_internet_gateway" "coprra_igw" {
  vpc_id = aws_vpc.coprra_vpc.id

  tags = {
    Name = "coprra-igw"
  }
}

# Load Balancer
resource "aws_lb" "coprra_alb" {
  name               = "coprra-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb_sg.id]
  subnets            = aws_subnet.public_subnet[*].id

  enable_deletion_protection = false

  tags = {
    Name = "coprra-alb"
  }
}

# Auto Scaling Group
resource "aws_autoscaling_group" "coprra_asg" {
  name                = "coprra-asg"
  vpc_zone_identifier = aws_subnet.private_subnet[*].id
  target_group_arns   = [aws_lb_target_group.coprra_tg.arn]
  health_check_type   = "ELB"
  min_size            = 2
  max_size            = 10
  desired_capacity    = 3

  launch_template {
    id      = aws_launch_template.coprra_lt.id
    version = "$Latest"
  }

  tag {
    key                 = "Name"
    value               = "coprra-instance"
    propagate_at_launch = true
  }
}

# RDS Database
resource "aws_db_instance" "coprra_db" {
  identifier     = "coprra-db"
  engine         = "mysql"
  engine_version = "8.0"
  instance_class = "db.t3.medium"
  
  allocated_storage     = 100
  max_allocated_storage = 1000
  storage_type          = "gp2"
  storage_encrypted     = true

  db_name  = "coprra_production"
  username = "coprra_user"
  password = var.db_password

  vpc_security_group_ids = [aws_security_group.rds_sg.id]
  db_subnet_group_name   = aws_db_subnet_group.coprra_db_subnet_group.name

  backup_retention_period = 7
  backup_window          = "03:00-04:00"
  maintenance_window     = "sun:04:00-sun:05:00"

  skip_final_snapshot = false
  final_snapshot_identifier = "coprra-db-final-snapshot"

  tags = {
    Name = "coprra-db"
  }
}

# ElastiCache Redis
resource "aws_elasticache_subnet_group" "coprra_cache_subnet_group" {
  name       = "coprra-cache-subnet-group"
  subnet_ids = aws_subnet.private_subnet[*].id
}

resource "aws_elasticache_replication_group" "coprra_redis" {
  replication_group_id       = "coprra-redis"
  description                = "Redis cluster for COPRRA"
  
  node_type                  = "cache.t3.medium"
  port                       = 6379
  parameter_group_name       = "default.redis7"
  
  num_cache_clusters         = 2
  automatic_failover_enabled = true
  multi_az_enabled          = true
  
  subnet_group_name = aws_elasticache_subnet_group.coprra_cache_subnet_group.name
  security_group_ids = [aws_security_group.redis_sg.id]

  at_rest_encryption_enabled = true
  transit_encryption_enabled = true
  auth_token                 = var.redis_auth_token

  tags = {
    Name = "coprra-redis"
  }
}
```

### Docker Configuration

#### Dockerfile
```dockerfile
FROM php:8.1-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    mysql-client \
    redis \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm ci --production && npm run production

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 775 storage bootstrap/cache

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/php.ini

# Expose port
EXPOSE 80

# Start services
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
```

#### Docker Compose (`docker-compose.prod.yml`)
```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "80:80"
      - "443:443"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=db
      - REDIS_HOST=redis
    volumes:
      - ./storage:/var/www/html/storage
      - ./ssl:/etc/ssl/certs
    depends_on:
      - db
      - redis
    restart: unless-stopped

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
      - ./docker/mysql.cnf:/etc/mysql/conf.d/custom.cnf
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    restart: unless-stopped

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/ssl/certs
    depends_on:
      - app
    restart: unless-stopped

volumes:
  db_data:
  redis_data:
```

## CI/CD Pipeline

### GitHub Actions Workflow (`.github/workflows/deploy.yml`)
```yaml
name: Deploy to Production

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: coprra_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
      
      redis:
        image: redis:7
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, redis
          coverage: xdebug
      
      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: /tmp/composer-cache
          key: ${{ runner.os }}-${{ hashFiles('**/composer.lock') }}
      
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          cache: 'npm'
      
      - name: Install npm dependencies
        run: npm ci
      
      - name: Build assets
        run: npm run production
      
      - name: Copy environment file
        run: cp .env.testing .env
      
      - name: Generate application key
        run: php artisan key:generate
      
      - name: Run migrations
        run: php artisan migrate --force
      
      - name: Run tests
        run: |
          php artisan test --coverage-clover coverage.xml
          vendor/bin/phpstan analyse
          vendor/bin/pint --test
      
      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml

  security-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Run security scan
        run: |
          composer audit
          npm audit
      
      - name: Run SAST scan
        uses: github/super-linter@v4
        env:
          DEFAULT_BRANCH: main
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}

  deploy:
    needs: [test, security-scan]
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to production
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /var/www/coprra
            git pull origin main
            composer install --no-dev --optimize-autoloader
            npm ci --production
            npm run production
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo systemctl reload nginx
            sudo systemctl reload php8.1-fpm
      
      - name: Health check
        run: |
          sleep 30
          curl -f ${{ secrets.PROD_URL }}/health || exit 1
      
      - name: Notify deployment
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          channel: '#deployments'
          webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

## Security Considerations

### 1. SSL/TLS Configuration

#### Let's Encrypt Setup
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### 2. Firewall Configuration

#### UFW Setup
```bash
# Enable UFW
sudo ufw enable

# Allow SSH
sudo ufw allow ssh

# Allow HTTP/HTTPS
sudo ufw allow 'Nginx Full'

# Allow MySQL (only from app servers)
sudo ufw allow from 10.0.0.0/16 to any port 3306

# Allow Redis (only from app servers)
sudo ufw allow from 10.0.0.0/16 to any port 6379

# Deny all other traffic
sudo ufw default deny incoming
sudo ufw default allow outgoing
```

### 3. Application Security

#### Environment Configuration
```bash
# .env.production
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-32-character-secret-key

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coprra_production
DB_USERNAME=coprra_user
DB_PASSWORD=secure_random_password

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=secure_redis_password
REDIS_PORT=6379

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

# Security
BCRYPT_ROUNDS=12
HASH_DRIVER=bcrypt

# CORS
CORS_ALLOWED_ORIGINS=https://your-domain.com
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE
CORS_ALLOWED_HEADERS=Content-Type,Authorization

# Rate limiting
THROTTLE_REQUESTS=60
THROTTLE_DECAY_MINUTES=1
```

## Performance Optimization

### 1. PHP Optimization

#### OPcache Configuration (`/etc/php/8.1/fpm/conf.d/10-opcache.ini`)
```ini
; Enable OPcache
opcache.enable=1
opcache.enable_cli=1

; Memory settings
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000

; Performance settings
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=0
opcache.fast_shutdown=1

; File cache
opcache.file_cache=/tmp/opcache
opcache.file_cache_only=0
```

### 2. Database Optimization

#### MySQL Performance Tuning
```sql
-- Enable query cache
SET GLOBAL query_cache_type = ON;
SET GLOBAL query_cache_size = 268435456;

-- Optimize InnoDB
SET GLOBAL innodb_buffer_pool_size = 2147483648;
SET GLOBAL innodb_log_file_size = 268435456;
SET GLOBAL innodb_flush_log_at_trx_commit = 2;

-- Connection optimization
SET GLOBAL max_connections = 200;
SET GLOBAL max_connect_errors = 1000000;
```

### 3. Caching Strategy

#### Redis Configuration for Caching
```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

'prefix' => env('CACHE_PREFIX', 'coprra_cache'),
```

#### Application-Level Caching
```php
// Cache frequently accessed data
Cache::remember('user_permissions_' . $userId, 3600, function () use ($userId) {
    return User::find($userId)->permissions;
});

// Cache database queries
Cache::remember('popular_projects', 1800, function () {
    return Project::popular()->limit(10)->get();
});

// Cache API responses
Cache::remember('api_stats_' . date('Y-m-d'), 86400, function () {
    return ApiStats::today()->get();
});
```

## Monitoring & Logging

### 1. Application Monitoring

#### Laravel Telescope (Development)
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

#### Production Monitoring with New Relic
```bash
# Install New Relic PHP agent
wget -O - https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
echo 'deb http://apt.newrelic.com/debian/ newrelic non-free' | sudo tee /etc/apt/sources.list.d/newrelic.list
sudo apt update
sudo apt install newrelic-php5

# Configure
sudo newrelic-install install
```

### 2. Log Management

#### Centralized Logging with ELK Stack
```yaml
# docker-compose.logging.yml
version: '3.8'

services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.5.0
    environment:
      - discovery.type=single-node
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data
    ports:
      - "9200:9200"

  logstash:
    image: docker.elastic.co/logstash/logstash:8.5.0
    volumes:
      - ./logstash.conf:/usr/share/logstash/pipeline/logstash.conf
    ports:
      - "5044:5044"
    depends_on:
      - elasticsearch

  kibana:
    image: docker.elastic.co/kibana/kibana:8.5.0
    ports:
      - "5601:5601"
    environment:
      ELASTICSEARCH_HOSTS: http://elasticsearch:9200
    depends_on:
      - elasticsearch

volumes:
  elasticsearch_data:
```

#### Logstash Configuration (`logstash.conf`)
```ruby
input {
  beats {
    port => 5044
  }
}

filter {
  if [fields][logtype] == "laravel" {
    grok {
      match => { "message" => "\[%{TIMESTAMP_ISO8601:timestamp}\] %{DATA:environment}\.%{DATA:level}: %{GREEDYDATA:message}" }
    }
    
    date {
      match => [ "timestamp", "yyyy-MM-dd HH:mm:ss" ]
    }
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "coprra-logs-%{+YYYY.MM.dd}"
  }
}
```

### 3. Health Checks

#### Application Health Check Endpoint
```php
// routes/web.php
Route::get('/health', function () {
    $checks = [
        'database' => false,
        'redis' => false,
        'storage' => false,
    ];
    
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (Exception $e) {
        Log::error('Database health check failed: ' . $e->getMessage());
    }
    
    try {
        Redis::ping();
        $checks['redis'] = true;
    } catch (Exception $e) {
        Log::error('Redis health check failed: ' . $e->getMessage());
    }
    
    try {
        Storage::disk('local')->put('health-check.txt', 'OK');
        Storage::disk('local')->delete('health-check.txt');
        $checks['storage'] = true;
    } catch (Exception $e) {
        Log::error('Storage health check failed: ' . $e->getMessage());
    }
    
    $healthy = array_reduce($checks, function ($carry, $check) {
        return $carry && $check;
    }, true);
    
    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toISOString(),
    ], $healthy ? 200 : 503);
});
```

## Backup & Recovery

### 1. Database Backup Strategy

#### Automated MySQL Backup Script
```bash
#!/bin/bash

# Configuration
DB_NAME="coprra_production"
DB_USER="backup_user"
DB_PASS="backup_password"
BACKUP_DIR="/var/backups/mysql"
S3_BUCKET="coprra-backups"
RETENTION_DAYS=30

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Generate backup filename
BACKUP_FILE="$BACKUP_DIR/coprra_$(date +%Y%m%d_%H%M%S).sql.gz"

# Create backup
mysqldump --single-transaction --routines --triggers \
  -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_FILE"

# Upload to S3
aws s3 cp "$BACKUP_FILE" "s3://$S3_BUCKET/database/"

# Clean up old local backups
find "$BACKUP_DIR" -name "coprra_*.sql.gz" -mtime +7 -delete

# Clean up old S3 backups
aws s3 ls "s3://$S3_BUCKET/database/" --recursive | \
  awk '$1 <= "'$(date -d "$RETENTION_DAYS days ago" '+%Y-%m-%d')'" {print $4}' | \
  xargs -I {} aws s3 rm "s3://$S3_BUCKET/{}"

echo "Backup completed: $BACKUP_FILE"
```

### 2. Application Backup

#### File System Backup
```bash
#!/bin/bash

# Configuration
APP_DIR="/var/www/coprra"
BACKUP_DIR="/var/backups/app"
S3_BUCKET="coprra-backups"

# Create backup
BACKUP_FILE="$BACKUP_DIR/app_$(date +%Y%m%d_%H%M%S).tar.gz"
tar -czf "$BACKUP_FILE" \
  --exclude="$APP_DIR/storage/logs/*" \
  --exclude="$APP_DIR/storage/framework/cache/*" \
  --exclude="$APP_DIR/storage/framework/sessions/*" \
  --exclude="$APP_DIR/storage/framework/views/*" \
  --exclude="$APP_DIR/node_modules" \
  --exclude="$APP_DIR/.git" \
  "$APP_DIR"

# Upload to S3
aws s3 cp "$BACKUP_FILE" "s3://$S3_BUCKET/application/"

echo "Application backup completed: $BACKUP_FILE"
```

### 3. Disaster Recovery Plan

#### Recovery Procedures
```bash
#!/bin/bash

# Database Recovery
echo "Restoring database..."
gunzip -c /path/to/backup.sql.gz | mysql -u root -p coprra_production

# Application Recovery
echo "Restoring application files..."
tar -xzf /path/to/app_backup.tar.gz -C /var/www/

# Restore permissions
chown -R www-data:www-data /var/www/coprra
chmod -R 755 /var/www/coprra
chmod -R 775 /var/www/coprra/storage
chmod -R 775 /var/www/coprra/bootstrap/cache

# Clear caches
cd /var/www/coprra
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
systemctl restart nginx
systemctl restart php8.1-fpm
systemctl restart redis-server

echo "Recovery completed!"
```

## Scaling Strategies

### 1. Horizontal Scaling

#### Load Balancer Configuration (HAProxy)
```
global
    daemon
    maxconn 4096

defaults
    mode http
    timeout connect 5000ms
    timeout client 50000ms
    timeout server 50000ms

frontend web_frontend
    bind *:80
    bind *:443 ssl crt /etc/ssl/certs/coprra.pem
    redirect scheme https if !{ ssl_fc }
    default_backend web_servers

backend web_servers
    balance roundrobin
    option httpchk GET /health
    server web1 10.0.1.10:80 check
    server web2 10.0.1.11:80 check
    server web3 10.0.1.12:80 check
```

### 2. Database Scaling

#### Read Replicas Configuration
```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [
            'mysql-read-1.example.com',
            'mysql-read-2.example.com',
        ],
    ],
    'write' => [
        'host' => [
            'mysql-write.example.com',
        ],
    ],
    'sticky' => true,
    'driver' => 'mysql',
    'database' => 'coprra_production',
    'username' => 'coprra_user',
    'password' => env('DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

### 3. Caching Scaling

#### Redis Cluster Configuration
```bash
# Redis cluster setup
redis-cli --cluster create \
  127.0.0.1:7000 127.0.0.1:7001 127.0.0.1:7002 \
  127.0.0.1:7003 127.0.0.1:7004 127.0.0.1:7005 \
  --cluster-replicas 1
```

## Troubleshooting

### Common Issues and Solutions

#### 1. High Memory Usage
```bash
# Check PHP-FPM processes
ps aux | grep php-fpm | wc -l

# Monitor memory usage
free -h
top -p $(pgrep -d',' php-fpm)

# Optimize PHP-FPM pool
# Reduce pm.max_children in pool configuration
```

#### 2. Database Connection Issues
```bash
# Check MySQL connections
mysql -e "SHOW PROCESSLIST;"

# Check connection limits
mysql -e "SHOW VARIABLES LIKE 'max_connections';"

# Monitor slow queries
tail -f /var/log/mysql/slow.log
```

#### 3. Cache Issues
```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check Redis status
redis-cli ping
redis-cli info memory
```

### Performance Debugging

#### Application Performance Profiling
```php
// Enable query logging
DB::enableQueryLog();

// Your application code here

// Get executed queries
$queries = DB::getQueryLog();
foreach ($queries as $query) {
    Log::info('Query: ' . $query['query'] . ' Time: ' . $query['time']);
}
```

## Best Practices

### 1. Security Best Practices
- **Regular Updates**: Keep all software components updated
- **Principle of Least Privilege**: Grant minimal necessary permissions
- **Input Validation**: Validate and sanitize all user inputs
- **HTTPS Everywhere**: Use SSL/TLS for all communications
- **Security Headers**: Implement proper security headers
- **Regular Audits**: Conduct regular security audits and penetration testing

### 2. Performance Best Practices
- **Caching Strategy**: Implement multi-layer caching
- **Database Optimization**: Use proper indexing and query optimization
- **Asset Optimization**: Minify and compress static assets
- **CDN Usage**: Use Content Delivery Networks for static content
- **Monitoring**: Implement comprehensive monitoring and alerting

### 3. Operational Best Practices
- **Infrastructure as Code**: Use IaC tools for reproducible deployments
- **Automated Testing**: Implement comprehensive test suites
- **Blue-Green Deployments**: Use zero-downtime deployment strategies
- **Backup Strategy**: Implement automated backup and recovery procedures
- **Documentation**: Maintain up-to-date documentation and runbooks

### 4. Monitoring Best Practices
- **Health Checks**: Implement comprehensive health check endpoints
- **Logging**: Use structured logging with proper log levels
- **Metrics Collection**: Collect and analyze key performance metrics
- **Alerting**: Set up proactive alerting for critical issues
- **Incident Response**: Have clear incident response procedures

This deployment guide provides a comprehensive foundation for deploying COPRRA in production environments. Adapt the configurations and procedures to match your specific infrastructure requirements and organizational policies.