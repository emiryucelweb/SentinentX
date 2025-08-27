# SentinentX Ubuntu 24.04 LTS Deployment Guide

**Version**: 1.0.0  
**Target OS**: Ubuntu 24.04 LTS (Noble Numbat)  
**Last Updated**: January 27, 2025  

---

## 🎯 Overview

This guide provides comprehensive instructions for deploying SentinentX AI Trading Bot on Ubuntu 24.04 LTS with production-grade configuration, security hardening, and performance optimization.

## 📋 Prerequisites

### System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| **OS** | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS Server |
| **CPU** | 2 cores | 4+ cores |
| **RAM** | 4 GB | 8+ GB |
| **Storage** | 20 GB SSD | 50+ GB NVMe SSD |
| **Network** | 100 Mbps | 1 Gbps |

### Software Versions

| Software | Version | Purpose |
|----------|---------|---------|
| **PHP** | 8.3+ | Application runtime |
| **PostgreSQL** | 16+ | Primary database |
| **Redis** | 7+ | Cache & queues |
| **Nginx** | 1.24+ | Web server |
| **Node.js** | 20+ | Frontend assets |
| **Composer** | 2.6+ | PHP dependencies |

### Security Requirements

- Root or sudo access
- SSH key-based authentication
- Firewall configured (UFW)
- SSL certificates (recommended)
- Regular security updates

---

## 🚀 Quick Installation

### Option 1: Automated Installation (Recommended)

```bash
# Download and run the automated installer
curl -fsSL https://raw.githubusercontent.com/sentinentx/sentinentx/main/deploy/ubuntu24/install.sh | sudo bash

# Or download first and review
wget https://raw.githubusercontent.com/sentinentx/sentinentx/main/deploy/ubuntu24/install.sh
chmod +x install.sh
sudo ./install.sh
```

### Option 2: Manual Step-by-Step Installation

