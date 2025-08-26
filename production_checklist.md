# SentinentX Production Checklist - 15 Günlük Test

## 🚀 VDS Seçimi ve Kurulum

### ✅ Tavsiye Edilen VDS Sağlayıcıları

#### 🏆 **En İyi Seçim: DigitalOcean**
- **Plan**: CPU-Optimized Droplet
- **Specs**: 4 vCPU, 8GB RAM, 100GB SSD
- **Lokasyon**: Frankfurt (düşük latency)
- **Fiyat**: ~$24 (15 gün)
- **Avantajlar**: Trading bot'lar için optimize edilmiş

#### 🥈 **Alternatif: Linode (Akamai)**
- **Plan**: Dedicated CPU
- **Specs**: 4 Core, 8GB RAM, 160GB SSD
- **Fiyat**: ~$18 (15 gün)
- **Avantaj**: En hızlı network (40Gbps)

#### 🥉 **Budget Seçenek: Hetzner Cloud**
- **Plan**: CPX31
- **Specs**: 4 vCPU, 8GB RAM, 160GB SSD
- **Fiyat**: ~€6.5 (15 gün)
- **Avantaj**: En uygun fiyat

---

## 📋 Kurulum Adımları

### 1. **VDS Hazırlığı** ⏱️ 5 dakika
```bash
# SSH bağlantısı
ssh root@your-server-ip

# Sistem güncelleme
apt update && apt upgrade -y
```

### 2. **Otomatik Kurulum** ⏱️ 10 dakika
```bash
# Quick install script kullan
curl -sSL https://raw.githubusercontent.com/your-repo/sentinentx/main/quick_vds_install.sh | bash
```

### 3. **Manuel Kurulum** ⏱️ 30 dakika
- VDS_DEPLOYMENT_GUIDE.md'yi takip et
- Adım adım kurulum

---

## 🔧 Konfigürasyon Checklist

### ✅ **Environment Variables (.env)**

#### 🔥 **Kritik Ayarlar**
```env
# Production mode
APP_ENV=production
APP_DEBUG=false

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=sentinentx
DB_USERNAME=sentinentx_user
DB_PASSWORD=güvenli-şifre-buraya

# Redis Cache
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis-şifre-buraya

# Security
HMAC_SECRET=32-karakter-random-key
IP_ALLOWLIST="127.0.0.1/32,home-ip/32"
```

#### 🤖 **AI Provider Keys (Test)**
```env
# OpenAI (GPT-4)
OPENAI_API_KEY=sk-your-test-key
OPENAI_MODEL=gpt-4-turbo-preview

# Google Gemini
GEMINI_API_KEY=your-gemini-test-key
GEMINI_MODEL=gemini-pro

# Grok
GROK_API_KEY=your-grok-test-key
GROK_MODEL=grok-2-1212
```

#### 🏦 **Bybit Integration (TESTNET)**
```env
BYBIT_API_KEY=testnet-api-key
BYBIT_API_SECRET=testnet-secret
BYBIT_TESTNET=true  # ÖNEMLİ: Test için true
```

#### 📱 **Telegram Bot**
```env
TELEGRAM_BOT_TOKEN=bot-token-from-botfather
TELEGRAM_CHAT_ID=your-chat-id
```

#### 📊 **CoinGecko Market Data**
```env
COINGECKO_API_KEY=CG-your-api-key
```

---

## 🔄 Servis Kontrolü

### ✅ **Systemd Services**
```bash
# Service durumlarını kontrol et
systemctl status nginx
systemctl status php8.2-fpm
systemctl status postgresql
systemctl status redis-server
systemctl status sentinentx-queue
systemctl status sentinentx-telegram

# Servisleri başlat
systemctl start sentinentx-queue
systemctl start sentinentx-telegram
```

### ✅ **Log Kontrolü**
```bash
# Laravel logs
tail -f /var/www/sentinentx/storage/logs/laravel.log

# Telegram service logs
journalctl -u sentinentx-telegram -f

# Queue service logs
journalctl -u sentinentx-queue -f

# Nginx access logs
tail -f /var/log/nginx/access.log
```

---

## 🧪 Fonksiyonel Test Checklist

### 1. **Web Interface Test** ✅
```bash
# Health check
curl http://your-server-ip/api/health

# Status endpoint
curl http://your-server-ip/api/status
```

### 2. **Telegram Bot Test** ✅
```
/help          # Komut listesi
/status        # Sistem durumu
/scan          # 4 coin taraması
/balance       # Testnet bakiye
/pnl           # Günlük kar/zarar
```

### 3. **Database Test** ✅
```bash
# PostgreSQL connection
sudo -u postgres psql -d sentinentx -c "SELECT COUNT(*) FROM users;"

# Redis connection
redis-cli -a your-redis-password ping
```

### 4. **API Provider Test** ✅
```bash
# Test AI providers
php artisan tinker
>>> app(\App\Services\Market\CoinGeckoService::class)->getCoinData('BTCUSDT');
```

---

## 📊 Performance Monitoring

### ✅ **System Resources**
```bash
# CPU and Memory usage
htop

# Disk usage
df -h

# Network usage
nethogs

# Process monitoring
top -p $(pgrep -d',' php)
```

### ✅ **Application Metrics**
```bash
# Laravel metrics
php artisan sentx:metrics

# Queue status
php artisan queue:monitor

# Cache status
php artisan cache:table
```

---

## 🔒 Güvenlik Checklist

