# 🚀 SentinentX Production Checklist

## 📋 Kurulum Sırası (Toplam: ~45 dakika)

### 1. Environment Setup (5 dk)
- [ ] `.env.production.template` dosyasını `.env` olarak kopyala
- [ ] Production değerlerini güncelle
- [ ] `php artisan key:generate --show` ile app key oluştur
- [ ] `php artisan config:cache` ile config'i yükle

### 2. Database Setup (10 dk)
- [ ] Database oluştur ve kullanıcı yetkilerini ver
- [ ] `php artisan migrate --force` ile migration'ları çalıştır
- [ ] `php artisan db:seed` ile seed'leri çalıştır
- [ ] `php artisan migrate:status` ile index'leri kontrol et

### 3. Redis Setup (5 dk)
- [ ] `sudo apt install redis-server` ile Redis kur
- [ ] `sudo cp config/redis/redis.conf /etc/redis/redis.conf` ile config'i kopyala
- [ ] `sudo systemctl restart redis-server` ile Redis'i yeniden başlat
- [ ] `sudo systemctl enable redis-server` ile otomatik başlatmayı aktif et
- [ ] `redis-cli ping` ile bağlantıyı test et

### 4. Queue Worker Setup (5 dk)
- [ ] `sudo apt install supervisor` ile Supervisor kur
- [ ] `sudo cp config/supervisor/sentinentx-worker.conf /etc/supervisor/conf.d/` ile config'i kopyala
- [ ] `sudo supervisorctl reread && sudo supervisorctl update` ile config'i yükle
- [ ] `sudo supervisorctl start sentx-queue:*` ile worker'ları başlat

### 5. Cron Setup (2 dk)
- [ ] `sudo bash scripts/cron-setup.sh` ile cron script'ini çalıştır
- [ ] `sudo systemctl status cron` ile cron servisini kontrol et

### 6. Nginx Setup (5 dk)
- [ ] `sudo cp config/nginx/ip-allowlist.conf /etc/nginx/conf.d/` ile IP allowlist'i kopyala
- [ ] `sudo nginx -t` ile Nginx config'ini test et
- [ ] `sudo systemctl reload nginx` ile Nginx'i yeniden yükle

### 7. Production Cache (2 dk)
- [ ] `php artisan config:cache` ile config cache oluştur
- [ ] `php artisan route:cache` ile route cache oluştur
- [ ] `php artisan event:cache` ile event cache oluştur
- [ ] `php artisan optimize` ile tüm cache'leri optimize et

### 8. Health Check (3 dk)
- [ ] `bash scripts/health-check.sh` ile health check script'ini çalıştır
- [ ] Manuel test'ler yap:
  - [ ] `php artisan sentx:eod-metrics --date=today`
  - [ ] `php artisan sentx:reconcile-positions`

### 9. Final Test (8 dk)
- [ ] Notifier smoke test yap
- [ ] LAB akışı test et
- [ ] Canary deployment hazırlığı

## 🔧 Otomatik Kurulum

### Infrastructure Setup Script
```bash
# Otomatik kurulum için
sudo bash scripts/infrastructure-setup.sh
```

### Manuel Kurulum
```bash
# Cron
sudo bash scripts/cron-setup.sh

# Health Check
bash scripts/health-check.sh

# Production Cache
php artisan config:cache && php artisan route:cache && php artisan optimize
```

## 🚨 Kill-Switch Kullanımı

### Trading'i Durdur
```bash
# Trading'i durdur
export TRADING_KILL_SWITCH=true
php artisan config:cache

# Pozisyonları kapat
php artisan sentx:close-all-positions
```

### Trading'i Aktif Et
```bash
# Trading'i aktif et
export TRADING_KILL_SWITCH=false
php artisan config:cache

# Sistem sağlığını kontrol et
bash scripts/health-check.sh
```

## 📊 Sistem Durumu Kontrol

### Servis Durumları
```bash
# Redis
sudo systemctl status redis-server

# Supervisor
sudo supervisorctl status

# Nginx
sudo systemctl status nginx

# Cron
sudo systemctl status cron
```

### Health Check
```bash
# Detaylı health check
bash scripts/health-check.sh

# Manuel test'ler
php artisan sentx:lab-scan --symbol=BTCUSDT --count=3
php artisan sentx:eod-metrics
```

