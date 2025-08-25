#!/bin/bash

# SentinentX VDS Reset & Install Script - FIXED VERSION
# Bu script VDS'i temizler ve fresh kurulum yapar

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}🚀 SENTINENTX VDS RESET & INSTALL (FIXED)${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Root check
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Root yetkileri gerekli!${NC}"
   echo "Kullanım: sudo bash $0"
   exit 1
fi

# Auto-confirm for pipe mode
if [ -t 0 ]; then
    echo -e "${YELLOW}⚠️  Bu script VDS'teki tüm SentinentX kalıntılarını silip yeniden kuracak!${NC}"
    read -p "Devam etmek için 'yes' yazın: " confirm
    if [[ $confirm != "yes" ]]; then
        echo -e "${RED}❌ İşlem iptal edildi.${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✅ Pipe mode - otomatik devam${NC}"
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
rm -rf /var/www/sentinentx
rm -rf /tmp/sentinentx*
rm -rf /home/*/sentinentx
echo -e "${GREEN}✅ Directories removed${NC}"

# Remove services
echo -e "${YELLOW}⚙️  Removing services...${NC}"
rm -f /etc/systemd/system/sentx-*.service
systemctl daemon-reload
echo -e "${GREEN}✅ Services removed${NC}"

# Clean database
echo -e "${YELLOW}🗄️  Cleaning database...${NC}"
sudo -u postgres dropdb --if-exists sentx 2>/dev/null || true
sudo -u postgres dropuser --if-exists sentx 2>/dev/null || true
echo -e "${GREEN}✅ Database cleaned${NC}"

# Clean Redis
echo -e "${YELLOW}🧽 Cleaning Redis...${NC}"
redis-cli FLUSHALL 2>/dev/null || true
echo -e "${GREEN}✅ Redis cleaned${NC}"

echo ""
echo -e "${GREEN}🎉 VDS CLEANED SUCCESSFULLY!${NC}"
echo ""

echo -e "${CYAN}🚀 PHASE 2: FRESH INSTALLATION${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Fix working directory issue at start
cd /root 2>/dev/null || cd /tmp 2>/dev/null || cd /

# Function for aggressive apt lock cleanup
cleanup_apt_locks() {
    echo -e "${CYAN}🔐 Cleaning apt locks and processes...${NC}"
    
    # Kill all apt/dpkg processes
    echo -e "${YELLOW}🔪 Killing apt/dpkg processes...${NC}"
    pkill -9 apt 2>/dev/null || true
    pkill -9 dpkg 2>/dev/null || true
    pkill -9 unattended-upgrade 2>/dev/null || true
    killall -9 apt 2>/dev/null || true
    killall -9 dpkg 2>/dev/null || true
    killall -9 unattended-upgrades 2>/dev/null || true
    
    # Remove lock files
    echo -e "${YELLOW}🗑️ Removing lock files...${NC}"
    rm -f /var/lib/dpkg/lock-frontend
    rm -f /var/lib/dpkg/lock
    rm -f /var/cache/apt/archives/lock
    rm -f /var/lib/apt/lists/lock
    
    # Configure dpkg
    echo -e "${YELLOW}⚙️ Configuring dpkg...${NC}"
    dpkg --configure -a 2>/dev/null || true
    
    echo -e "${GREEN}✅ Lock cleanup completed${NC}"
}

# Initial cleanup
cleanup_apt_locks

# STEP 1: System Update
echo -e "${CYAN}📦 STEP 1/12: System Update${NC}"
apt update
apt upgrade -y
echo -e "${GREEN}✅ System updated${NC}"

# STEP 2: Install Basic Packages
echo -e "${CYAN}📦 STEP 2/12: Installing Basic Packages${NC}"
apt install -y curl git unzip wget software-properties-common
echo -e "${GREEN}✅ Basic packages installed${NC}"

# STEP 3: Install PHP 8.2
echo -e "${CYAN}🐘 STEP 3/12: Installing PHP 8.2${NC}"
add-apt-repository ppa:ondrej/php -y
apt update

# PHP packages to install
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
    if apt-cache show "$package" &>/dev/null; then
        echo -e "${YELLOW}Installing $package...${NC}"
        apt install -y "$package"
        echo -e "${GREEN}✅ $package installed${NC}"
    else
        echo -e "${YELLOW}⚠️ Skipping $package (not available)${NC}"
    fi
done

echo -e "${GREEN}✅ PHP 8.2 installation completed${NC}"

# STEP 4: Install Composer
echo -e "${CYAN}🎼 STEP 4/12: Installing Composer${NC}"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
echo -e "${GREEN}✅ Composer installed${NC}"

# STEP 5: Install PostgreSQL
echo -e "${CYAN}🗄️  STEP 5/12: Installing PostgreSQL${NC}"
apt install -y postgresql postgresql-contrib
systemctl start postgresql
systemctl enable postgresql
echo -e "${GREEN}✅ PostgreSQL installed${NC}"

# STEP 6: Create Database
echo -e "${CYAN}🗄️  STEP 6/12: Creating Database${NC}"
export PATH="/usr/lib/postgresql/*/bin:$PATH"
cd /tmp

# Create user and database
sudo -u postgres createuser -s sentx 2>/dev/null || true
sudo -u postgres psql -c "ALTER USER sentx PASSWORD 'sentx123';" 2>/dev/null || true
sudo -u postgres createdb sentx -O sentx 2>/dev/null || true
echo -e "${GREEN}✅ Database created${NC}"

# STEP 7: Install Redis
echo -e "${CYAN}🧽 STEP 7/12: Installing Redis${NC}"
apt install -y redis-server
systemctl start redis-server
systemctl enable redis-server
echo -e "${GREEN}✅ Redis installed${NC}"

# STEP 8: Clone Project
echo -e "${CYAN}📥 STEP 8/12: Cloning Project${NC}"
mkdir -p /var/www/sentinentx
cd /var/www/sentinentx
git clone https://github.com/emiryucelweb/SentinentX.git .
git config --global --add safe.directory /var/www/sentinentx
echo -e "${GREEN}✅ Project cloned${NC}"

# STEP 9: Set Permissions
echo -e "${CYAN}🔐 STEP 9/12: Setting Permissions${NC}"
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
chmod -R 775 /var/www/sentinentx/storage
chmod -R 775 /var/www/sentinentx/bootstrap/cache
echo -e "${GREEN}✅ Permissions set${NC}"

# STEP 10: Install Dependencies
echo -e "${CYAN}📦 STEP 10/12: Installing Dependencies${NC}"
cd /var/www/sentinentx
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ Dependencies installed${NC}"

# STEP 11: Configure Laravel
echo -e "${CYAN}🔧 STEP 11/12: Configuring Laravel${NC}"

# Create .env.example if missing
if [ ! -f .env.example ]; then
    cat > .env.example << 'ENVEXAMPLE'
APP_NAME=SentinentX
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Europe/Istanbul
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentx
DB_USERNAME=sentx
DB_PASSWORD=sentx123

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

BYBIT_API_KEY=
BYBIT_API_SECRET=
BYBIT_TESTNET=true

TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

OPENAI_API_KEY=
ANTHROPIC_API_KEY=
GEMINI_API_KEY=
GROK_API_KEY=
COINGECKO_API_KEY=
ENVEXAMPLE
fi

cp .env.example .env

echo -e "${CYAN}🔐 Creating basic .env configuration...${NC}"
echo -e "${YELLOW}⚠️ API keys will need to be added manually after installation${NC}"

# Update .env with complete config
cat > .env << ENVEOF
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

# Exchange API
BYBIT_API_KEY=
BYBIT_API_SECRET=
BYBIT_TESTNET=true
BYBIT_BASE_URL=https://api-testnet.bybit.com

# Telegram
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

# AI Providers
OPENAI_API_KEY=
OPENAI_ENABLED=true
OPENAI_MODEL=gpt-4o-mini

ANTHROPIC_API_KEY=

GEMINI_API_KEY=
GEMINI_ENABLED=true
GEMINI_MODEL=gemini-2.0-flash-exp
GEMINI_BASE_URL=https://generativelanguage.googleapis.com

GROK_API_KEY=
GROK_ENABLED=true
GROK_MODEL=grok-2-1212
GROK_BASE_URL=https://api.x.ai/v1

# Market Data
COINGECKO_API_KEY=
COINGECKO_BASE_URL=https://pro-api.coingecko.com/api/v3

# Trading Configuration
TRADING_MAX_LEVERAGE=75
TRADING_MODE_ONE_WAY=true
TRADING_MARGIN_MODE=cross

# Lab Configuration
LAB_ENVIRONMENT=testnet
LAB_INITIAL_BALANCE=1000
LAB_MAX_POSITION_SIZE=100
LAB_RISK_PER_TRADE=2
LAB_MAX_DRAWDOWN=10
LAB_STOP_LOSS_PCT=2
LAB_TAKE_PROFIT_PCT=4

# Security (placeholders)
SECURITY_ENCRYPTION_KEY=placeholder_encryption_key
HMAC_SECRET_KEY=placeholder_hmac_key
BYBIT_HMAC_SECRET=placeholder_bybit_hmac

# Monitoring
LOGGING_LEVEL=info
MONITORING_ENABLED=true
METRICS_ENABLED=true
ENVEOF

# Generate security keys and replace placeholders
echo -e "${CYAN}🔐 Generating security keys...${NC}"
ENCRYPTION_KEY="base64:$(openssl rand -base64 32)"
HMAC_KEY=$(openssl rand -hex 32)
BYBIT_HMAC_KEY=$(openssl rand -hex 32)

sed -i "s|placeholder_encryption_key|${ENCRYPTION_KEY}|" .env
sed -i "s|placeholder_hmac_key|${HMAC_KEY}|" .env
sed -i "s|placeholder_bybit_hmac|${BYBIT_HMAC_KEY}|" .env

echo -e "${GREEN}✅ Security keys generated${NC}"

# Generate Laravel app key
php artisan key:generate --force
echo -e "${GREEN}✅ Laravel configured${NC}"

# STEP 12: Run Migrations
echo -e "${CYAN}🗄️  STEP 12/12: Running Database Migration${NC}"
php artisan migrate --force
echo -e "${GREEN}✅ Database migrated${NC}"

# Create System Services
echo -e "${CYAN}🔄 Creating System Services${NC}"

# Queue Worker Service
cat > /etc/systemd/system/sentx-queue.service << SERVICEEOF
[Unit]
Description=SentinentX Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
SERVICEEOF

# Telegram Bot Service
cat > /etc/systemd/system/sentx-telegram.service << SERVICEEOF
[Unit]
Description=SentinentX Telegram Bot
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php artisan telegram:polling
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
SERVICEEOF

# Enable services
systemctl daemon-reload
systemctl enable sentx-queue
systemctl enable sentx-telegram

echo -e "${GREEN}✅ Services created${NC}"

echo ""
echo -e "${GREEN}🎉 INSTALLATION COMPLETED!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${CYAN}📝 NEXT STEPS:${NC}"
echo -e "${YELLOW}1. Edit .env file and add your API keys:${NC}"
echo "   nano /var/www/sentinentx/.env"
echo ""
echo -e "${YELLOW}   Required API Keys:${NC}"
echo "   - BYBIT_API_KEY=your_testnet_api_key"
echo "   - BYBIT_API_SECRET=your_testnet_secret"
echo "   - TELEGRAM_BOT_TOKEN=your_bot_token"
echo "   - TELEGRAM_CHAT_ID=your_chat_id"
echo "   - OPENAI_API_KEY=sk-your_openai_key"
echo "   - ANTHROPIC_API_KEY=sk-ant-your_claude_key"
echo "   - GEMINI_API_KEY=AIza_your_gemini_key"
echo "   - GROK_API_KEY=your_grok_key"
echo "   - COINGECKO_API_KEY=CG-your_coingecko_key"
echo ""
echo -e "${YELLOW}2. Start services:${NC}"
echo "   systemctl start sentx-queue"
echo "   systemctl start sentx-telegram"
echo ""
echo -e "${YELLOW}3. Check status:${NC}"
echo "   systemctl status sentx-queue"
echo "   systemctl status sentx-telegram"
echo ""
echo -e "${YELLOW}4. Test Telegram bot with:${NC}"
echo "   /help"
echo ""
echo -e "${GREEN}🚀 SentinentX is ready for testnet trading!${NC}"
