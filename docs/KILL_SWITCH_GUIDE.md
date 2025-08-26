# 🔴 Kill-Switch Kullanım Kılavuzu

## Genel Bakış

SentinentX'te kill-switch sistemi, acil durumlarda tüm trading işlemlerini anında durdurmak için kullanılır.

## 🔧 Kurulum

### 1. Environment Variable

```bash
# .env dosyasına ekle
TRADING_KILL_SWITCH=false
```

### 2. Config Cache

```bash
php artisan config:cache
```

## 🚨 Acil Durum Komutları

### Trading'i Tamamen Durdur
```bash
# Tüm trading işlemlerini durdur
export TRADING_KILL_SWITCH=true
php artisan config:cache

# Veya .env'de değiştir
echo "TRADING_KILL_SWITCH=true" >> .env
php artisan config:cache
```

### Maintenance Mode
```bash
# Laravel maintenance mode
php artisan down --message="Trading system maintenance" --retry=300

# Sadece trading'i kapat
export TRADING_KILL_SWITCH=true
php artisan config:cache
```

### Pozisyonları Kapat
```bash
# Tüm açık pozisyonları kapat
php artisan sentx:close-all-positions

# Belirli sembol pozisyonlarını kapat
php artisan sentx:close-positions --symbol=BTCUSDT
```

## 📋 Kill-Switch Kontrol Listesi

### ✅ Öncesi
- [ ] Tüm açık pozisyonları listele
- [ ] Risk değerlendirmesi yap
- [ ] Backup al
- [ ] Ekip bilgilendir

### 🔴 Sırasında
- [ ] `TRADING_KILL_SWITCH=true` yap
- [ ] Config cache temizle
- [ ] Pozisyonları kapat
- [ ] Queue worker'ları durdur
- [ ] Scheduler'ı durdur

### ✅ Sonrası
- [ ] Log'ları kontrol et
- [ ] Pozisyon durumunu doğrula
- [ ] Sistem sağlığını kontrol et
- [ ] Ekip bilgilendir

## 🚨 Acil Durum Senaryoları

### 1. Sistem Hata
```bash
# Hızlı durdurma
export TRADING_KILL_SWITCH=true
php artisan config:cache
php artisan queue:restart
```

### 2. Risk Limit Aşımı
```bash
# Pozisyonları kapat
php artisan sentx:close-all-positions

# Trading'i durdur
export TRADING_KILL_SWITCH=true
php artisan config:cache
```

### 3. API Sorunu
```bash
# Bybit bağlantısını kes
export BYBIT_API_KEY=""
export BYBIT_API_SECRET=""
```

## 🔒 Kill-Switch Davranışı

### RiskGuard Entegrasyonu
* `.env` → `TRADING_KILL_SWITCH=true` yaptığınızda **RiskGuard** yeni pozisyon açılmasını `reasons=['KILL_SWITCH']` ile engeller.
* LAB & raporlama akışları etkilenmez; yalnızca canlı açılış kapatılır.
* Canlıya çıkmadan önce `TRADING_KILL_SWITCH=false` olarak bırakıp yalnızca LAB/Testnet'i açık tutabilirsiniz.

### Not: Bu değişken zaten RiskGuard kompozit kapısında okunacak şekilde tasarlandı. (Aksi durumda aynı isimli flag'i okuyan kısa devre kontrolünü RiskGuard'a ekleyin.)

## 📊 Kill-Switch Durumu Kontrol

### Environment Kontrol
```bash
# Kill-switch durumunu kontrol et
php artisan tinker --execute="echo 'Kill-Switch: ' . (config('trading.kill_switch') ? 'ACTIVE' : 'INACTIVE');"
```

### RiskGuard Log Kontrol
```bash
# RiskGuard log'larını kontrol et
tail -f storage/logs/laravel.log | grep "KILL_SWITCH"
```

## 🔄 Kill-Switch Kaldırma

### Trading'i Tekrar Aktif Et
```bash
# Kill-switch'i kapat
export TRADING_KILL_SWITCH=false
php artisan config:cache

# Sistem sağlığını kontrol et
bash scripts/health-check.sh

# Test pozisyonu aç (LAB modunda)
php artisan sentx:lab-scan --symbol=BTCUSDT --count=1
```

## ⚠️ Önemli Notlar

1. **Kill-switch aktifken** yeni pozisyon açılamaz
2. **Mevcut pozisyonlar** etkilenmez (sadece yeni açılışlar engellenir)
3. **LAB sistemi** çalışmaya devam eder
4. **Raporlama** normal şekilde devam eder
5. **Config cache** her değişiklikten sonra temizlenmelidir

## 🚀 Production Deployment

### Canary Deployment Öncesi
```bash
# Kill-switch'i kapat
export TRADING_KILL_SWITCH=false
php artisan config:cache

# Sistem sağlığını kontrol et
bash scripts/health-check.sh

# Test pozisyonu aç
php artisan sentx:lab-scan --symbol=BTCUSDT --count=1
```

### Canary Deployment Sırasında
```bash
# Kill-switch'i hazır tut
export TRADING_KILL_SWITCH=false
php artisan config:cache

# Monitoring aktif
tail -f storage/logs/laravel.log
```

### Acil Durum
```bash
# Hızlı durdurma
export TRADING_KILL_SWITCH=true
php artisan config:cache

# Pozisyonları kapat
php artisan sentx:close-all-positions
```
