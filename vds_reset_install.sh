#!/bin/bash

# SentinentX VDS Reset & Install Script
# Bu script VDS'i temizler ve fresh kurulum yapar

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}🚀 SENTINENTX VDS RESET & INSTALL${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Root check
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Root yetkileri gerekli!${NC}"
   echo "Kullanım: sudo bash $0"
   exit 1
fi

# Auto-confirm for pipe mode, ask for interactive mode
if [ -t 0 ]; then
    echo -e "${YELLOW}⚠️  Bu script VDS'teki tüm SentinentX kalıntılarını silip yeniden kuracak!${NC}"
    read -p "Devam etmek için 'yes' yazın: " confirm
    if [[ $confirm != "yes" ]]; then
        echo -e "${RED}❌ İşlem iptal edildi.${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✅ Pipe mode - otomatik devam${NC}"
    sleep 1
fi

echo ""
echo -e "${RED}🧹 PHASE 1: CLEANING VDS${NC}"
echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Stop processes
echo -e "${YELLOW}🛑 Stopping processes...${NC}"
pkill -f "sentx" 2>/dev/null || true
pkill -f "php artisan" 2>/dev/null || true
killall -9 php 2>/dev/null || true
systemctl stop sentx-* 2>/dev/null || true
systemctl disable sentx-* 2>/dev/null || true
echo -e "${GREEN}✅ Processes stopped${NC}"

# Remove directories
echo -e "${YELLOW}📁 Removing directories...${NC}"
rm -rf /var/www/sentinentx 2>/dev/null || true
rm -rf /var/www/SentinentX 2>/dev/null || true
echo -e "${GREEN}✅ Directories removed${NC}"

# Remove services
echo -e "${YELLOW}⚙️  Removing services...${NC}"
rm -f /etc/systemd/system/sentx-*.service 2>/dev/null || true
systemctl daemon-reload
echo -e "${GREEN}✅ Services removed${NC}"

# Clean database
echo -e "${YELLOW}🗄️  Cleaning database...${NC}"
sudo -u postgres psql -c "DROP DATABASE IF EXISTS sentx;" 2>/dev/null || true
sudo -u postgres psql -c "DROP USER IF EXISTS sentx;" 2>/dev/null || true
echo -e "${GREEN}✅ Database cleaned${NC}"

# Clean Redis
echo -e "${YELLOW}🧽 Cleaning Redis...${NC}"
redis-cli FLUSHALL 2>/dev/null || true
echo -e "${GREEN}✅ Redis cleaned${NC}"

# Clean logs and temp
echo -e "${YELLOW}📝 Cleaning logs and temp files...${NC}"
rm -rf /var/log/sentx* 2>/dev/null || true
rm -rf /tmp/composer-* 2>/dev/null || true
rm -rf /tmp/php* 2>/dev/null || true
echo -e "${GREEN}✅ Logs and temp files cleaned${NC}"

echo ""
echo -e "${GREEN}🎉 VDS CLEANED SUCCESSFULLY!${NC}"
sleep 2

echo ""
echo -e "${BLUE}🚀 PHASE 2: FRESH INSTALLATION${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Aggressive apt lock cleanup
echo -e "${CYAN}🔐 Cleaning apt locks and processes...${NC}"

# Kill all apt/dpkg processes immediately
echo -e "${YELLOW}🔪 Killing apt/dpkg processes...${NC}"
pkill -9 -f "apt|dpkg|unattended-upgrade" 2>/dev/null || true
killall -9 apt apt-get dpkg 2>/dev/null || true

# Wait a moment for processes to die
sleep 3

# Remove all lock files
echo -e "${YELLOW}🗑️ Removing lock files...${NC}"
rm -f /var/lib/dpkg/lock-frontend 2>/dev/null || true
rm -f /var/lib/dpkg/lock 2>/dev/null || true
rm -f /var/cache/apt/archives/lock 2>/dev/null || true
rm -f /var/lib/apt/lists/lock 2>/dev/null || true

# Configure dpkg
echo -e "${YELLOW}⚙️ Configuring dpkg...${NC}"
dpkg --configure -a 2>/dev/null || true

# Final check with timeout
echo -e "${CYAN}🔍 Final lock check with timeout...${NC}"
timeout=0
while fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1 && [ $timeout -lt 6 ]; do
    echo -e "${YELLOW}⏳ Waiting... (${timeout}/5)${NC}"
    sleep 5
    timeout=$((timeout + 1))
done

# If still locked after timeout, force remove and continue
if fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1; then
    echo -e "${RED}⚠️ Force removing persistent locks...${NC}"
    fuser -k /var/lib/dpkg/lock-frontend 2>/dev/null || true
    rm -f /var/lib/dpkg/lock-frontend 2>/dev/null || true
    sleep 2
fi

echo -e "${GREEN}✅ Lock cleanup completed${NC}"

# Update system
echo -e "${CYAN}📦 STEP 1/12: System Update${NC}"
apt update -y
echo -e "${GREEN}✅ System updated${NC}"

# Install basic packages
echo -e "${CYAN}📦 STEP 2/12: Installing Basic Packages${NC}"
apt install -y curl wget git unzip software-properties-common
echo -e "${GREEN}✅ Basic packages installed${NC}"

# Install PHP 8.2
echo -e "${CYAN}🐘 STEP 3/12: Installing PHP 8.2${NC}"

# Quick lock check before adding repository
timeout=0
while fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1 && [ $timeout -lt 3 ]; do
    echo -e "${YELLOW}⏳ Quick check before PHP repository... (${timeout}/2)${NC}"
    sleep 2
    timeout=$((timeout + 1))
done
if fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1; then
    echo -e "${RED}⚠️ Force proceeding with PHP repository...${NC}"
    fuser -k /var/lib/dpkg/lock-frontend 2>/dev/null || true
fi

add-apt-repository ppa:ondrej/php -y

# Quick lock check before update
timeout=0
while fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1 && [ $timeout -lt 2 ]; do
    echo -e "${YELLOW}⏳ Quick check before update... (${timeout}/1)${NC}"
    sleep 3
    timeout=$((timeout + 1))
done
if fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1; then
    echo -e "${RED}⚠️ Force proceeding with update...${NC}"
    fuser -k /var/lib/dpkg/lock-frontend 2>/dev/null || true
fi

apt update -y

# Install PHP packages one by one to handle missing packages gracefully
PHP_PACKAGES=(
    "php8.2"
    "php8.2-cli" 
    "php8.2-fpm"
    "php8.2-mysql"
    "php8.2-pgsql"
    "php8.2-sqlite3"
    "php8.2-redis"
    "php8.2-curl"
    "php8.2-mbstring"
    "php8.2-xml"
    "php8.2-zip"
    "php8.2-gd"
    "php8.2-intl"
    "php8.2-bcmath"
    "php8.2-soap"
    "php8.2-xsl"
    "php8.2-opcache"
)

for package in "${PHP_PACKAGES[@]}"; do
    if apt-cache show "$package" > /dev/null 2>&1; then
        apt install -y "$package"
        echo -e "${GREEN}✅ $package installed${NC}"
    else
        echo -e "${YELLOW}⚠️ $package not available, skipping${NC}"
    fi
done

echo -e "${GREEN}✅ PHP 8.2 installation completed${NC}"

# Install Composer
echo -e "${CYAN}🎼 STEP 4/12: Installing Composer${NC}"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
echo -e "${GREEN}✅ Composer installed${NC}"

# Install PostgreSQL
echo -e "${CYAN}🗄️  STEP 5/12: Installing PostgreSQL${NC}"

# Quick lock check before PostgreSQL
if fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1; then
    echo -e "${RED}⚠️ Force proceeding with PostgreSQL...${NC}"
    fuser -k /var/lib/dpkg/lock-frontend 2>/dev/null || true
    sleep 1
fi

apt install -y postgresql postgresql-contrib
systemctl start postgresql
systemctl enable postgresql
echo -e "${GREEN}✅ PostgreSQL installed${NC}"

# Create database
echo -e "${CYAN}🗄️  STEP 6/12: Creating Database${NC}"
sudo -u postgres psql -c "CREATE USER sentx WITH PASSWORD 'sentx123';"
sudo -u postgres psql -c "CREATE DATABASE sentx OWNER sentx;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentx TO sentx;"
echo -e "${GREEN}✅ Database created${NC}"

# Install Redis
echo -e "${CYAN}🧽 STEP 7/12: Installing Redis${NC}"

# Quick lock check before Redis
if fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1; then
    echo -e "${RED}⚠️ Force proceeding with Redis...${NC}"
    fuser -k /var/lib/dpkg/lock-frontend 2>/dev/null || true
    sleep 1
fi

apt install -y redis-server
systemctl start redis-server
systemctl enable redis-server
echo -e "${GREEN}✅ Redis installed${NC}"

# Clone project
echo -e "${CYAN}📥 STEP 8/12: Cloning Project${NC}"
mkdir -p /var/www/sentinentx
cd /var/www/sentinentx
git clone https://github.com/emiryucelweb/SentinentX.git .
echo -e "${GREEN}✅ Project cloned${NC}"

# Set permissions
echo -e "${CYAN}🔐 STEP 9/12: Setting Permissions${NC}"
useradd -r -s /bin/false www-data 2>/dev/null || true
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
chmod -R 775 /var/www/sentinentx/storage
chmod -R 775 /var/www/sentinentx/bootstrap/cache
echo -e "${GREEN}✅ Permissions set${NC}"

# Install dependencies
echo -e "${CYAN}📦 STEP 10/12: Installing Dependencies${NC}"
cd /var/www/sentinentx
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ Dependencies installed${NC}"

# Configure Laravel
echo -e "${CYAN}🔧 STEP 11/12: Configuring Laravel${NC}"
cp .env.example .env
php artisan key:generate --force

# Update .env with basic config
cat > .env << 'EOF'
APP_NAME=SentinentX
APP_ENV=production
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

BYBIT_TESTNET=true
BYBIT_BASE_URL=https://api-testnet.bybit.com
BYBIT_API_KEY=
BYBIT_API_SECRET=

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini

GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash-exp
GEMINI_BASE_URL=https://generativelanguage.googleapis.com

GROK_API_KEY=
GROK_MODEL=grok-2-1212
GROK_BASE_URL=https://api.x.ai/v1

TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

TRADING_MAX_LEVERAGE=75
TRADING_MODE_ONE_WAY=true
TRADING_MARGIN_MODE=cross

COINGECKO_BASE_URL=https://api.coingecko.com/api/v3
COINGECKO_TIMEOUT=15
EOF

# Generate new key
php artisan key:generate --force
echo -e "${GREEN}✅ Laravel configured${NC}"

# Run migrations
echo -e "${CYAN}🗄️  STEP 12/12: Running Database Migration${NC}"
php artisan migrate --force
echo -e "${GREEN}✅ Database migrated${NC}"

# Create services
echo -e "${CYAN}🔄 Creating System Services${NC}"

# Queue Worker Service
cat > /etc/systemd/system/sentx-queue.service << 'EOF'
[Unit]
Description=SentinentX Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Telegram Bot Service
cat > /etc/systemd/system/sentx-telegram.service << 'EOF'
[Unit]
Description=SentinentX Telegram Bot
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan telegram:polling
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Enable services
systemctl daemon-reload
systemctl enable sentx-queue
systemctl enable sentx-telegram

echo -e "${GREEN}✅ Services created${NC}"

echo ""
echo -e "${GREEN}🎉 INSTALLATION COMPLETED!${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}📝 NEXT STEPS:${NC}"
echo "1. Edit /var/www/sentinentx/.env and add your API keys:"
echo "   - BYBIT_API_KEY=your_key"
echo "   - BYBIT_API_SECRET=your_secret"
echo "   - OPENAI_API_KEY=sk-your_key"
echo "   - GEMINI_API_KEY=AIzaSy_your_key"
echo "   - GROK_API_KEY=your_key"
echo "   - TELEGRAM_BOT_TOKEN=your_token"
echo "   - TELEGRAM_CHAT_ID=your_chat_id"
echo ""
echo "2. Start services:"
echo "   systemctl start sentx-queue"
echo "   systemctl start sentx-telegram"
echo ""
echo "3. Check status:"
echo "   systemctl status sentx-queue"
echo "   systemctl status sentx-telegram"
echo ""
echo -e "${GREEN}🚀 SentinentX is ready for testnet trading!${NC}"
