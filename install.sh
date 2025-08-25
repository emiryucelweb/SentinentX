#!/bin/bash

# ========================================
# SENTINENTX VDS AUTO INSTALLER
# ========================================
# 🚀 AI-Powered Cryptocurrency Trading Bot
# 📡 Public Repository Installation Script
# 
# Usage:
# curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/install.sh | bash
# 
# Or download and run:
# wget https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/install.sh
# chmod +x install.sh
# ./install.sh

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Welcome banner
echo -e "${PURPLE}"
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                    🚀 SENTINENTX INSTALLER                   ║"
echo "║                                                               ║"
echo "║  AI-Powered Cryptocurrency Trading Bot                       ║"
echo "║  Automated VDS Setup & Deployment                            ║"
echo "║                                                               ║"
echo "║  🤖 2-Stage AI Consensus (OpenAI + Gemini + Grok)           ║"
echo "║  ⚡ Bybit Testnet Integration                                ║"
echo "║  📱 Telegram Bot Interface                                   ║"
echo "║  🔬 15-Day LAB Backtesting                                   ║"
echo "║                                                               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${CYAN}🎯 SentinentX Auto Installation Starting...${NC}"
echo -e "${YELLOW}⏰ Estimated time: 10-15 minutes${NC}"
echo -e "${BLUE}🌐 Repository: https://github.com/emiryucelweb/SentinentX${NC}"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ This script must be run as root!${NC}"
   echo -e "${YELLOW}💡 Run with: sudo bash install.sh${NC}"
   exit 1
fi

# System information
echo -e "${CYAN}📊 System Information:${NC}"
echo "OS: $(cat /etc/os-release | grep PRETTY_NAME | cut -d'"' -f2)"
echo "Kernel: $(uname -r)"
echo "Architecture: $(uname -m)"
echo ""

# Get API keys interactively
echo -e "${BLUE}🔑 CONFIGURATION SETUP${NC}"
echo -e "${YELLOW}💡 Enter your API keys (you can leave empty and configure later)${NC}"
echo -e "${YELLOW}💡 Press Enter to skip any field${NC}"
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
echo -e "${GREEN}✅ Configuration collected! Starting installation...${NC}"
echo ""

# Create log file
LOGFILE="/var/log/sentinentx_install.log"
echo "📜 Installation log: $LOGFILE"
exec 1> >(tee -a $LOGFILE)
exec 2> >(tee -a $LOGFILE >&2)

# Step 1: System Update
echo -e "${CYAN}📦 STEP 1/12: System Update${NC}"
apt update -y
apt upgrade -y
echo -e "${GREEN}✅ System updated${NC}"

# Step 2: Essential Packages
echo -e "${CYAN}📦 STEP 2/12: Installing Essential Packages${NC}"
apt install -y curl wget git unzip software-properties-common ca-certificates gnupg lsb-release
echo -e "${GREEN}✅ Essential packages installed${NC}"

# Step 3: PHP 8.2
echo -e "${CYAN}🐘 STEP 3/12: Installing PHP 8.2${NC}"
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-pgsql php8.2-xml php8.2-curl \
    php8.2-zip php8.2-mbstring php8.2-bcmath php8.2-gd php8.2-redis php8.2-intl

# Configure PHP timezone
sed -i 's/;date.timezone =/date.timezone = Europe\/Istanbul/' /etc/php/8.2/cli/php.ini
sed -i 's/;date.timezone =/date.timezone = Europe\/Istanbul/' /etc/php/8.2/fpm/php.ini

echo -e "${GREEN}✅ PHP 8.2 installed and configured${NC}"

# Step 4: Composer
echo -e "${CYAN}🎼 STEP 4/12: Installing Composer${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
echo -e "${GREEN}✅ Composer installed${NC}"

# Step 5: PostgreSQL
echo -e "${CYAN}🐘 STEP 5/12: Installing PostgreSQL${NC}"
apt install -y postgresql postgresql-contrib
systemctl start postgresql
systemctl enable postgresql

# Create database and user
sudo -u postgres createuser sentx 2>/dev/null || true
sudo -u postgres createdb sentx 2>/dev/null || true
sudo -u postgres psql -c "ALTER USER sentx PASSWORD 'sentx123';" 2>/dev/null || true
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentx TO sentx;" 2>/dev/null || true

