#!/bin/bash

# SentinentX Sistem Durdurma Scripti
# Kullanım: ./stop_sentinentx.sh

echo "🔴 SentinentX Sistemi durduruluyor..."
echo ""

# Tüm ilgili işlemleri durdur
echo "🛑 Servisler durduruluyor..."

# PHP Artisan işlemlerini durdur
pkill -f "php artisan queue:work" && echo "   ✅ Queue Worker durduruldu"
pkill -f "php artisan schedule:work" && echo "   ✅ Scheduler durduruldu" 
pkill -f "php artisan serve" && echo "   ✅ Laravel Server durduruldu"
pkill -f "php artisan telegram:polling" && echo "   ✅ Telegram Bot durduruldu"

# Ngrok varsa durdur
pkill -f "ngrok" 2>/dev/null && echo "   ✅ Ngrok durduruldu"

# Kısa bekleme
sleep 2

# Kalan işlemleri kontrol et
REMAINING=$(ps aux | grep "php artisan\|ngrok" | grep -v grep | wc -l)

if [ $REMAINING -eq 0 ]; then
    echo ""
    echo "🎯 SentinentX Sistemi tamamen durduruldu!"
    echo ""
    echo "🚀 Yeniden başlatmak için: ./start_sentinentx.sh"
else
    echo ""
    echo "⚠️ Bazı işlemler hala çalışıyor olabilir:"
    ps aux | grep "php artisan\|ngrok" | grep -v grep
    echo ""
    echo "🔥 Zorla durdurmak için:"
    echo "   pkill -9 -f 'php artisan'"
    echo "   pkill -9 -f 'ngrok'"
fi

echo ""
