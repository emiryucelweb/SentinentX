#!/bin/bash

# SentinentX Tam Sistem Başlatma Scripti
# Kullanım: ./start_sentinentx.sh

echo "🚀 SentinentX Sistemi başlatılıyor..."
echo ""

# Proje dizinine git
cd /home/emir/Desktop/sentinentx

# Önceki işlemleri temizle
echo "🧹 Önceki işlemler temizleniyor..."
pkill -f "php artisan" 2>/dev/null
pkill -f "ngrok" 2>/dev/null
sleep 2

# 1. Queue Worker (arka plan işleri)
echo "⚙️ Queue Worker başlatılıyor..."
php artisan queue:work --daemon --sleep=3 --tries=3 --max-time=3600 &
QUEUE_PID=$!

# 2. Scheduler (otomatik görevler)
echo "📅 Scheduler başlatılıyor..."
php artisan schedule:work &
SCHEDULER_PID=$!

# 3. Laravel Development Server
echo "🌐 Laravel Server başlatılıyor..."
php artisan serve --host=0.0.0.0 --port=8000 &
SERVER_PID=$!

# 4. Telegram Bot Polling
echo "🤖 Telegram Bot başlatılıyor..."
nohup php artisan telegram:polling > /dev/null 2>&1 &
TELEGRAM_PID=$!

# Kısa bekleme
sleep 3

echo ""
echo "🎉 SentinentX Sistemi başarıyla başlatıldı!"
echo ""
echo "📊 Başlatılan Servisler:"
echo "   ⚙️ Queue Worker (PID: $QUEUE_PID)"
echo "   📅 Scheduler (PID: $SCHEDULER_PID)" 
echo "   🌐 Laravel Server (PID: $SERVER_PID)"
echo "   🤖 Telegram Bot (PID: $TELEGRAM_PID)"
echo ""
echo "🔗 Erişim Bilgileri:"
echo "   📱 Telegram: Botunuza 'selam canım' yazarak test edin"
echo "   🌐 Web: http://localhost:8000"
echo ""
echo "📋 Test Komutları:"
echo "   /status - Sistem durumu"
echo "   /balance - Bakiye bilgisi"
echo "   /positions - Açık pozisyonlar"
echo "   /help - Tüm komutlar"
echo ""
echo "🛑 Sistemi durdurmak için: ./stop_sentinentx.sh"
echo ""
echo "✅ Sistem hazır! Telegram'dan test edebilirsiniz."
