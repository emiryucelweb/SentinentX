#!/bin/bash

# SentinentX Quick Install Script
# Usage: bash quick_install.sh

set -e

echo "🚀 SentinentX Quick Installation Starting..."
echo "⏰ Estimated time: 10-15 minutes"
echo ""

# Check root
if [[ $EUID -ne 0 ]]; then
   echo "❌ This script must be run as root!"
   exit 1
fi

# Get API keys
echo "🔑 Enter your API keys (leave empty to configure later):"
read -p "📡 Bybit Testnet API Key: " BYBIT_API_KEY
read -s -p "🔐 Bybit Testnet Secret: " BYBIT_API_SECRET
echo ""
read -p "🤖 OpenAI API Key: " OPENAI_API_KEY
read -p "🧠 Gemini API Key: " GEMINI_API_KEY
read -p "🚀 Grok API Key: " GROK_API_KEY
read -p "📱 Telegram Bot Token: " TELEGRAM_BOT_TOKEN
read -p "💬 Telegram Chat ID: " TELEGRAM_CHAT_ID

echo "✅ Starting installation..."

# System update
echo "📦 Updating system..."
apt update -y && apt upgrade -y

# Install packages
echo "📦 Installing packages..."
apt install -y curl wget git unzip software-properties-common

# PHP 8.2
echo "🐘 Installing PHP 8.2..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-pgsql php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring php8.2-bcmath php8.2-gd php8.2-redis php8.2-intl

# Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# PostgreSQL
echo "🐘 Installing PostgreSQL..."
apt install -y postgresql postgresql-contrib
systemctl start postgresql
systemctl enable postgresql
sudo -u postgres createuser sentx 2>/dev/null || true
sudo -u postgres createdb sentx 2>/dev/null || true
sudo -u postgres psql -c "ALTER USER sentx PASSWORD 'sentx123';" 2>/dev/null || true
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sentx TO sentx;" 2>/dev/null || true

# Redis
echo "🔴 Installing Redis..."
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

# Project
echo "📁 Downloading project..."
mkdir -p /var/www/sentinentx
cd /var/www/sentinentx
rm -rf /var/www/sentinentx/*
git clone https://github.com/emiryucelweb/SentinentX.git .

# Permissions
echo "👤 Setting permissions..."
useradd -r -s /bin/false www-data 2>/dev/null || true
chown -R www-data:www-data /var/www/sentinentx
chmod -R 755 /var/www/sentinentx
chmod -R 775 /var/www/sentinentx/storage
chmod -R 775 /var/www/sentinentx/bootstrap/cache

# Dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Laravel setup
echo "🔧 Configuring Laravel..."
cp .env.example .env
php artisan key:generate --force

# Configure .env
cat > .env << EOF
APP_NAME=SentinentX
APP_ENV=production
APP_KEY=$(php artisan --no-ansi key:generate --show)
APP_DEBUG=false
APP_TIMEZONE=Europe/Istanbul
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sentx
DB_USERNAME=sentx
DB_PASSWORD=sentx123

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

BYBIT_TESTNET=true
BYBIT_API_KEY=${BYBIT_API_KEY}
BYBIT_API_SECRET=${BYBIT_API_SECRET}

OPENAI_API_KEY=${OPENAI_API_KEY}
GEMINI_API_KEY=${GEMINI_API_KEY}
GROK_API_KEY=${GROK_API_KEY}

TELEGRAM_BOT_TOKEN=${TELEGRAM_BOT_TOKEN}
TELEGRAM_CHAT_ID=${TELEGRAM_CHAT_ID}
EOF

# Database migration
echo "🗄️ Running database migration..."
php artisan migrate --force

# Cache
php artisan config:cache

# Systemd services
echo "🔄 Creating systemd services..."

# Queue service
cat > /etc/systemd/system/sentx-queue.service << EOF
[Unit]
Description=SentinentX Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/sentinentx
ExecStart=/usr/bin/php /var/www/sentinentx/artisan queue:work --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
EOF

# Telegram service
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

[Install]
WantedBy=multi-user.target
EOF

# Start services
systemctl daemon-reload
systemctl enable sentx-queue sentx-telegram
systemctl start sentx-queue sentx-telegram

# Create management scripts
cat > /var/www/sentinentx/start.sh << 'EOF'
#!/bin/bash
systemctl start sentx-queue sentx-telegram
echo "✅ Services started!"
EOF

cat > /var/www/sentinentx/stop.sh << 'EOF'
#!/bin/bash
systemctl stop sentx-queue sentx-telegram
echo "✅ Services stopped!"
EOF

cat > /var/www/sentinentx/status.sh << 'EOF'
#!/bin/bash
echo "📊 SentinentX Status:"
systemctl status sentx-queue --no-pager
systemctl status sentx-telegram --no-pager
EOF

chmod +x /var/www/sentinentx/*.sh

echo ""
echo "🎉 INSTALLATION COMPLETED!"
echo ""
echo "📋 Next steps:"
echo "1. Set risk profile: php artisan sentx:risk-profile"
echo "2. Start LAB test: php artisan sentx:lab-start --days=15 --initial-balance=1000"
echo "3. Test Telegram bot: Send /start to your bot"
echo "4. Run first scan: php artisan sentx:scan"
echo ""
echo "🔧 Management commands:"
echo "• Start services: ./start.sh"
echo "• Stop services: ./stop.sh"
echo "• Check status: ./status.sh"
echo ""
echo "📁 Project location: /var/www/sentinentx"
echo ""
echo "🚀 SentinentX is ready for testnet trading!"

cd /var/www/sentinentx
./status.sh