See [Manual Installation](#manual-installation) section below.

---

## 🔧 Manual Installation

### Step 1: System Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y software-properties-common apt-transport-https ca-certificates curl wget gnupg lsb-release unzip git htop tree jq bc sqlite3 zip supervisor ufw fail2ban

# Configure timezone (optional)
sudo timedatectl set-timezone UTC
```

### Step 2: Install PHP 8.3

```bash
# Add Ondrej's PHP repository
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-pgsql php8.3-redis php8.3-xml php8.3-mbstring php8.3-curl php8.3-json php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-soap php8.3-sqlite3 php8.3-opcache php8.3-readline php8.3-tokenizer php8.3-fileinfo php8.3-ctype php8.3-dom php8.3-simplexml php8.3-xmlwriter php8.3-xmlreader

# Configure PHP for production
sudo nano /etc/php/8.3/fpm/php.ini
```

**Key PHP settings for production:**
```ini
memory_limit = 512M
max_execution_time = 300
post_max_size = 50M
upload_max_filesize = 50M
expose_php = Off
display_errors = Off
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 32531
opcache.validate_timestamps = 0
```

### Step 3: Install Composer

```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Verify installation
composer --version
```

### Step 4: Install Node.js 20

```bash
# Add NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -

# Install Node.js
sudo apt install -y nodejs

# Install global packages
sudo npm install -g npm@latest pm2
```

### Step 5: Install PostgreSQL 16

```bash
# Add PostgreSQL official repository
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
echo "deb http://apt.postgresql.org/pub/repos/apt/ $(lsb_release -cs)-pgdg main" | sudo tee /etc/apt/sources.list.d/pgdg.list

# Install PostgreSQL
sudo apt update
sudo apt install -y postgresql-16 postgresql-client-16 postgresql-contrib-16

# Create database and user
sudo -u postgres createdb sentinentx
sudo -u postgres psql -c "CREATE USER sentinentx WITH PASSWORD 'sentinentx_secure_password';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentinentx TO sentinentx;"
sudo -u postgres psql -c "ALTER USER sentinentx CREATEDB;"
```

### Step 6: Install Redis 7

```bash
# Install Redis
sudo apt install -y redis-server

# Configure Redis for production
sudo nano /etc/redis/redis.conf
```

**Key Redis settings:**
```conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save ""  # Disable persistence for performance
tcp-keepalive 300
timeout 300
```

### Step 7: Install Nginx

```bash
# Install Nginx
sudo apt install -y nginx

# Remove default site
sudo rm -f /etc/nginx/sites-enabled/default

# Create SentinentX configuration
sudo nano /etc/nginx/sites-available/sentinentx
```

**Nginx configuration example:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/sentinentx/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.env {
        deny all;
    }
}
```

### Step 8: Deploy Application

```bash
# Create application directory
sudo mkdir -p /var/www/sentinentx
cd /var/www/sentinentx

# Clone application (replace with your repository)
sudo git clone https://github.com/your-org/sentinentx.git .

# Set ownership
sudo chown -R www-data:www-data /var/www/sentinentx

# Set permissions
sudo find /var/www/sentinentx -type f -exec chmod 644 {} \;
sudo find /var/www/sentinentx -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install Node dependencies (if needed)
sudo -u www-data npm ci --production
sudo -u www-data npm run build

# Copy environment configuration
sudo cp deploy/ubuntu24/config.template.env .env
sudo chown www-data:www-data .env
sudo chmod 600 .env

# Generate application key
sudo -u www-data php artisan key:generate

# Cache configuration
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Run migrations
sudo -u www-data php artisan migrate --force
```

### Step 9: Configure systemd Services

```bash
# Copy service files
sudo cp deploy/ubuntu24/*.service /etc/systemd/system/

# Make scripts executable
sudo chmod +x deploy/ubuntu24/scripts/*.sh

# Create required directories
sudo mkdir -p /run/sentinentx /var/log/sentinentx
sudo chown www-data:www-data /run/sentinentx /var/log/sentinentx

# Reload systemd and enable services
sudo systemctl daemon-reload
sudo systemctl enable sentinentx.service
sudo systemctl enable sentinentx-scheduler.service
sudo systemctl enable sentinentx-queue.service
sudo systemctl enable sentinentx-ws.service
```

### Step 10: Configure Firewall

```bash
# Reset and configure UFW
sudo ufw --force reset
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow essential services
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 6001/tcp  # WebSocket

# Enable firewall
sudo ufw --force enable
```

---

## ⚙️ Configuration

### Environment Configuration

Edit `/var/www/sentinentx/.env`:

```bash
sudo nano /var/www/sentinentx/.env
```

**Critical settings to configure:**

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_PASSWORD=your-secure-password

# AI Providers
OPENAI_API_KEY=sk-your-key-here
GEMINI_API_KEY=your-gemini-key
GROK_API_KEY=your-grok-key

# Trading
BYBIT_API_KEY=your-bybit-key
BYBIT_API_SECRET=your-bybit-secret
BYBIT_TESTNET=true  # Set to false for mainnet

# Telegram
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-chat-id

# Security
ALLOWED_SYMBOLS=BTCUSDT,ETHUSDT,SOLUSDT,XRPUSDT
HMAC_SECRET_KEY=your-hmac-secret
```

### SSL Configuration (Recommended)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 🔄 Service Management

### Starting Services

```bash
# Start all services
sudo systemctl start sentinentx

# Start individual services
sudo systemctl start sentinentx-scheduler
sudo systemctl start sentinentx-queue
sudo systemctl start sentinentx-ws
```

### Checking Status

```bash
# Check all services
sudo systemctl status sentinentx*

# Check individual service
sudo systemctl status sentinentx

# View logs
sudo journalctl -f -u sentinentx
sudo journalctl -f -u sentinentx-queue
```

### Stopping Services

```bash
# Stop all services
sudo systemctl stop sentinentx

# Stop individual services
sudo systemctl stop sentinentx-scheduler
sudo systemctl stop sentinentx-queue
sudo systemctl stop sentinentx-ws
```

### Restarting Services

```bash
# Restart all services
sudo systemctl restart sentinentx

# Restart after configuration changes
sudo systemctl daemon-reload
sudo systemctl restart sentinentx*
```

---

## 📊 Monitoring & Maintenance

### Log Locations

| Service | Log Location |
|---------|--------------|
| **Application** | `/var/www/sentinentx/storage/logs/` |
| **Nginx** | `/var/log/nginx/` |
| **PHP-FPM** | `/var/log/php8.3-fpm.log` |
| **PostgreSQL** | `/var/log/postgresql/` |
| **Redis** | `/var/log/redis/` |
| **systemd** | `journalctl -u service-name` |

### Health Checks

```bash
# Application health check
curl http://localhost/api/health

# Service status
sudo systemctl is-active sentinentx*

# Resource usage
htop
df -h
free -h

# Database status
sudo -u postgres psql -c "SELECT version();"

# Redis status
redis-cli ping
```

### Performance Monitoring

```bash
# PHP-FPM status
curl http://localhost/fpm-status

# Nginx status
curl http://localhost/nginx_status

# Queue monitoring
cd /var/www/sentinentx
php artisan queue:monitor

# Database performance
sudo -u postgres psql sentinentx -c "SELECT * FROM pg_stat_activity;"
```

### Backup Procedures

```bash
# Database backup
sudo -u postgres pg_dump sentinentx > /backup/sentinentx_$(date +%Y%m%d_%H%M%S).sql

# Application backup
sudo tar -czf /backup/sentinentx_app_$(date +%Y%m%d_%H%M%S).tar.gz -C /var/www sentinentx

# Configuration backup
sudo cp /var/www/sentinentx/.env /backup/env_$(date +%Y%m%d_%H%M%S).backup
```

---

## 🔒 Security Hardening

### System Security

```bash
# Update packages regularly
sudo apt update && sudo apt upgrade -y

# Configure fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Disable root SSH login
sudo nano /etc/ssh/sshd_config
# Set: PermitRootLogin no
sudo systemctl restart ssh

# Configure automatic security updates
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

### Application Security

```bash
# Set secure permissions
sudo find /var/www/sentinentx -type f -exec chmod 644 {} \;
sudo find /var/www/sentinentx -type d -exec chmod 755 {} \;
sudo chmod 600 /var/www/sentinentx/.env
sudo chmod -R 775 /var/www/sentinentx/storage
sudo chmod -R 775 /var/www/sentinentx/bootstrap/cache

# Secure sensitive files
echo "deny from all" | sudo tee /var/www/sentinentx/.htaccess
```

### Database Security

```bash
# Secure PostgreSQL installation
sudo -u postgres psql -c "ALTER USER postgres PASSWORD 'secure-postgres-password';"

# Configure pg_hba.conf for security
sudo nano /etc/postgresql/16/main/pg_hba.conf
# Use md5 authentication for local connections
```

---

## 🐛 Troubleshooting

### Common Issues

#### Service Won't Start

```bash
# Check service status
sudo systemctl status sentinentx

# Check logs
sudo journalctl -u sentinentx --since "10 minutes ago"

# Check application logs
tail -f /var/www/sentinentx/storage/logs/laravel.log

# Verify configuration
cd /var/www/sentinentx
php artisan config:check
```

#### Database Connection Issues

```bash
# Test database connection
cd /var/www/sentinentx
php artisan tinker
# In tinker: DB::connection()->getPdo();

# Check PostgreSQL status
sudo systemctl status postgresql

# Check database permissions
sudo -u postgres psql -c "\du"
```

#### Permission Issues

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/sentinentx

# Fix permissions
sudo chmod -R 775 /var/www/sentinentx/storage
sudo chmod -R 775 /var/www/sentinentx/bootstrap/cache
sudo chmod 600 /var/www/sentinentx/.env
```

#### Performance Issues

```bash
# Check resource usage
htop
iostat -x 1

# Check PHP-FPM processes
sudo systemctl status php8.3-fpm

# Optimize OPcache
sudo nano /etc/php/8.3/fpm/php.ini
# Increase opcache.memory_consumption

# Clear caches
cd /var/www/sentinentx
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📈 Performance Optimization

### PHP Optimization

```bash
# Increase PHP-FPM workers
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### PostgreSQL Optimization

```bash
sudo nano /etc/postgresql/16/main/postgresql.conf
```

```conf
# Memory
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB

# Checkpoints
checkpoint_completion_target = 0.9
wal_buffers = 16MB

# Planner
random_page_cost = 1.1
effective_io_concurrency = 200
```

### Nginx Optimization

```bash
sudo nano /etc/nginx/nginx.conf
```

```nginx
worker_processes auto;
worker_connections 1024;

# Gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

# Cache static files
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

---

## 🔄 Update Procedures

### Application Updates

```bash
# Stop services
sudo systemctl stop sentinentx

# Backup current version
sudo cp -r /var/www/sentinentx /backup/sentinentx_$(date +%Y%m%d_%H%M%S)

# Pull updates
cd /var/www/sentinentx
sudo -u www-data git pull origin main

# Update dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Run migrations
sudo -u www-data php artisan migrate --force

# Clear and rebuild cache
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart services
sudo systemctl start sentinentx
```

### System Updates

```bash
# Update packages
sudo apt update && sudo apt upgrade -y

# Update PHP
sudo apt install php8.3*

# Restart services after updates
sudo systemctl restart php8.3-fpm nginx postgresql redis-server
sudo systemctl restart sentinentx*
```

---

## 📞 Support

### Getting Help

- **Documentation**: Check this guide and application documentation
- **Logs**: Always check logs first (`journalctl -u sentinentx`)
- **Community**: GitHub Issues, Discord, or community forums
- **Professional Support**: Contact enterprise support team

### Emergency Procedures

```bash
# Emergency stop all services
sudo systemctl stop sentinentx*

# Emergency restart
sudo systemctl restart sentinentx*

# Emergency database backup
sudo -u postgres pg_dump sentinentx > /tmp/emergency_backup_$(date +%Y%m%d_%H%M%S).sql

# Rollback to previous version
sudo systemctl stop sentinentx
sudo rm -rf /var/www/sentinentx
sudo mv /backup/sentinentx_YYYYMMDD_HHMMSS /var/www/sentinentx
sudo systemctl start sentinentx
```

---

## 📋 Checklist

### Pre-deployment

- [ ] Server meets minimum requirements
- [ ] Ubuntu 24.04 LTS installed and updated
- [ ] SSH access configured
- [ ] Domain name configured (if applicable)
- [ ] SSL certificates ready (if applicable)

### Deployment

- [ ] All dependencies installed
- [ ] Application deployed and configured
- [ ] Environment file configured
- [ ] Database created and migrated
- [ ] Services enabled and started
- [ ] Firewall configured
- [ ] SSL configured (if applicable)

### Post-deployment

- [ ] All services running
- [ ] Health checks passing
- [ ] Logs being generated
- [ ] Monitoring configured
- [ ] Backups configured
- [ ] Documentation updated

---

**Deployment completed successfully! 🎉**
