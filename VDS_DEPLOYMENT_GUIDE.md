# 🚀 SENTINENTX VDS DEPLOYMENT REHBERİ

## 📋 **DEPLOYMENT ÖNCESİ HAZIRLIK**

### 1️⃣ **VDS Gereksinimleri (Testnet)**
```bash
# Minimum Sistem Gereksinimleri
CPU: 2 vCPU
RAM: 4GB 
Disk: 40GB SSD
Network: 100Mbps
OS: Ubuntu 22.04 LTS

# Önerilen Testnet VDS Sağlayıcıları
- DigitalOcean: $20/month (2vCPU, 4GB RAM, 80GB SSD)
- Vultr: $20/month (2vCPU, 4GB RAM, 80GB SSD)  
- Linode: $24/month (2vCPU, 4GB RAM, 80GB SSD)
- Hetzner: €16/month (2vCPU, 4GB RAM, 80GB SSD)
```

### 2️⃣ **Gerekli Bilgiler**
```bash
# Bybit Testnet API Keys
BYBIT_API_KEY="your_testnet_key"
BYBIT_API_SECRET="your_testnet_secret"

# AI Provider Keys
OPENAI_API_KEY="sk-..."
GEMINI_API_KEY="AIzaSy..."
GROK_API_KEY="grok_..."

# Telegram Bot Token
TELEGRAM_BOT_TOKEN="7..."
TELEGRAM_CHAT_ID="your_chat_id"

# Database Bilgileri
DB_PASSWORD="güçlü_şifre_123"
```

---

## 🔧 **ADIM 1: VDS KURULUMU**

### **1.1 VDS Satın Al ve Bağlan**
```bash
# SSH ile bağlan
ssh root@YOUR_VDS_IP

# Sistem güncellemesi
apt update && apt upgrade -y

# Gerekli paketler
apt install -y curl wget git unzip software-properties-common
```

### **1.2 PHP 8.2 Kurulumu**
```bash
# PHP repository ekle
add-apt-repository ppa:ondrej/php -y
apt update

# PHP ve extensions
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-pgsql \
    php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring php8.2-bcmath \
    php8.2-gd php8.2-redis php8.2-intl

# PHP timezone ayarla
sed -i 's/;date.timezone =/date.timezone = Europe\/Istanbul/' /etc/php/8.2/cli/php.ini
sed -i 's/;date.timezone =/date.timezone = Europe\/Istanbul/' /etc/php/8.2/fpm/php.ini
```

### **1.3 Composer Kurulumu**
```bash
# Composer yükle
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### **1.4 PostgreSQL Kurulumu**
```bash
# PostgreSQL yükle
apt install -y postgresql postgresql-contrib

# Database oluştur
sudo -u postgres createuser sentx
sudo -u postgres createdb sentx
sudo -u postgres psql -c "ALTER USER sentx PASSWORD 'güçlü_şifre_123';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentx TO sentx;"
```

### **1.5 Redis Kurulumu**
```bash
# Redis yükle ve başlat
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server
```

---

## 📦 **ADIM 2: PROJE DEPLOYMENT**

### **2.1 Proje Upload**
```bash
# Proje klasörü oluştur
mkdir -p /var/www/sentinentx
cd /var/www/sentinentx

