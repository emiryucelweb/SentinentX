#!/bin/bash

# ========================================
# SENTINENTX VDS OTOMATIK KURULUM SCRIPTI
# ========================================
# Kullanım: curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/vds_auto_install.sh | bash
# Veya: wget -O - https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/vds_auto_install.sh | bash

set -e  # Hata durumunda dur

# Renkli çıktı için
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Logo ve başlık
echo -e "${PURPLE}"
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                    🚀 SENTINENTX AUTO INSTALLER               ║"
echo "║                                                               ║"
echo "║  AI-Powered Cryptocurrency Trading Bot                       ║"
echo "║  Automatic VDS Setup & Deployment                            ║"
echo "║                                                               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Başlangıç mesajı
echo -e "${CYAN}🎯 SentinentX Otomatik Kurulum Başlıyor...${NC}"
echo -e "${YELLOW}⏰ Tahmini süre: 10-15 dakika${NC}"
echo ""

# Root kontrolü
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Bu script root kullanıcısı ile çalıştırılmalı!${NC}"
   echo "Kullanım: sudo bash vds_auto_install.sh"
   exit 1
fi

# API key'leri kullanıcıdan al
echo -e "${BLUE}🔑 API KEY'LERİ GİRİN:${NC}"
echo -e "${YELLOW}💡 Boş bırakırsanız daha sonra manuel ayarlayabilirsiniz${NC}"
echo ""

read -p "📡 Bybit Testnet API Key: " BYBIT_API_KEY
read -s -p "🔐 Bybit Testnet Secret: " BYBIT_API_SECRET
echo ""
read -p "🤖 OpenAI API Key (sk-...): " OPENAI_API_KEY
read -p "🧠 Gemini API Key (AIzaSy...): " GEMINI_API_KEY
read -p "🚀 Grok API Key: " GROK_API_KEY
read -p "📱 Telegram Bot Token: " TELEGRAM_BOT_TOKEN
read -p "💬 Telegram Chat ID: " TELEGRAM_CHAT_ID

echo ""
echo -e "${GREEN}✅ API key'ler alındı! Kurulum başlıyor...${NC}"
echo ""

# Log dosyası
LOGFILE="/var/log/sentinentx_install.log"
exec 1> >(tee -a $LOGFILE)
exec 2> >(tee -a $LOGFILE >&2)

# Adım 1: Sistem güncellemesi
echo -e "${CYAN}📦 ADIM 1/10: Sistem güncelleniyor...${NC}"
apt update -y
apt upgrade -y
echo -e "${GREEN}✅ Sistem güncellemesi tamamlandı${NC}"

# Adım 2: Temel paketler
echo -e "${CYAN}📦 ADIM 2/10: Temel paketler yükleniyor...${NC}"
apt install -y curl wget git unzip software-properties-common ca-certificates gnupg lsb-release
echo -e "${GREEN}✅ Temel paketler yüklendi${NC}"

# Adım 3: PHP 8.2
echo -e "${CYAN}🐘 ADIM 3/10: PHP 8.2 yükleniyor...${NC}"
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-pgsql php8.2-xml php8.2-curl \
    php8.2-zip php8.2-mbstring php8.2-bcmath php8.2-gd php8.2-redis php8.2-intl

# PHP timezone ayarı
sed -i 's/;date.timezone =/date.timezone = Europe\/Istanbul/' /etc/php/8.2/cli/php.ini
sed -i 's/;date.timezone =/date.timezone = Europe\/Istanbul/' /etc/php/8.2/fpm/php.ini

echo -e "${GREEN}✅ PHP 8.2 yüklendi ve yapılandırıldı${NC}"

# Adım 4: Composer
echo -e "${CYAN}🎼 ADIM 4/10: Composer yükleniyor...${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
echo -e "${GREEN}✅ Composer yüklendi${NC}"

# Adım 5: PostgreSQL
echo -e "${CYAN}🐘 ADIM 5/10: PostgreSQL yükleniyor...${NC}"
apt install -y postgresql postgresql-contrib

# PostgreSQL servisini başlat
systemctl start postgresql
systemctl enable postgresql

# Database ve kullanıcı oluştur
sudo -u postgres createuser sentx 2>/dev/null || true
sudo -u postgres createdb sentx 2>/dev/null || true
sudo -u postgres psql -c "ALTER USER sentx PASSWORD 'sentx123';" 2>/dev/null || true
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentx TO sentx;" 2>/dev/null || true

