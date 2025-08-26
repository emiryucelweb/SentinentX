# 🚀 Hızlı Rollback ve Kurulum Sırası

## 📋 Kurulum Sırası

### 1. Environment Setup (5 dk)
```bash
# .env template'ini kopyala
cp env.production.template .env

# Değerleri güncelle
nano .env

# App key oluştur
php artisan key:generate
```

### 2. Database Setup (10 dk)
```bash
# Migration'ları çalıştır
php artisan migrate --force

# Seed'leri çalıştır
php artisan db:seed

# Index'leri kontrol et
php artisan migrate:status
```

### 3. Redis Setup (5 dk)
```bash
# Redis kur
sudo apt install redis-server

# Config'i kopyala
sudo cp config/redis/redis.conf /etc/redis/redis.conf

# Redis'i yeniden başlat
sudo systemctl restart redis
sudo systemctl enable redis
```

### 4. Queue Worker Setup (5 dk)
```bash
# Supervisor kur
sudo apt install supervisor

# Config'i kopyala
sudo cp config/supervisor/sentinentx-worker.conf /etc/supervisor/conf.d/

# Supervisor'ı yeniden yükle
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sentx-queue:*
```

### 5. Cron Setup (2 dk)
```bash
# Cron script'ini çalıştır
sudo bash scripts/cron-setup.sh

# Cron servisini kontrol et
sudo systemctl status cron
```

### 6. Nginx Setup (5 dk)
```bash
# IP allowlist'i kopyala
sudo cp config/nginx/ip-allowlist.conf /etc/nginx/conf.d/

# Nginx'i test et
sudo nginx -t

# Nginx'i yeniden başlat
sudo systemctl reload nginx
```

### 7. Production Cache (2 dk)
```bash
# Cache'leri oluştur
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan optimize
```

### 8. Health Check (3 dk)
```bash
# Health check script'ini çalıştır
bash scripts/health-check.sh

# Manuel test'ler
php artisan sentx:eod-metrics --date=today
php artisan sentx:reconcile-positions
```

## 🔄 Hızlı Rollback

### Senaryo 1: Config Hatası
```bash
# Config cache'i temizle
php artisan config:clear

# .env'i eski haline getir
git checkout .env

# Config'i yeniden yükle
php artisan config:cache
```

### Senaryo 2: Database Sorunu
```bash
# Migration'ları geri al
php artisan migrate:rollback --step=1

# Veya tamamen geri al
php artisan migrate:reset

# Eski backup'tan restore et
mysql -u root -p sentx < backup.sql
```

### Senaryo 3: Redis Sorunu
```bash
# Redis'i yeniden başlat
sudo systemctl restart redis-server

# Cache'i temizle
php artisan cache:clear

# Redis bağlantısını test et
redis-cli ping
```

### Senaryo 4: Queue Worker Sorunu
```bash
# Supervisor worker'ları durdur
sudo supervisorctl stop sentx-queue:*

# Config'i yeniden yükle
sudo supervisorctl reread
sudo supervisorctl update

# Worker'ları başlat
sudo supervisorctl start sentx-queue:*
```

### Senaryo 5: Nginx Sorunu
```bash
# Nginx config'i test et
sudo nginx -t

# Eski config'e geri dön
sudo cp /etc/nginx/nginx.conf.backup /etc/nginx/nginx.conf

# Nginx'i yeniden başlat
sudo systemctl restart nginx
```

## 🚨 Acil Durum Rollback

### Trading'i Tamamen Durdur
```bash
# Kill-switch'i aktif et
export TRADING_KILL_SWITCH=true
php artisan config:cache

# Tüm pozisyonları kapat
php artisan sentx:close-all-positions

# Queue worker'ları durdur
sudo supervisorctl stop sentx-queue:*
```

### Sistem Geri Alma
```bash
# Git ile son commit'e dön
git reset --hard HEAD~1

# Composer dependencies'i yeniden yükle
composer install --no-dev

# Cache'leri temizle
php artisan optimize:clear

# Config'i yeniden yükle
php artisan config:cache
```

## 📊 Rollback Kontrol Listesi

### ✅ Rollback Öncesi
- [ ] Mevcut durumu backup al
- [ ] Ekip bilgilendir
- [ ] Rollback planını hazırla
- [ ] Test ortamında dene

### 🔄 Rollback Sırasında
- [ ] Servisleri durdur
- [ ] Config'leri geri al
- [ ] Database'i restore et
- [ ] Cache'leri temizle

### ✅ Rollback Sonrası
- [ ] Sistem sağlığını kontrol et
- [ ] Test pozisyonu aç
- [ ] Monitoring'i aktif et
- [ ] Ekip bilgilendir

## 🛠️ Otomatik Rollback Script

### Rollback Script Oluştur
```bash
#!/bin/bash
# scripts/rollback.sh

echo "🚨 SentinentX Rollback Başlıyor..."

# Kill-switch aktif et
export TRADING_KILL_SWITCH=true
php artisan config:cache

# Pozisyonları kapat
php artisan sentx:close-all-positions

# Queue worker'ları durdur
sudo supervisorctl stop sentx-queue:*

# Config cache'i temizle
php artisan config:clear

# Git ile geri dön
git reset --hard HEAD~1

# Dependencies'i yeniden yükle
composer install --no-dev

# Cache'leri oluştur
php artisan config:cache
php artisan route:cache
php artisan optimize

echo "✅ Rollback tamamlandı!"
```

## 📋 Rollback Sonrası Test

### Sistem Sağlığı
```bash
# Health check
bash scripts/health-check.sh

# Database test
php artisan migrate:status

# Redis test
redis-cli ping

# Queue test
php artisan queue:work --once
```

### Trading Test
```bash
# LAB scan test
php artisan sentx:lab-scan --symbol=BTCUSDT --count=1

# EOD metrics test
php artisan sentx:eod-metrics --date=today

# Position reconcile test
php artisan sentx:reconcile-positions
```

## 🔍 Rollback Log'ları

### Rollback Audit Trail
```bash
# Rollback log'u oluştur
echo "$(date): ROLLBACK EXECUTED - Reason: $1" >> storage/logs/rollback.log

# Git log kontrol
git log --oneline -5

# Config değişiklikleri
git diff HEAD~1 config/
```

## ⚠️ Önemli Notlar

1. **Rollback öncesi** mutlaka backup alın
2. **Ekip bilgilendirme** kritik
3. **Test ortamında** rollback'i deneyin
4. **Monitoring** aktif tutun
5. **Log'ları** detaylı tutun

## 🚀 Sonraki Adımlar

### Rollback Sonrası
1. **Root cause analysis** yapın
2. **Test coverage** artırın
3. **Monitoring** geliştirin
4. **Documentation** güncelleyin
5. **Team training** yapın

### Production Deployment
1. **Canary deployment** planlayın
2. **Rollback planı** hazırlayın
3. **Monitoring** kurun
4. **Alert sistemi** aktif edin
5. **Team communication** planlayın