# Proje dosyalarını upload et (scp veya rsync ile)
# Local'den:
scp -r /home/emir/Desktop/sentinentx/* root@YOUR_VDS_IP:/var/www/sentinentx/

# VEYA GitHub'dan clone (eğer push ettiysen):
# git clone https://github.com/yourusername/sentinentx.git .
```

### **2.2 Permissions ve Ownership**
```bash
# Web server user oluştur
useradd -r -s /bin/false www-data

# Ownership ayarla
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
chmod -R 775 /var/www/sentinentx/storage
chmod -R 775 /var/www/sentinentx/bootstrap/cache
```

### **2.3 Environment Yapılandırması**
```bash
cd /var/www/sentinentx

# .env dosyası oluştur
cp env.production.template .env

# .env dosyasını düzenle
nano .env
```

**`.env` Dosyası İçeriği:**
```env
# LARAVEL CORE
APP_NAME=SentinentX
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_VDS_IP
APP_TIMEZONE=Europe/Istanbul
APP_KEY=base64:PASTE_GENERATED_KEY_HERE

# DATABASE
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentx
DB_USERNAME=sentx
DB_PASSWORD=güçlü_şifre_123

# CACHE & QUEUE
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# BYBIT TESTNET
BYBIT_TESTNET=true
BYBIT_API_KEY=your_testnet_key
BYBIT_API_SECRET=your_testnet_secret

# AI PROVIDERS
OPENAI_API_KEY=sk-...
GEMINI_API_KEY=AIzaSy...
GROK_API_KEY=grok_...

# TELEGRAM
TELEGRAM_BOT_TOKEN=7...
TELEGRAM_CHAT_ID=your_chat_id

# LOGGING
LOG_CHANNEL=json
LOG_LEVEL=info
```

### **2.4 Laravel Setup**
```bash
cd /var/www/sentinentx

# Dependencies yükle
composer install --no-dev --optimize-autoloader

# APP_KEY generate et
php artisan key:generate

# Database migration
php artisan migrate --force

# Cache optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔄 **ADIM 3: SERVİSLERİ BAŞLATMA**

### **3.1 Systemd Service Files**

**Laravel Queue Worker:**
```bash
# Service dosyası oluştur
cat > /etc/systemd/system/sentx-queue.service << EOF
[Unit]
Description=SentinentX Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
```

**Telegram Bot:**
```bash
cat > /etc/systemd/system/sentx-telegram.service << EOF
[Unit]
Description=SentinentX Telegram Bot
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan telegram:polling
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
```

**Laravel Scheduler:**
```bash
cat > /etc/systemd/system/sentx-scheduler.service << EOF
[Unit]
Description=SentinentX Scheduler
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan schedule:work
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
```

### **3.2 Servisleri Etkinleştir**
```bash
# Servisleri reload ve enable et
systemctl daemon-reload
systemctl enable sentx-queue
systemctl enable sentx-telegram  
systemctl enable sentx-scheduler

# Servisleri başlat
systemctl start sentx-queue
systemctl start sentx-telegram
systemctl start sentx-scheduler

# Status kontrol
systemctl status sentx-queue
systemctl status sentx-telegram
systemctl status sentx-scheduler
```

---

## ✅ **ADIM 4: SİSTEM KONTROLÜ**

### **4.1 Health Check**
```bash
cd /var/www/sentinentx

# Database bağlantısı
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database: OK\n';"

# Redis bağlantısı  
php artisan tinker --execute="Cache::put('test', 'ok'); echo 'Redis: ' . Cache::get('test') . '\n';"

# Bybit API test
php artisan sentx:system-check

# LAB system test
php artisan sentx:lab-monitor
```

### **4.2 Log Monitoring**
```bash
# Service logları
journalctl -u sentx-queue -f
journalctl -u sentx-telegram -f
journalctl -u sentx-scheduler -f

# Laravel logları
tail -f /var/www/sentinentx/storage/logs/laravel.log
```

---

## 🎯 **ADIM 5: 15 GÜNLÜK TESTNET SÜRECİNİ BAŞLATMA**

### **5.1 Risk Profili Ayarlama**
```bash
# Risk profili seç (Low/Medium/High)
php artisan sentx:risk-profile
# Seçenek: Medium (15-45x leverage)
```

### **5.2 LAB Sistemi Başlatma**
```bash
# LAB run başlat (15 günlük)
php artisan sentx:lab-start --days=15 --initial-balance=1000

# LAB monitoring aktif et
php artisan sentx:lab-monitor
```

### **5.3 Telegram Bot Test**
```bash
# Telegram'dan test komutları:
/start
/help
/status
/balance
/scan
/open BTCUSDT
```

### **5.4 Trading Döngüsünü Başlat**
```bash
# Otomatik scan'i aktif et (her 2 saatte)
# Zaten scheduler ile aktif, manuel başlatma:
php artisan sentx:scan
```

---

## 📊 **ADIM 6: MONİTORİNG VE MAINTENANCE**

### **6.1 Günlük Kontroller**
```bash
# Sistem durumu
./start_sentinentx.sh status

# LAB performans
php artisan sentx:lab-monitor

# Trade geçmişi
php artisan sentx:trades --days=1

# Log kontrolü
tail -n 100 /var/www/sentinentx/storage/logs/laravel.log
```

### **6.2 Weekly Maintenance**
```bash
# Log rotation
find /var/www/sentinentx/storage/logs -name "*.log" -mtime +7 -delete

# Cache temizleme
php artisan cache:clear
php artisan config:cache

# Database optimization
php artisan sentx:cleanup --days=30
```

---

## 🚨 **TROUBLESHOOTING**

### **Yaygın Sorunlar:**

**1. Servis başlamıyor:**
```bash
# Log kontrol
journalctl -u sentx-queue --no-pager
# Permission kontrol
ls -la /var/www/sentinentx/storage/
```

**2. Database connection error:**
```bash
# PostgreSQL status
systemctl status postgresql
# Connection test
psql -h 127.0.0.1 -U sentx -d sentx
```

**3. Telegram bot cevap vermiyor:**
```bash
# Bot service kontrol
systemctl status sentx-telegram
# Network test
curl -X GET "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/getMe"
```

**4. AI API errors:**
```bash
# API key test
php artisan sentx:ai-test
# Rate limit kontrol
tail -f storage/logs/laravel.log | grep -i "rate"
```

---

## 🎉 **DEPLOYMENT TAMAMLANDI!**

### **Başarılı Deployment Kontrol Listesi:**
- ✅ VDS kurulumu ve yapılandırması
- ✅ Proje dosyaları upload edildi  
- ✅ Database migration tamamlandı
- ✅ Tüm servisler çalışıyor
- ✅ Telegram bot aktif
- ✅ AI providers test edildi
- ✅ Bybit testnet bağlantısı OK
- ✅ LAB sistemi başlatıldı
- ✅ 15 günlük testnet süreci aktif

### **İletişim Kanalları:**
- 📱 **Telegram Bot**: Günlük trading işlemleri
- 📊 **LAB Monitor**: Performans takibi  
- 📝 **Log Files**: Detaylı sistem kayıtları
- 🔍 **System Check**: Sağlık durumu kontrolü

**🚀 SentinentX artık production'da ve 15 günlük testnet sürecine hazır!**