echo -e "${GREEN}✅ PostgreSQL yüklendi ve yapılandırıldı${NC}"

# Adım 6: Redis
echo -e "${CYAN}🔴 ADIM 6/10: Redis yükleniyor...${NC}"
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# Redis test
if redis-cli ping | grep -q "PONG"; then
    echo -e "${GREEN}✅ Redis yüklendi ve çalışıyor${NC}"
else
    echo -e "${RED}❌ Redis kurulumunda sorun var${NC}"
fi

# Adım 7: Proje indirme
echo -e "${CYAN}📁 ADIM 7/10: SentinentX projesi indiriliyor...${NC}"
mkdir -p /var/www/sentinentx
cd /var/www/sentinentx

# Eğer klasör boş değilse temizle
if [ "$(ls -A /var/www/sentinentx)" ]; then
    rm -rf /var/www/sentinentx/*
fi

git clone https://github.com/emiryucelweb/SentinentX.git .
echo -e "${GREEN}✅ Proje indirildi${NC}"

# Adım 8: İzinler
echo -e "${CYAN}👤 ADIM 8/10: İzinler ayarlanıyor...${NC}"
useradd -r -s /bin/false www-data 2>/dev/null || true
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
chmod -R 775 /var/www/sentinentx/storage
chmod -R 775 /var/www/sentinentx/bootstrap/cache
echo -e "${GREEN}✅ İzinler ayarlandı${NC}"

# Adım 9: Composer install
echo -e "${CYAN}📦 ADIM 9/10: PHP dependencies yükleniyor...${NC}"
cd /var/www/sentinentx
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ Dependencies yüklendi${NC}"

# Adım 10: Laravel yapılandırması
echo -e "${CYAN}🔧 ADIM 10/10: Laravel yapılandırılıyor...${NC}"

# .env dosyası oluştur
cp .env.example .env

# Laravel key generate
php artisan key:generate --force

# .env dosyasını yapılandır
cat > .env << EOF
APP_NAME=SentinentX
APP_ENV=production
APP_KEY=$(php artisan --no-ansi key:generate --show)
APP_DEBUG=false
APP_TIMEZONE=Europe/Istanbul
APP_URL=http://localhost
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=json
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentx
DB_USERNAME=sentx
DB_PASSWORD=sentx123

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Bybit Testnet
BYBIT_TESTNET=true
BYBIT_API_KEY=${BYBIT_API_KEY}
BYBIT_API_SECRET=${BYBIT_API_SECRET}

# AI Providers
OPENAI_API_KEY=${OPENAI_API_KEY}
GEMINI_API_KEY=${GEMINI_API_KEY}
GROK_API_KEY=${GROK_API_KEY}

# Telegram Bot
TELEGRAM_BOT_TOKEN=${TELEGRAM_BOT_TOKEN}
TELEGRAM_CHAT_ID=${TELEGRAM_CHAT_ID}

# Trading Settings
TRADING_MAX_LEVERAGE=75
TRADING_MODE_ONE_WAY=true
TRADING_MARGIN_MODE=cross
EOF

echo -e "${GREEN}✅ .env dosyası oluşturuldu${NC}"

# Database migration
echo -e "${CYAN}🗄️ Database migration çalıştırılıyor...${NC}"
php artisan migrate --force
echo -e "${GREEN}✅ Database tabloları oluşturuldu${NC}"

# Cache optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Systemd servisleri oluştur
echo -e "${CYAN}🔄 Systemd servisleri oluşturuluyor...${NC}"

# Queue Worker Service
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

# Telegram Bot Service
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

# Scheduler Service
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

# Servisleri etkinleştir ve başlat
systemctl daemon-reload
systemctl enable sentx-queue sentx-telegram sentx-scheduler
systemctl start sentx-queue sentx-telegram sentx-scheduler

echo -e "${GREEN}✅ Systemd servisleri oluşturuldu ve başlatıldı${NC}"

# Sistem testleri
echo -e "${CYAN}🔍 Sistem testleri yapılıyor...${NC}"

# Database test
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q "OK"; then
    echo -e "${GREEN}✅ Database bağlantısı: OK${NC}"
else
    echo -e "${RED}❌ Database bağlantısı: HATA${NC}"
fi

# Redis test
if redis-cli ping 2>/dev/null | grep -q "PONG"; then
    echo -e "${GREEN}✅ Redis bağlantısı: OK${NC}"
else
    echo -e "${RED}❌ Redis bağlantısı: HATA${NC}"
fi

# Servis durumları
echo -e "${CYAN}📊 Servis durumları:${NC}"
for service in sentx-queue sentx-telegram sentx-scheduler; do
    if systemctl is-active --quiet $service; then
        echo -e "${GREEN}✅ $service: Çalışıyor${NC}"
    else
        echo -e "${RED}❌ $service: Durmuş${NC}"
    fi
done

# Yönetim scriptleri oluştur
echo -e "${CYAN}📜 Yönetim scriptleri oluşturuluyor...${NC}"

# Start script
cat > /var/www/sentinentx/start_sentinentx.sh << 'EOF'
#!/bin/bash
echo "🚀 SentinentX servisleri başlatılıyor..."
systemctl start sentx-queue sentx-telegram sentx-scheduler
echo "✅ Tüm servisler başlatıldı!"
systemctl status sentx-queue sentx-telegram sentx-scheduler --no-pager
EOF

# Stop script
cat > /var/www/sentinentx/stop_sentinentx.sh << 'EOF'
#!/bin/bash
echo "🛑 SentinentX servisleri durduruluyor..."
systemctl stop sentx-queue sentx-telegram sentx-scheduler
echo "✅ Tüm servisler durduruldu!"
EOF

# Status script
cat > /var/www/sentinentx/status_sentinentx.sh << 'EOF'
#!/bin/bash
echo "📊 SentinentX Sistem Durumu:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
for service in sentx-queue sentx-telegram sentx-scheduler; do
    if systemctl is-active --quiet $service; then
        echo "✅ $service: Çalışıyor"
    else
        echo "❌ $service: Durmuş"
    fi
done
echo ""
echo "🗄️ Database: $(cd /var/www/sentinentx && php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'OK'; } catch(Exception \$e) { echo 'ERROR'; }" 2>/dev/null)"
echo "🔴 Redis: $(redis-cli ping 2>/dev/null || echo 'ERROR')"
echo ""
echo "📱 Test komutları:"
echo "   php artisan sentx:system-check"
echo "   php artisan sentx:scan"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
EOF

chmod +x /var/www/sentinentx/*.sh

echo -e "${GREEN}✅ Yönetim scriptleri oluşturuldu${NC}"

# Final mesajlar
echo ""
echo -e "${PURPLE}╔═══════════════════════════════════════════════════════════════╗"
echo -e "║                    🎉 KURULUM TAMAMLANDI!                    ║"
echo -e "╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}✅ SentinentX başarıyla kuruldu ve çalışıyor!${NC}"
echo ""
echo -e "${CYAN}📋 SONRAKI ADIMLAR:${NC}"
echo -e "${YELLOW}1️⃣ Risk profili ayarla:${NC} cd /var/www/sentinentx && php artisan sentx:risk-profile"
echo -e "${YELLOW}2️⃣ LAB testi başlat:${NC} php artisan sentx:lab-start --days=15 --initial-balance=1000"
echo -e "${YELLOW}3️⃣ Telegram bot test:${NC} Bot'una /start mesajı gönder"
echo -e "${YELLOW}4️⃣ İlk tarama:${NC} php artisan sentx:scan"
echo ""
echo -e "${CYAN}🔧 YÖNETİM KOMUTLARI:${NC}"
echo -e "${YELLOW}• Servisleri başlat:${NC} ./start_sentinentx.sh"
echo -e "${YELLOW}• Servisleri durdur:${NC} ./stop_sentinentx.sh"
echo -e "${YELLOW}• Sistem durumu:${NC} ./status_sentinentx.sh"
echo -e "${YELLOW}• System check:${NC} php artisan sentx:system-check"
echo ""
echo -e "${CYAN}📁 PROJE KONUMU:${NC} /var/www/sentinentx"
echo -e "${CYAN}📜 LOG DOSYASI:${NC} $LOGFILE"
echo ""
echo -e "${GREEN}🚀 SentinentX hazır! Testnet trading'e başlayabilirsin!${NC}"
echo ""

# Son durum kontrolü
cd /var/www/sentinentx
./status_sentinentx.sh

exit 0
