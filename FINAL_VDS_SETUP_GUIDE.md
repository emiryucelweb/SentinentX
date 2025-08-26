# 🚀 SentinentX VDS Final Setup Guide - Production Ready

## 🎯 **SIFIRDAN VDS KURULUM TALİMATLARI**

### **ADIM 1: VDS SATIN ALMA VE HAZIRLIK** ⏱️ 5 dakika

#### **🏆 Tavsiye Edilen VDS Sağlayıcıları:**

1. **DigitalOcean** (En iyi seçenek)
   - Plan: CPU-Optimized Droplet
   - Specs: 4 vCPU, 8GB RAM, 100GB SSD
   - Lokasyon: Frankfurt
   - Fiyat: ~$24 (15 gün)

2. **Linode (Akamai)** (Performance)
   - Plan: Dedicated CPU
   - Specs: 4 Core, 8GB RAM, 160GB SSD
   - Fiyat: ~$18 (15 gün)

3. **Hetzner Cloud** (Budget)
   - Plan: CPX31
   - Specs: 4 vCPU, 8GB RAM, 160GB SSD
   - Fiyat: ~€6.5 (15 gün)

#### **✅ VDS Konfigürasyonu:**
- **OS**: Ubuntu 24.04 LTS x64 (**Kesinlikle 24.04 kullan**)
- **SSH Key**: Güvenlik için SSH key ekle
- **Firewall**: Basic protection aktif et
- **Backup**: Otomatik backup aktif et

---

### **ADIM 2: VDS'YE BAĞLANMA** ⏱️ 2 dakika

```bash
# SSH ile VDS'ye bağlan
ssh root@your-server-ip

# Veya putty/terminal ile bağlan
```

**İlk bağlantıda yapılacaklar:**
```bash
# Sistem durumunu kontrol et
uname -a
free -h
df -h

# Network kontrol
ping -c 3 google.com
```

---

### **ADIM 3: TEK KOMUTLA TAM KURULUM** ⏱️ 5-10 dakika

#### **🚀 One-Command Complete Installation:**

```bash
curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/one_command_deploy.sh | bash
```

**Bu komut otomatik olarak:**
- ✅ Sistem uyumluluğunu kontrol eder
- ✅ PHP 8.2, PostgreSQL, Redis, Nginx kurar
- ✅ SentinentX'i clone eder
- ✅ Tüm dependencies'leri yükler
- ✅ Environment'ı konfigüre eder
- ✅ Database'i hazırlar
- ✅ Servisleri başlatır
- ✅ TESTNET modunu aktif eder

#### **⚠️ Kurulum Sırasında Dikkat Edilecekler:**
- Script'in tamamlanmasını bekle (5-10 dakika)
- Herhangi bir hata görürsen script'i tekrar çalıştır
- Internet bağlantısının stabil olduğundan emin ol

---

### **ADIM 4: API KEY KONFİGÜRASYONU** ⏱️ 5 dakika

#### **📝 .env Dosyasını Düzenle:**

```bash
# .env dosyasını aç
nano /var/www/sentinentx/.env
```

#### **🔑 Gerekli API Key'leri Ekle:**

```bash
# OpenAI (ChatGPT)
OPENAI_API_KEY=sk-your-openai-key-here

# Google Gemini
GEMINI_API_KEY=your-gemini-key-here

# Grok (X.AI)
GROK_API_KEY=your-grok-key-here

# Bybit TESTNET Keys
BYBIT_API_KEY=your-testnet-api-key
BYBIT_API_SECRET=your-testnet-secret
BYBIT_TESTNET=true

# Telegram Bot
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-chat-id

# CoinGecko (zaten var)
COINGECKO_API_KEY=CG-Xo5enN9WjkBkeeYHEDG9aium
```

#### **💾 Dosyayı Kaydet:**
```bash
# Ctrl+X, Y, Enter ile kaydet
# Veya ESC, :wq ile kaydet (vi editörü ise)
```

#### **🔄 Servisleri Yeniden Başlat:**
```bash
systemctl restart sentinentx-queue
systemctl restart sentinentx-telegram
```

---