### ✅ **Firewall**
```bash
# UFW status
ufw status

# Only required ports open
# 22 (SSH), 80 (HTTP), 443 (HTTPS)
```

### ✅ **File Permissions**
```bash
# Laravel permissions
ls -la /var/www/sentinentx/storage/
ls -la /var/www/sentinentx/bootstrap/cache/

# .env file security
ls -la /var/www/sentinentx/.env  # Should be 600
```

### ✅ **SSL Certificate (Optional)**
```bash
# Let's Encrypt SSL
apt install certbot python3-certbot-nginx
certbot --nginx -d your-domain.com
```

---

## 🚨 Alert System Test

### ✅ **Emergency Alerts**
```bash
# Test alert dispatch
php artisan tinker
>>> app(\App\Services\Notifier\AlertDispatcher::class)->send('critical', 'test', 'Emergency test alert');
```

### ✅ **Telegram Alert Delivery**
- Telegram'da test alerti alındı mı?
- Alert deduplication çalışıyor mu?
- Critical alertler anında iletiliyor mu?

---

## 📈 15 Günlük Test Planı

### **Hafta 1: Stabilite ve Temel Fonksiyonlar**

#### Gün 1-2: Kurulum ve Doğrulama
- [ ] VDS kurulumu tamamlandı
- [ ] Tüm servisler çalışıyor
- [ ] Telegram bot cevap veriyor
- [ ] Database bağlantısı aktif
- [ ] Temel komutlar test edildi

#### Gün 3-4: AI Provider Testleri
- [ ] OpenAI API çalışıyor
- [ ] Gemini API çalışıyor
- [ ] Grok API çalışıyor
- [ ] Multi-coin analysis çalışıyor
- [ ] Consensus decision working

#### Gün 5-7: Trading Simulation (Testnet)
- [ ] Bybit testnet bağlantısı
- [ ] Position açma/kapama test
- [ ] Risk profilleri test edildi
- [ ] SL/TP calculations working
- [ ] Position monitoring active

### **Hafta 2: Performance ve Production Hazırlığı**

#### Gün 8-10: Load Testing
- [ ] Concurrent user simulation
- [ ] High frequency API calls
- [ ] Database performance test
- [ ] Memory usage monitoring
- [ ] CPU optimization test

#### Gün 11-12: Error Handling
- [ ] API failure scenarios
- [ ] Network interruption test
- [ ] Database connection loss
- [ ] Alert system reliability
- [ ] Auto-recovery mechanisms

#### Gün 13-14: Security Audit
- [ ] Penetration testing
- [ ] API key security check
- [ ] Input validation test
- [ ] SQL injection prevention
- [ ] Rate limiting effectiveness

#### Gün 15: Production Deployment
- [ ] Testnet to mainnet migration
- [ ] Live trading configuration
- [ ] Final security review
- [ ] Backup procedures tested
- [ ] Rollback plan ready

---

## 🎯 Success Criteria

### ✅ **Minimum Requirements**
- [ ] 99% uptime during test period
- [ ] <500ms API response time
- [ ] All Telegram commands working
- [ ] Zero critical security issues
- [ ] Successful AI consensus decisions

### ✅ **Performance Targets**
- [ ] <100ms database queries
- [ ] <2s multi-coin analysis
- [ ] <1s Telegram response time
- [ ] <50MB memory per process
- [ ] <5% CPU usage baseline

### ✅ **Reliability Goals**
- [ ] Automatic error recovery
- [ ] Graceful degradation
- [ ] Zero data loss
- [ ] Consistent alert delivery
- [ ] Stable position monitoring

---

## 🆘 Troubleshooting Guide

### Common Issues

#### 1. **High Memory Usage**
```bash
# Check PHP processes
ps aux | grep php | head -10

# Restart services
systemctl restart sentinentx-queue
systemctl restart php8.2-fpm
```

#### 2. **Database Connection Errors**
```bash
# Check PostgreSQL status
systemctl status postgresql

# Check connections
sudo -u postgres psql -c "SELECT count(*) FROM pg_stat_activity;"
```

#### 3. **Telegram Bot Not Responding**
```bash
# Check service logs
journalctl -u sentinentx-telegram --since "10 minutes ago"

# Restart telegram service
systemctl restart sentinentx-telegram
```

#### 4. **API Rate Limiting**
```bash
# Check API usage logs
grep "rate_limit" /var/www/sentinentx/storage/logs/laravel.log

# Monitor API calls
watch -n 1 'grep "API_CALL" /var/www/sentinentx/storage/logs/laravel.log | tail -5'
```

---

## 💰 Cost Optimization

### ✅ **15 Günlük Budget**
- **DigitalOcean**: $24 (recommended)
- **Linode**: $18 (performance)
- **Hetzner**: €6.5 (budget)

### ✅ **Cost Monitoring**
- Daily VDS costs tracking
- API usage monitoring
- Data transfer limits
- Storage optimization

---

## 🎉 Final Production Checklist

### Before Going Live
- [ ] All tests passed
- [ ] Security audit completed
- [ ] Backup strategy implemented
- [ ] Monitoring alerts configured
- [ ] Documentation updated
- [ ] Team training completed

### Launch Day
- [ ] Testnet → Mainnet migration
- [ ] API keys updated
- [ ] Trading limits configured
- [ ] Emergency contacts ready
- [ ] Rollback plan tested

---

**🚀 15 günlük test sonunda SentinentX production'a hazır olacak!**

**Success rate target: %95+ 💪**
