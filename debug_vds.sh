#!/bin/bash

echo "🔍 SENTINENTX VDS DEBUG - FULL DIAGNOSTIC"
echo "========================================"
echo

echo "1. BASIC SYSTEM INFO:"
echo "- Current user: $(whoami)"
echo "- Current directory: $(pwd)"
echo "- Date: $(date)"
echo

echo "2. USER & PERMISSIONS CHECK:"
id www-data 2>/dev/null || echo "❌ www-data user not found"
echo

echo "3. DIRECTORY STRUCTURE:"
ls -la /var/www/ 2>/dev/null || echo "❌ /var/www/ not found"
ls -la /var/www/sentinentx 2>/dev/null || echo "❌ /var/www/sentinentx not found"
echo

echo "4. ENV FILES CHECK:"
ls -la /var/www/sentinentx/.env* 2>/dev/null || echo "❌ No .env files found"
if [ -f "/var/www/sentinentx/.env" ]; then
    echo "APP_KEY in .env:"
    grep APP_KEY /var/www/sentinentx/.env 2>/dev/null || echo "❌ No APP_KEY found"
fi
echo

echo "5. PHP VERSION & AVAILABILITY:"
php -v 2>/dev/null || echo "❌ PHP not found"
which php 2>/dev/null || echo "❌ PHP not in PATH"
echo

echo "6. LARAVEL ARTISAN TEST:"
if [ -d "/var/www/sentinentx" ]; then
    cd /var/www/sentinentx
    php artisan --version 2>/dev/null || echo "❌ Laravel artisan failed"
    sudo -u www-data php artisan --version 2>/dev/null || echo "❌ Laravel artisan failed as www-data"
fi
echo

echo "7. OPENSSL AVAILABILITY:"
which openssl 2>/dev/null || echo "❌ OpenSSL not found"
openssl version 2>/dev/null || echo "❌ OpenSSL version failed"
echo

echo "8. SCRIPT CONTENT CHECK:"
if [ -f "install_sentx.sh" ]; then
    echo "Script exists in current directory"
    grep -A 5 -B 2 'key:generate' install_sentx.sh 2>/dev/null || echo "❌ key:generate not found in script"
elif [ -f "/tmp/install_sentx.sh" ]; then
    echo "Script exists in /tmp"
    grep -A 5 -B 2 'key:generate' /tmp/install_sentx.sh 2>/dev/null || echo "❌ key:generate not found in script"
else
    echo "❌ install_sentx.sh not found"
fi
echo

echo "9. COMPOSER CHECK:"
if [ -d "/var/www/sentinentx" ]; then
    cd /var/www/sentinentx
    ls -la composer.* 2>/dev/null || echo "❌ Composer files not found"
    which composer 2>/dev/null || echo "❌ Composer not in PATH"
fi
echo

echo "10. DISK SPACE:"
df -h /var/2>/dev/null || echo "❌ Disk space check failed"
echo

echo "========================================"
echo "🔍 DEBUG COMPLETED - Send this output!"
echo "========================================"
