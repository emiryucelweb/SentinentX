# SentinentX VDS Deployment Guide - Production Ready

## 🚀 VDS Tavsiyeleri (15 Günlük Test İçin)

### 🏆 **En İyi Seçenekler**

#### 1. **DigitalOcean Droplets** ⭐⭐⭐⭐⭐
- **Tavsiye Edilen Plan**: CPU-Optimized (4 vCPUs, 8GB RAM, 100GB SSD)
- **Fiyat**: ~$48/ay (15 günlük test: ~$24)
- **Avantajlar**:
  - Çok hızlı SSD storage
  - 1-click PostgreSQL + Redis kurulumu
  - Excellent network performance
  - SSH key management
  - Load balancer ready
- **Lokasyon**: Frankfurt (Avrupa) veya Singapore (Asya)
- **Setup Link**: https://digitalocean.com

#### 2. **Linode (Akamai)** ⭐⭐⭐⭐⭐
- **Tavsiye Edilen Plan**: Dedicated CPU (4 Core, 8GB RAM, 160GB SSD)
- **Fiyat**: ~$36/ay (15 günlük test: ~$18)
- **Avantajlar**:
  - En hızlı network (40Gbps)
  - Predictable performance
  - Excellent API connectivity
  - SSD storage
- **Lokasyon**: Frankfurt/London (düşük latency)
- **Setup Link**: https://linode.com

#### 3. **Vultr High Frequency** ⭐⭐⭐⭐
- **Tavsiye Edilen Plan**: High Frequency (4 vCPU, 8GB RAM, 128GB NVMe)
- **Fiyat**: ~$32/ay (15 günlük test: ~$16)
- **Avantajlar**:
  - NVMe SSD (çok hızlı)
  - Global network
  - Competitive pricing
- **Lokasyon**: Amsterdam/Frankfurt

#### 4. **Hetzner Cloud** ⭐⭐⭐⭐
- **Tavsiye Edilen Plan**: CPX31 (4 vCPU, 8GB RAM, 160GB SSD)
- **Fiyat**: ~€13/ay (15 günlük test: ~€6.5)
- **Avantajlar**:
  - En ucuz fiyat
  - Avrupa'da mükemmel performance
  - Dedicated resources
- **Dezavantaj**: Sadece Avrupa lokasyonları

### 🎯 **Önerilen Seçim: DigitalOcean** 

**Neden DigitalOcean?**
- Trading bot'lar için optimize edilmiş network
- 1-click database kurulumu
- Excellent monitoring tools
- Stable performance
- Easy scaling

---

## 🛠️ VDS Kurulum Rehberi

### 1. **VDS Oluşturma (DigitalOcean)**

```bash
# 1. DigitalOcean'da hesap oluştur
# 2. New Droplet → CPU-Optimized
# 3. Ubuntu 22.04 LTS seç
# 4. 4 vCPU, 8GB RAM, 100GB SSD
# 5. Frankfurt datacenter seç
# 6. SSH Key ekle (güvenlik için)
```

### 2. **SSH Bağlantısı**

```bash
# SSH key ile bağlan
ssh root@your-server-ip

# Veya password ile
ssh root@your-server-ip
```

### 3. **Sistem Güncelleme**

```bash
# Sistem güncellemesi
apt update && apt upgrade -y

# Temel araçları yükle
apt install -y git curl wget unzip software-properties-common
```

### 4. **PHP 8.2 Kurulumu**

```bash
# PHP repository ekle
add-apt-repository ppa:ondrej/php -y
apt update

# PHP 8.2 ve gerekli extension'ları yükle
apt install -y php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-pgsql \
    php8.2-sqlite3 \
    php8.2-redis \
    php8.2-curl \
    php8.2-json \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-gd \
    php8.2-opcache

# PHP version kontrol
php -v
```

### 5. **Composer Kurulumu**

```bash
# Composer yükle
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Version kontrol
composer --version
```

### 6. **PostgreSQL Kurulumu**

```bash
# PostgreSQL yükle
apt install -y postgresql postgresql-contrib

# PostgreSQL servisini başlat
systemctl start postgresql
systemctl enable postgresql

# Database ve user oluştur
sudo -u postgres psql << EOF
CREATE DATABASE sentinentx;
CREATE USER sentinentx_user WITH PASSWORD 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE sentinentx TO sentinentx_user;
ALTER USER sentinentx_user CREATEDB;
\q
EOF
```

### 7. **Redis Kurulumu**