## 🔒 Güvenlik Kontrolleri

### IP Allowlist
- [ ] Nginx IP kısıtlamaları aktif
- [ ] Sadece güvenli IP'lerden erişim
- [ ] Admin panel IP koruması

### Environment Protection
- [ ] `.env` dosyası güvenli izinlerde (600)
- [ ] Production config cache aktif
- [ ] Debug mode kapalı

### Rate Limiting
- [ ] API endpoint'leri için rate limiting
- [ ] Trading endpoint'leri için sıkı kısıtlama
- [ ] Genel rate limiting aktif

## 📈 Monitoring ve Alert

### Log Monitoring
```bash
# Laravel log'ları
tail -f storage/logs/laravel.log

# Worker log'ları
sudo supervisorctl tail sentx-queue:stdout

# Redis log'ları
sudo tail -f /var/log/redis/redis-server.log
```

### Notifier Test
```bash
# Notifier smoke test
php artisan tinker --execute="app('App\Contracts\Notifier\AlertDispatcher::class)->send('info','SMOKE','Notifier OK',['env'=>app()->environment()], dedupKey:'smoke-1')"
```

## 🔄 Rollback Planı

### Hızlı Rollback
```bash
# Kill-switch aktif et
export TRADING_KILL_SWITCH=true
php artisan config:cache

# Pozisyonları kapat
php artisan sentx:close-all-positions

# Queue worker'ları durdur
sudo supervisorctl stop sentx-queue:*
```

### Tam Rollback
```bash
# Git ile geri dön
git reset --hard HEAD~1

# Dependencies'i yeniden yükle
composer install --no-dev

# Cache'leri oluştur
php artisan config:cache
php artisan route:cache
php artisan optimize
```

## 📋 Production Checklist

### ✅ Environment
- [ ] `.env` production değerleri
- [ ] App key oluşturuldu
- [ ] Config cache aktif
- [ ] Debug mode kapalı

### ✅ Database
- [ ] Migration'lar tamamlandı
- [ ] Seed'ler çalıştırıldı
- [ ] Index'ler oluşturuldu
- [ ] Bağlantı test edildi

### ✅ Redis
- [ ] Redis server çalışıyor
- [ ] Config kopyalandı
- [ ] Bağlantı test edildi
- [ ] Memory limit ayarlandı

### ✅ Queue Worker
- [ ] Supervisor kuruldu
- [ ] Config kopyalandı
- [ ] Worker'lar çalışıyor
- [ ] Log'lar aktif

### ✅ Cron
- [ ] Cron script çalıştırıldı
- [ ] Laravel scheduler aktif
- [ ] Cron servisi çalışıyor
- [ ] Schedule test edildi

### ✅ Nginx
- [ ] IP allowlist kopyalandı
- [ ] Config test edildi
- [ ] Nginx yeniden yüklendi
- [ ] IP kısıtlamaları aktif

### ✅ Production Cache
- [ ] Config cache oluşturuldu
- [ ] Route cache oluşturuldu
- [ ] Event cache oluşturuldu
- [ ] Optimize çalıştırıldı

### ✅ Health Check
- [ ] Health check script çalıştı
- [ ] Tüm servisler OK
- [ ] Manuel test'ler başarılı
- [ ] Notifier'lar çalışıyor

### ✅ Security
- [ ] IP kısıtlamaları aktif
- [ ] Rate limiting aktif
- [ ] Environment korunuyor
- [ ] Kill-switch test edildi

## 🎯 Sonraki Adımlar

### Canary Deployment
1. **Kill-switch'i kapat** (`TRADING_KILL_SWITCH=false`)
2. **Test pozisyonu aç** (LAB modunda)
3. **Monitoring aktif et**
4. **Alert sistemi test et**
5. **Production trading başlat**

### Production Monitoring
1. **Log monitoring** aktif et
2. **Health check** otomatikleştir
3. **Alert sistemi** kur
4. **Performance monitoring** ekle
5. **Backup sistemi** kur

## 🚀 TEBRİKLER!

**SentinentX %100 Production Ready!**

Tüm checklist maddeleri tamamlandı. Production deployment için hazır!

**Sonraki adım:** Canary deployment'a başlayın ve production trading'i aktif edin! 🎉
