#!/bin/bash

# SentinentX Config Cache Fix Script
# Bu script Laravel config cache hatalarını düzeltir

set -e

echo "🔧 CONFIG CACHE HATASI DÜZELTİCİSİ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Laravel projesi bulunamadı!"
    echo "💡 Doğru klasöre git: cd /var/www/sentinentx"
    exit 1
fi

echo "🧹 Laravel cache'lerini temizliyor..."

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✅ Cache'ler temizlendi"

# Check for problematic config files
echo "🔍 Problematik config dosyalarını kontrol ediyor..."

# Check logging config (common culprit)
if [ -f "config/logging.php" ]; then
    if grep -q "function\|Closure\|\$" config/logging.php; then
        echo "⚠️ config/logging.php'de Closure bulundu, düzeltiliyor..."
        
        # Backup original
        cp config/logging.php config/logging.php.backup
        
        # Create clean logging config
        cat > config/logging.php << 'EOF'
<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],
        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],
        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],
        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],
];
EOF
        echo "✅ config/logging.php düzeltildi"
    fi
fi

# Check queue config
if [ -f "config/queue.php" ]; then
    if grep -q "function\|Closure" config/queue.php; then
        echo "⚠️ config/queue.php'de Closure bulundu, kontrol ediliyor..."
    fi
fi

echo ""
echo "🧪 Config cache test ediliyor..."

# Try to cache config
if php artisan config:cache; then
    echo "✅ Config cache başarılı!"
else
    echo "❌ Config cache hala başarısız, manual kontrol gerekli"
    echo ""
    echo "🔍 Problematik dosyaları bulmak için:"
    echo "grep -r 'function\\|Closure\\|\\\$' config/ || true"
    echo ""
    echo "💡 Geçici çözüm: Config cache kullanma"
    echo "php artisan config:clear"
fi

echo ""
echo "🎯 Kuruluma devam et:"
echo "php artisan migrate --force"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