```bash
# Redis yükle
apt install -y redis-server

# Redis konfigürasyonu
sed -i 's/^# requirepass/requirepass YOUR_REDIS_PASSWORD/' /etc/redis/redis.conf
sed -i 's/^bind 127.0.0.1/bind 127.0.0.1/' /etc/redis/redis.conf

# Redis servisini başlat
systemctl restart redis-server
systemctl enable redis-server

# Redis test
redis-cli ping
```

### 8. **Nginx Kurulumu**

```bash
# Nginx yükle
apt install -y nginx

# Nginx başlat
systemctl start nginx
systemctl enable nginx
```

### 9. **Node.js Kurulumu (Frontend assets için)**

```bash
# Node.js 18 yükle
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Version kontrol
node -v
npm -v
```

---

## 📁 SentinentX Kurulumu

### 1. **Git Clone**

```bash
# Proje klasörü oluştur
mkdir -p /var/www
cd /var/www

# Repository clone et
git clone https://github.com/your-username/sentinentx.git
cd sentinentx

# Klasör izinleri ayarla
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
```

### 2. **Dependencies Kurulumu**

```bash
# Composer dependencies
composer install --optimize-autoloader --no-dev

# NPM dependencies (frontend assets için)
npm install
npm run build
```

### 3. **Environment Konfigürasyonu**

```bash
# .env dosyası oluştur
cp env.example.template .env

# .env dosyasını düzenle
nano .env
```

**Kritik .env ayarları:**

```env
# Application
APP_NAME=SentinentX
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentinentx
DB_USERNAME=sentinentx_user
DB_PASSWORD=YOUR_SECURE_PASSWORD

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
REDIS_PORT=6379

# AI Providers (TEST KEYS İÇİN)
OPENAI_API_KEY=sk-your-openai-key-here
GEMINI_API_KEY=your-gemini-key-here
GROK_API_KEY=your-grok-key-here

# CoinGecko
COINGECKO_API_KEY=CG-Xo5enN9WjkBkeeYHEDG9aium

# Bybit (TESTNET İÇİN)
BYBIT_API_KEY=your-testnet-api-key
BYBIT_API_SECRET=your-testnet-secret
BYBIT_TESTNET=true

# Telegram
TELEGRAM_BOT_TOKEN=your-telegram-bot-token
TELEGRAM_CHAT_ID=your-telegram-chat-id

# Security
HMAC_SECRET=$(openssl rand -hex 32)
IP_ALLOWLIST="127.0.0.1/32,YOUR_HOME_IP/32"
```

### 4. **Laravel Konfigürasyonu**