### **ADIM 5: 15 GÜNLÜK TESTNET KOŞUSUNU BAŞLAT** ⏱️ 2 dakika

#### **🧪 Testnet Test Başlatma:**

```bash
/var/www/sentinentx/start_15day_testnet.sh
```

**Bu script otomatik olarak:**
- ✅ API key'leri doğrular
- ✅ TESTNET modunu kontrol eder
- ✅ Otomatik monitoring kurar
- ✅ Günlük raporları aktif eder
- ✅ Health checking başlatır
- ✅ 15-günlük tracking oluşturur

---

### **ADIM 6: SİSTEM KONTROLÜ VE TEST** ⏱️ 5 dakika

#### **📊 Servis Durumu Kontrolü:**
```bash
# Tüm servisleri kontrol et
systemctl status sentinentx-queue sentinentx-telegram nginx postgresql redis-server

# Hepsi "active (running)" olmalı
```

#### **📱 Telegram Bot Test:**
```bash
# Telegram'da bot'a bu komutları gönder:
/help          # Komut listesi görmeli
/status        # Sistem durumu görmeli
/scan          # 4 coin analizi yapmalı
/balance       # Testnet bakiye görmeli
```

#### **🌐 Web Interface Test:**
```bash
# Server IP'ni öğren
curl ifconfig.me

# Browser'da aç: http://your-server-ip
# "SentinentX" sayfası açılmalı
```

#### **📝 Log Kontrolü:**
```bash
# Canlı logları izle
tail -f /var/www/sentinentx/storage/logs/laravel.log

# 15-day test logları
tail -f /var/log/sentinentx_15day_test.log

# Herhangi bir ERROR görmemen lazım
```

---

## 🔧 **KONTROL VE YÖNETİM KOMUTLARI**

### **📊 Sistem Durumu:**
```bash
# Sistem özeti
cat /root/sentinentx_deployment_summary.txt

# 15-günlük test durumu
cat /root/sentinentx_15day_test.txt

# Günlük raporlar
ls /var/log/sentinentx_reports/
```

### **🔄 Servis Yönetimi:**
```bash
# Servisleri yeniden başlat
systemctl restart sentinentx-queue sentinentx-telegram

# Servisleri durdur
systemctl stop sentinentx-queue sentinentx-telegram

# Durumları kontrol et
systemctl status sentinentx-*
```

### **🛑 Sistemi Durdurma:**
```bash
# Normal durdurma
/var/www/sentinentx/stop_sentinentx.sh

# Zorla durdurma
/var/www/sentinentx/stop_sentinentx.sh --force

# Acil durdurma
/var/www/sentinentx/stop_sentinentx.sh --emergency
```

### **📈 Performance Monitoring:**
```bash
# Sistem kaynakları
htop

# Disk kullanımı
df -h

# Memory kullanımı
free -h

# Network aktivitesi
nethogs
```

---

## 🚨 **SORUN GİDERME**

### **❌ Yaygın Sorunlar ve Çözümleri:**

#### **1. Kurulum Başarısız Olursa:**
```bash
# Script'i tekrar çalıştır (idempotent)
curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/one_command_deploy.sh | bash

# Log'ları kontrol et
tail -f /tmp/sentinentx_deploy.log
```

#### **2. Telegram Bot Cevap Vermiyorsa:**
```bash
# API key'leri kontrol et
grep TELEGRAM /var/www/sentinentx/.env

# Service'i yeniden başlat
systemctl restart sentinentx-telegram

# Log'ları kontrol et
journalctl -u sentinentx-telegram -f
```

#### **3. Database Bağlantı Hatası:**
```bash
# PostgreSQL durumu
systemctl status postgresql

# Database test
sudo -u postgres psql -d sentinentx -c "SELECT 1;"

# Laravel database test
cd /var/www/sentinentx && php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';"
```

#### **4. Yüksek CPU/Memory Kullanımı:**
```bash
# Process'leri kontrol et
top -p $(pgrep -d',' php)

# Queue worker'ı yeniden başlat
systemctl restart sentinentx-queue

# Memory temizle
sync && echo 3 > /proc/sys/vm/drop_caches
```