echo -e "${GREEN}✅ PostgreSQL installed and configured${NC}"

# Step 6: Redis
echo -e "${CYAN}🔴 STEP 6/12: Installing Redis${NC}"
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# Test Redis
if redis-cli ping | grep -q "PONG"; then
    echo -e "${GREEN}✅ Redis installed and running${NC}"
else
    echo -e "${RED}❌ Redis installation failed${NC}"
    exit 1
fi

# Step 7: Download Project
echo -e "${CYAN}📁 STEP 7/12: Downloading SentinentX Project${NC}"
echo -e "${BLUE}🌐 Cloning from: https://github.com/emiryucelweb/SentinentX.git${NC}"

mkdir -p /var/www/sentinentx
cd /var/www/sentinentx

# Clean directory if not empty
if [ "$(ls -A /var/www/sentinentx)" ]; then
    rm -rf /var/www/sentinentx/*
fi

# Clone project
git clone https://github.com/emiryucelweb/SentinentX.git .
echo -e "${GREEN}✅ Project downloaded successfully${NC}"

# Step 8: Set Permissions
echo -e "${CYAN}👤 STEP 8/12: Setting Permissions${NC}"
useradd -r -s /bin/false www-data 2>/dev/null || true
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
chmod -R 775 /var/www/sentinentx/storage
chmod -R 775 /var/www/sentinentx/bootstrap/cache
echo -e "${GREEN}✅ Permissions configured${NC}"

# Step 9: Install Dependencies
echo -e "${CYAN}📦 STEP 9/12: Installing PHP Dependencies${NC}"
cd /var/www/sentinentx
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✅ Dependencies installed${NC}"

# Step 10: Laravel Configuration
echo -e "${CYAN}🔧 STEP 10/12: Configuring Laravel${NC}"

# Create .env file
cp .env.example .env
php artisan key:generate --force

# Configure .env with user inputs
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

# Bybit Testnet Configuration
BYBIT_TESTNET=true
BYBIT_API_KEY=${BYBIT_API_KEY}
BYBIT_API_SECRET=${BYBIT_API_SECRET}

# AI Providers Configuration
OPENAI_API_KEY=${OPENAI_API_KEY}
GEMINI_API_KEY=${GEMINI_API_KEY}
GROK_API_KEY=${GROK_API_KEY}

# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=${TELEGRAM_BOT_TOKEN}
TELEGRAM_CHAT_ID=${TELEGRAM_CHAT_ID}

# Trading Configuration
TRADING_MAX_LEVERAGE=75
TRADING_MODE_ONE_WAY=true
TRADING_MARGIN_MODE=cross
EOF

echo -e "${GREEN}✅ Laravel configured${NC}"

# Step 11: Database Migration
echo -e "${CYAN}🗄️ STEP 11/12: Running Database Migration${NC}"
php artisan migrate --force
echo -e "${GREEN}✅ Database tables created${NC}"

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 12: Create System Services
echo -e "${CYAN}🔄 STEP 12/12: Creating System Services${NC}"

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

# Scheduler Service (optional)
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

# Enable and start services
systemctl daemon-reload
systemctl enable sentx-queue sentx-telegram sentx-scheduler
systemctl start sentx-queue sentx-telegram sentx-scheduler

echo -e "${GREEN}✅ System services created and started${NC}"

# Create Management Scripts
echo -e "${CYAN}📜 Creating Management Scripts${NC}"

cat > /var/www/sentinentx/start.sh << 'EOF'
#!/bin/bash
echo "🚀 Starting SentinentX services..."
systemctl start sentx-queue sentx-telegram sentx-scheduler
echo "✅ All services started!"
systemctl status sentx-queue sentx-telegram sentx-scheduler --no-pager
EOF

cat > /var/www/sentinentx/stop.sh << 'EOF'
#!/bin/bash
echo "🛑 Stopping SentinentX services..."
systemctl stop sentx-queue sentx-telegram sentx-scheduler
echo "✅ All services stopped!"
EOF

cat > /var/www/sentinentx/status.sh << 'EOF'
#!/bin/bash
echo "📊 SentinentX System Status"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Service Status
for service in sentx-queue sentx-telegram sentx-scheduler; do
    if systemctl is-active --quiet $service; then
        echo "✅ $service: Running"
    else
        echo "❌ $service: Stopped"
    fi
done

echo ""

# Database Status
DB_STATUS=$(cd /var/www/sentinentx && php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Connected'; } catch(Exception \$e) { echo 'Error'; }" 2>/dev/null)
echo "🗄️ Database: $DB_STATUS"

# Redis Status
REDIS_STATUS=$(redis-cli ping 2>/dev/null || echo 'Error')
echo "🔴 Redis: $REDIS_STATUS"

echo ""
echo "📁 Project Path: /var/www/sentinentx"
echo "📜 Log File: /var/log/sentinentx_install.log"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
EOF

cat > /var/www/sentinentx/test.sh << 'EOF'
#!/bin/bash
echo "🧪 SentinentX System Test"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

cd /var/www/sentinentx

echo "1️⃣ Testing system components..."
php artisan sentx:system-check

echo ""
echo "2️⃣ Testing Telegram bot..."
echo "💡 Send /start to your Telegram bot to test"

echo ""
echo "3️⃣ Available commands:"
echo "• php artisan sentx:risk-profile    - Set risk level"
echo "• php artisan sentx:lab-start       - Start LAB simulation"
echo "• php artisan sentx:scan            - Run market scan"
echo "• php artisan sentx:lab-monitor     - Monitor LAB performance"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
EOF

chmod +x /var/www/sentinentx/*.sh

echo -e "${GREEN}✅ Management scripts created${NC}"

# System Tests
echo -e "${CYAN}🔍 Running System Tests${NC}"

# Test database connection
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q "OK"; then
    echo -e "${GREEN}✅ Database connection: OK${NC}"
else
    echo -e "${RED}❌ Database connection: Failed${NC}"
fi

# Test Redis connection
if redis-cli ping 2>/dev/null | grep -q "PONG"; then
    echo -e "${GREEN}✅ Redis connection: OK${NC}"
else
    echo -e "${RED}❌ Redis connection: Failed${NC}"
fi

# Service status check
echo -e "${CYAN}📊 Service Status:${NC}"
for service in sentx-queue sentx-telegram sentx-scheduler; do
    if systemctl is-active --quiet $service; then
        echo -e "${GREEN}✅ $service: Running${NC}"
    else
        echo -e "${RED}❌ $service: Failed${NC}"
    fi
done

# Final Success Message
echo ""
echo -e "${PURPLE}╔═══════════════════════════════════════════════════════════════╗"
echo -e "║                    🎉 INSTALLATION COMPLETED!                ║"
echo -e "╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}🚀 SentinentX has been successfully installed and configured!${NC}"
echo ""
echo -e "${CYAN}📋 NEXT STEPS:${NC}"
echo -e "${YELLOW}1️⃣ Set risk profile:${NC} php artisan sentx:risk-profile"
echo -e "${YELLOW}2️⃣ Start LAB simulation:${NC} php artisan sentx:lab-start --days=15 --initial-balance=1000"
echo -e "${YELLOW}3️⃣ Test Telegram bot:${NC} Send /start message to your bot"
echo -e "${YELLOW}4️⃣ Run first market scan:${NC} php artisan sentx:scan"
echo ""
echo -e "${CYAN}🔧 MANAGEMENT COMMANDS:${NC}"
echo -e "${YELLOW}• Start services:${NC} ./start.sh"
echo -e "${YELLOW}• Stop services:${NC} ./stop.sh"
echo -e "${YELLOW}• Check status:${NC} ./status.sh"
echo -e "${YELLOW}• Run tests:${NC} ./test.sh"
echo ""
echo -e "${CYAN}📁 PROJECT LOCATION:${NC} /var/www/sentinentx"
echo -e "${CYAN}📜 INSTALLATION LOG:${NC} $LOGFILE"
echo -e "${CYAN}🌐 REPOSITORY:${NC} https://github.com/emiryucelweb/SentinentX"
echo ""
echo -e "${GREEN}✨ Ready for testnet trading! Good luck! ✨${NC}"
echo ""

# Run final status check
cd /var/www/sentinentx
./status.sh

exit 0