```bash
# App key generate
php artisan key:generate

# Database migration
php artisan migrate --force

# Cache optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Storage link
php artisan storage:link

# İzinleri düzelt
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### 5. **Nginx Konfigürasyonu**

```bash
# Nginx site config oluştur
cat > /etc/nginx/sites-available/sentinentx << 'EOF'
server {
    listen 80;
    server_name your-domain.com your-server-ip;
    root /var/www/sentinentx/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Site'ı aktif et
ln -s /etc/nginx/sites-available/sentinentx /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default

# Nginx test ve restart
nginx -t
systemctl restart nginx
```

### 6. **PHP-FPM Optimizasyonu**

```bash
# PHP-FPM config düzenle
nano /etc/php/8.2/fpm/pool.d/www.conf

# Bu ayarları güncelle:
# pm.max_children = 50
# pm.start_servers = 10
# pm.min_spare_servers = 5
# pm.max_spare_servers = 15

# PHP-FPM restart
systemctl restart php8.2-fpm
```

---

## 🔄 Systemd Services (Otomatik Başlatma)

### 1. **Laravel Queue Worker**

```bash
# Service dosyası oluştur
cat > /etc/systemd/system/sentinentx-queue.service << 'EOF'
[Unit]
Description=SentinentX Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=60

[Install]
WantedBy=multi-user.target
EOF

# Service'i aktif et
systemctl enable sentinentx-queue
systemctl start sentinentx-queue
```

### 2. **Telegram Polling Service**

```bash
# Service dosyası oluştur
cat > /etc/systemd/system/sentinentx-telegram.service << 'EOF'
[Unit]
Description=SentinentX Telegram Bot
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php artisan telegram:polling

[Install]
WantedBy=multi-user.target
EOF

# Service'i aktif et
systemctl enable sentinentx-telegram
systemctl start sentinentx-telegram
```

### 3. **Position Monitoring Service**

```bash
# Cron job ekle
crontab -e

# Bu satırı ekle (her dakika pozisyon kontrol)
* * * * * cd /var/www/sentinentx && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔒 Güvenlik Ayarları

### 1. **Firewall Konfigürasyonu**

```bash
# UFW firewall aktif et
ufw enable

# Gerekli portları aç
ufw allow ssh
ufw allow 80
ufw allow 443

# Gereksiz servisleri kapat
systemctl disable apache2 2>/dev/null || true
systemctl stop apache2 2>/dev/null || true
```

### 2. **SSH Güvenliği**

```bash
# SSH config düzenle
nano /etc/ssh/sshd_config

# Bu ayarları güncelle:
# PermitRootLogin no
# PasswordAuthentication no
# PubkeyAuthentication yes
# Port 2222 (opsiyonel)

# SSH restart
systemctl restart ssh
```

### 3. **Fail2Ban Kurulumu**

```bash
# Fail2ban yükle
apt install -y fail2ban

# Basic config
systemctl enable fail2ban
systemctl start fail2ban
```

---

## 📊 Monitoring & Logging

### 1. **Log Rotasyonu**

```bash
# Laravel logs için logrotate
cat > /etc/logrotate.d/sentinentx << 'EOF'
/var/www/sentinentx/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 www-data www-data
}
EOF
```

### 2. **System Monitoring**

```bash
# Htop kurulum
apt install -y htop

# Process monitoring
htop

# Disk usage check
df -h

# Memory usage
free -h
```

---

## 🚀 Başlatma ve Test

### 1. **Servis Durumları Kontrol**

```bash
# Tüm servisleri kontrol et
systemctl status nginx
systemctl status php8.2-fpm
systemctl status postgresql
systemctl status redis-server
systemctl status sentinentx-queue
systemctl status sentinentx-telegram

# Log kontrol
tail -f /var/www/sentinentx/storage/logs/laravel.log
```

### 2. **Telegram Bot Test**

```bash
# Telegram'dan bot'a mesaj gönder
/help
/status
/scan
```

### 3. **API Test**

```bash
# Server'dan API test
curl -X GET "http://localhost/api/health"

# External'dan test
curl -X GET "http://your-server-ip/api/health"
```

---

## 🔧 Production Optimizasyonları

### 1. **PHP Optimizasyonu**

```bash
# OPcache settings
nano /etc/php/8.2/fpm/conf.d/10-opcache.ini

# Bu ayarları ekle:
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### 2. **Database Optimizasyonu**

```bash
# PostgreSQL config
nano /etc/postgresql/14/main/postgresql.conf

# Bu ayarları güncelle:
shared_buffers = 1GB
effective_cache_size = 4GB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
```

### 3. **Redis Optimizasyonu**

```bash
# Redis config
nano /etc/redis/redis.conf

# Bu ayarları ekle:
maxmemory 2gb
maxmemory-policy allkeys-lru
```

---

## 📈 15 Günlük Test Planı

### Hafta 1: Stabilite Testi
- **Gün 1-3**: Sistem kurulumu ve basic testler
- **Gün 4-5**: Telegram bot komutları test
- **Gün 6-7**: Bybit testnet trading

### Hafta 2: Performance Testi
- **Gün 8-10**: AI consensus testleri
- **Gün 11-12**: Position monitoring
- **Gün 13-14**: Alert system test

### Gün 15: Live Trading Hazırlığı
- Final security check
- Testnet'ten mainnet'e geçiş
- Production deployment

---

## 🆘 Sorun Giderme

### Yaygın Sorunlar

1. **"Storage not writable" hatası**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

2. **Database connection hatası**
   ```bash
   # PostgreSQL durumunu kontrol et
   systemctl status postgresql
   
   # Connection test
   sudo -u postgres psql -d sentinentx -c "SELECT 1;"
   ```

3. **Telegram bot cevap vermiyor**
   ```bash
   # Telegram service log kontrol
   journalctl -u sentinentx-telegram -f
   
   # Bot token kontrol
   grep TELEGRAM_BOT_TOKEN /var/www/sentinentx/.env
   ```

4. **High CPU usage**
   ```bash
   # Process monitoring
   top -p $(pgrep -d',' php)
   
   # Queue worker restart
   systemctl restart sentinentx-queue
   ```

---

## 💰 Maliyet Tahmini (15 Gün)

| Provider | Plan | Aylık | 15 Günlük |
|----------|------|--------|-----------|
| DigitalOcean | CPU-Optimized | $48 | $24 |
| Linode | Dedicated CPU | $36 | $18 |
| Vultr | High Frequency | $32 | $16 |
| Hetzner | CPX31 | €13 | €6.5 |

**Tavsiye**: DigitalOcean ile başla (~$24), gerekirse scale up yap.

---

## 🎯 Sonuç

Bu rehber ile SentinentX'i production-grade VDS'de çalıştırabilir ve 15 günlük test sürecini başarıyla tamamlayabilirsin. Sistem monitör et, logları takip et ve gerektiğinde optimizasyonlar yap.

**Başarılar! 🚀💰**