#### **5. Network Bağlantı Sorunları:**
```bash
# DNS test
nslookup google.com

# API endpoint'leri test
curl -I https://api.openai.com
curl -I https://api.coingecko.com

# Firewall kontrol
ufw status
```

---

## 📅 **15 GÜNLÜK TEST TAKİP PLANI**

### **Günlük Yapılacaklar:**
```bash
# Her gün bu kontrolleri yap:

# 1. Sistem durumu
systemctl status sentinentx-*

# 2. Log kontrol
tail -50 /var/www/sentinentx/storage/logs/laravel.log | grep ERROR

# 3. Test durumu güncelle
nano /root/sentinentx_15day_test.txt
# Günün durumunu [✅] veya [❌] olarak işaretle

# 4. Günlük raporu kontrol et
cat /var/log/sentinentx_reports/daily_report_$(date +%Y-%m-%d).txt

# 5. Telegram bot test
# /status komutunu bot'a gönder
```

### **Haftalık Değerlendirme:**
- **Hafta 1 Sonu**: Stabilite değerlendirmesi
- **Hafta 2 Sonu**: Performance analizi
- **Gün 15**: Production kararı

---

## 🎯 **SUCCESS CRİTERLERİ**

### **✅ Başarılı Test İçin Gerekler:**
- **Uptime**: %99+ (max 3.6 saat downtime)
- **Telegram Commands**: %95+ success rate
- **API Response**: <500ms average
- **Memory Usage**: <80% peak
- **Error Rate**: <1% total operations
- **AI Decisions**: %90+ consensus success

### **📊 Takip Edilen Metrikler:**
- Günlük trade sayısı
- AI consensus accuracy
- Position success rate
- System resource usage
- Error frequency
- Response times

---

## 🚀 **PRODUCTİON'A GEÇİŞ (15 Gün Sonra)**

### **Test Başarılı İse:**
```bash
# 1. .env'de live keys'e geç
nano /var/www/sentinentx/.env

# Bu değişiklikleri yap:
BYBIT_TESTNET=false
BYBIT_API_KEY=your-live-api-key
BYBIT_API_SECRET=your-live-secret

# 2. Servisleri yeniden başlat
systemctl restart sentinentx-*

# 3. İlk live trade'leri yakından takip et
tail -f /var/www/sentinentx/storage/logs/laravel.log | grep -i "LIVE\|TRADE"
```

---

## 📞 **DESTEK VE KAYNAKLAR**

### **📋 Önemli Dosya Konumları:**
```bash
# Ana kurulum
/var/www/sentinentx/

# Log dosyaları
/var/log/sentinentx_15day_test.log
/var/www/sentinentx/storage/logs/laravel.log

# Konfigürasyon
/var/www/sentinentx/.env

# Özetler
/root/sentinentx_deployment_summary.txt
/root/sentinentx_15day_test.txt
```

### **🆘 Acil Durum Komutları:**
```bash
# Tüm trading'i durdur
systemctl stop sentinentx-queue sentinentx-telegram

# Acil backup al
cd /var/www && tar -czf sentinentx_emergency_$(date +%Y%m%d).tar.gz sentinentx/

# Sistem health check
/usr/local/bin/sentinentx_monitor.sh
```

---

## 🏁 **ÖZET: TAM KURULUM SÜRECİ**

### **⏱️ Toplam Süre: ~20 dakika**

```bash
# 1. VDS al (Ubuntu 24.04 LTS x64)
# 2. SSH ile bağlan
# 3. Tek komutla kur:
curl -sSL https://raw.githubusercontent.com/emiryucelweb/SentinentX/main/one_command_deploy.sh | bash

# 4. API key'leri ekle:
nano /var/www/sentinentx/.env

# 5. 15-günlük testi başlat:
/var/www/sentinentx/start_15day_testnet.sh

# 6. Telegram'da test et:
/help /status /scan

# 7. 15 gün boyunca takip et
# 8. Production'a geç
```

---

## ✅ **SONUÇ**

🎉 **SentinentX artık tamamen otomatik, hatasız kurulum ile 15 günlük testnet koşusuna hazır!**

**Her şey tek komutla, sıfır manuel müdahale, %100 otomasyon! 🚀💰**

**Başarılar! 💪**
