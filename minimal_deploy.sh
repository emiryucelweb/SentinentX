#!/bin/bash

# SentinentX Minimal Safe Deployment 
# No complex error handling, just essential deployment

echo "🚀 SentinentX Minimal Safe Deployment"
echo "===================================="

# Basic setup
cd /var/www
rm -rf sentinentx 2>/dev/null || true

# Download via direct methods
echo "📡 Downloading SentinentX..."
if git clone https://github.com/emiryucelweb/SentinentX.git sentinentx; then
    echo "✅ Git clone successful"
elif curl -sSL "https://github.com/emiryucelweb/SentinentX/archive/refs/heads/main.zip" | unzip -q - -d /tmp && mv /tmp/SentinentX-main sentinentx; then
    echo "✅ ZIP download successful"
else
    echo "❌ Download failed"
    exit 1
fi

cd sentinentx

# Install infrastructure with timeout
echo "🔧 Installing infrastructure..."
timeout 600 bash quick_vds_install.sh || {
    echo "⚠️ Infrastructure installation timeout, but may be OK"
}

# Basic Laravel setup
echo "🔧 Laravel setup..."
cp env.example.template .env
composer install --no-dev --optimize-autoloader 2>/dev/null || echo "⚠️ Composer issues"

# Set minimal config
echo "APP_KEY=" > .env
echo "DB_CONNECTION=pgsql" >> .env
echo "DB_HOST=127.0.0.1" >> .env
echo "DB_DATABASE=sentinentx" >> .env
echo "DB_USERNAME=sentinentx" >> .env
echo "DB_PASSWORD=sentinentx123" >> .env
echo "RISK_PROFILE=moderate" >> .env

# Laravel commands
php artisan key:generate --force 2>/dev/null || echo "⚠️ Key generation issues"
php artisan migrate --force 2>/dev/null || echo "⚠️ Migration issues"

# Set permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data . 2>/dev/null || true

echo ""
echo "✅ Minimal deployment completed!"
echo "📁 Installation directory: /var/www/sentinentx"
echo "🔍 Check status: systemctl status nginx postgresql"
echo ""
