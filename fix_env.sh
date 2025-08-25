#!/bin/bash

# SentinentX .env Fix Script
# Bu script .env parsing hatalarını düzeltir

set -e

echo "🔧 .ENV PARSING HATASI DÜZELTİCİSİ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Laravel projesi bulunamadı!"
    echo "💡 Doğru klasöre git: cd /var/www/sentinentx"
    exit 1
fi

echo "🔍 Mevcut .env dosyasını kontrol ediyor..."

# Check if .env has bash commands
if grep -q "read -s -p" .env 2>/dev/null; then
    echo "❌ .env dosyasında bash komutları bulundu!"
    echo "🧹 .env dosyasını temizliyor..."
    
    # Backup current .env
    cp .env .env.backup 2>/dev/null || true
    
    # Remove .env and recreate from example
    rm -f .env
    
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo "✅ .env.example'dan yeni .env oluşturuldu"
    else
        echo "⚠️ .env.example bulunamadı, minimal .env oluşturuluyor..."
        cat > .env << 'EOF'
APP_NAME=SentinentX
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Europe/Istanbul
APP_URL=http://localhost

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

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

BYBIT_TESTNET=true
BYBIT_BASE_URL=https://api-testnet.bybit.com
BYBIT_API_KEY=
BYBIT_API_SECRET=

OPENAI_API_KEY=
GEMINI_API_KEY=
GROK_API_KEY=

TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

TRADING_MAX_LEVERAGE=75
COINGECKO_BASE_URL=https://api.coingecko.com/api/v3
EOF
    fi
    
    # Generate Laravel key
    echo "🔑 Laravel application key oluşturuluyor..."
    php artisan key:generate --force
    
    echo ""
    echo "✅ .env dosyası düzeltildi!"
    echo "📝 Şimdi API key'lerini manuel olarak eklemen gerekiyor:"
    echo ""
    echo "nano .env"
    echo ""
    echo "🔧 Eklemen gereken key'ler:"
    echo "- BYBIT_API_KEY=your_key_here"
    echo "- BYBIT_API_SECRET=your_secret_here"
    echo "- OPENAI_API_KEY=sk-your_key_here"
    echo "- GEMINI_API_KEY=AIzaSy_your_key_here"
    echo "- GROK_API_KEY=your_key_here"
    echo "- TELEGRAM_BOT_TOKEN=your_token_here"
    echo "- TELEGRAM_CHAT_ID=your_chat_id_here"
    
else
    echo "✅ .env dosyası temiz görünüyor"
    echo "🔍 Laravel key kontrol ediliyor..."
    
    if ! grep -q "APP_KEY=base64:" .env; then
        echo "🔑 Laravel application key oluşturuluyor..."
        php artisan key:generate --force
    fi
fi

echo ""
echo "🎯 Migration'ı tekrar dene:"
echo "php artisan migrate --force"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
