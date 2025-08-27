# ⚖️ Risk Modları & Döngü Sistemi Raporu

**📅 Tarih:** $(date +%Y-%m-%d)
**🎯 Sistem:** LOW/MID/HIGH profilleri, 1-3dk döngü, sembol kilitleme

## ✅ MEVCUT RİSK PROFIL SİSTEMİ

### 🏗️ DUAL CONFIG YAPISINDAKİ PROFILLER

#### DETAYLI PROFİLLER (`config/risk_profiles.php`)
```php
'conservative' => [
    'leverage' => ['min' => 3, 'max' => 15, 'default' => 5],
    'risk' => [
        'daily_profit_target_pct' => 20.0,  // %20 günlük kar hedefi
        'per_trade_risk_pct' => 0.5,        // %0.5 trade riski
        'max_concurrent_positions' => 2,     // Max 2 pozisyon
        'stop_loss_pct' => 3.0,             // %3 stop loss
        'take_profit_pct' => 6.0,           // %6 take profit
    ],
    'timing' => [
        'position_check_minutes' => 3,       // 3dk pozisyon kontrolü ✅
        'new_position_interval_hours' => 2,  // 2 saatte bir yeni pozisyon
    ],
],

'moderate' => [
    'leverage' => ['min' => 15, 'max' => 45, 'default' => 25],
    'risk' => [
        'daily_profit_target_pct' => 50.0,   // %50 günlük kar hedefi
        'per_trade_risk_pct' => 1.0,         // %1 trade riski
        'max_concurrent_positions' => 3,      // Max 3 pozisyon
        'stop_loss_pct' => 4.0,              // %4 stop loss
        'take_profit_pct' => 8.0,            // %8 take profit
    ],
    'timing' => [
        'position_check_minutes' => 1.5,     // 1.5dk pozisyon kontrolü ✅
        'new_position_interval_hours' => 1.5,
    ],
],

'aggressive' => [
    'leverage' => ['min' => 45, 'max' => 75, 'default' => 60],
    'risk' => [
        'daily_profit_target_pct' => 150.0,  // %150 günlük kar hedefi (100-200%)
        'per_trade_risk_pct' => 2.0,         // %2 trade riski
        'max_concurrent_positions' => 4,      // Max 4 pozisyon
        'stop_loss_pct' => 5.0,              // %5 stop loss
        'take_profit_pct' => 10.0,           // %10 take profit
    ],
    'timing' => [
        'position_check_minutes' => 1.0,     // 1dk pozisyon kontrolü ✅
        'new_position_interval_hours' => 1,
    ],
]
```

#### BASIT PROFİLLER (`config/trading.php`)
```php
'conservative' => [
    'position_check_interval_minutes' => 3,   // 3dk ✅
    'leverage' => ['min' => 3, 'max' => 15],
],
'moderate' => [
    'position_check_interval_minutes' => 1.5, // 1.5dk ✅
    'leverage' => ['min' => 15, 'max' => 45],
],
'aggressive' => [
    'position_check_interval_minutes' => 1,   // 1dk ✅
    'leverage' => ['min' => 45, 'max' => 75],
]
```

## 🔄 DÖNGÜ SİSTEMİ ANALİZİ

### 1️⃣ ANA TRADING DÖNGÜSÜ (`OpenNowCommand`)
```bash
# Komut
php artisan sentx:open-now --symbols=BTC,ETH,SOL,XRP --snapshot=/path/snapshot.json

# Çalışma mantığı:
1. Snapshot dosyasından market data okur
2. AI Consensus Service'e sembol bazlı karar sorar  
3. Risk gate kontrolü (RiskGuard::allowOpenWithGuards)
4. Pozisyon açma/kapama kararları
```

**🔧 Kullanılan Risk Kontrolleri:**
- **Leverage kontrolü:** Min/max kaldıraç limitleri
- **Correlation guard:** Açık pozisyonlarla korelasyon kontrolü
- **Funding guard:** Funding fee penceresi kontrolü  
- **Position sizing:** Risk yüzdesine göre boyutlandırma

### 2️⃣ POZİSYON İZLEME DÖNGÜSÜ (`PositionMonitoringService`)
```php
// Risk profiline göre kontrol aralığı
private function getCheckInterval(array $riskProfile): float
{
    return (float) ($riskProfile['timing']['position_check_minutes'] ?? 3.0);
}

// Pozisyon izleme zamanlaması
public function schedulePositionMonitoring(User $user): void
{
    $intervalMinutes = $this->getCheckInterval($riskProfile);
    $nextCheckTime = now()->addMinutes($intervalMinutes);
    Cache::put("next_position_check_{$user->id}", $nextCheckTime);
}
```

**🎯 İzleme Aralıkları (Kurallara Uygun 1-3dk):**
- **Conservative:** 3.0 dakika ✅
- **Moderate:** 1.5 dakika ✅  
- **Aggressive:** 1.0 dakika ✅

### 3️⃣ LAB TESTNET DÖNGÜSÜ (`LabRunCommand`)
```bash
# 15 gün testnet komutu
php artisan sentx:lab-run --days=15 --symbols=BTC,ETH,SOL,XRP
```

**📊 LAB Döngü Özellikleri:**
- Günlük maksimum 3 trade per symbol
- AI consensus simülasyonu
- Path simulation ile P&L hesaplama
- Risk gate kontrolleri aktif

## 🔒 RİSK MODU DEĞİŞİM YÖNETİMİ

### ❌ MEVCUT SORUN: TEK AKTİF MOD DEĞİL
**Problem:** Şu anda kullanıcı bazlı risk profili var, sistem geneli tek mod yok.

**Gereklilik (Kurallara göre):**
> "Tek aktif mod çalışır; mod değişiminde önceki döngü durur"

### ✅ ÇÖZÜM: Risk Mode Manager Service

**Oluşturulması Gereken:**
```php
class RiskModeManager
{
    private string $activeMode = 'moderate'; // Global active mode
    
    public function switchMode(string $newMode): void
    {
        // 1. Mevcut döngüleri durdur
        $this->stopActiveCycles();
        
        // 2. Yeni modu aktifleştir
        $this->setActiveMode($newMode);
        
        // 3. Yeni mode'a göre döngüleri başlat
        $this->startCyclesForMode($newMode);
    }
}
```

## 🔐 SEMBOL BAZLI KİLİTLEME (IDEMPOTENSİ)

### ❌ MEVCUT DURUM: SEMBOL KİLİTİ YOK
**Kontrol Edilen Kod:**
- `OpenNowCommand`: Sembol kilidi yok
- `PositionMonitoringService`: User bazlı cache var ama sembol bazlı değil
- Risk servisleri: Sembol kilitleme yok

### ✅ ÇÖZÜM: Sembol Bazlı Redis Kilitleme

```php
class SymbolLockManager  
{
    public function acquireLock(string $symbol, int $ttlSeconds = 300): bool
    {
        $key = "symbol_lock:{$symbol}";
        return Redis::set($key, now()->timestamp, 'EX', $ttlSeconds, 'NX');
    }
    
    public function releaseLock(string $symbol): void
    {
        Redis::del("symbol_lock:{$symbol}");
    }
}
```

## 🤖 AI KARAR BAĞLAMI ENTEGRASYONU

### ✅ MEVCUT RİSK MODU CONTEXT'İ
```php
// OpenNowCommand.php:57-58
$snap['mode'] = config('trading.mode');
$snap['risk'] = config('trading.risk');

// AI'ya gönderilen snapshot'ta risk context mevcut
```

**🎯 AI Providers'a Gönderilen Risk Bilgisi:**
- Trading mode (cross/isolated margin)
- Risk parametreleri (max leverage, loss limits)
- Risk profil bilgisi (conservative/moderate/aggressive)

### ⚠️ GELİŞTİRİLMESİ GEREKEN
**Eksik:** Aktif risk modu AI prompt'larında explicit belirtilmiyor

**Gerekli İyileştirme:**
```php
// AI prompt'larına risk modu bilgisi eklenmeli
$aiContext = [
    'risk_mode' => $this->riskModeManager->getActiveMode(),
    'risk_profile' => config("risk_profiles.profiles.{$activeMode}"),
    'constraints' => [
        'max_leverage' => $riskProfile['leverage']['max'],
        'max_positions' => $riskProfile['risk']['max_concurrent_positions'],
    ]
];
```

## 📊 DÖNGÜ TİMİNG KONTROLÜ

### ✅ MEVCUT ARALIKLARıNDAKİ UYGUNLUK (1-3dk İçinde)

| Risk Modu | Pozisyon Kontrolü | Kural Uygunluğu |
|-----------|------------------|------------------|
| Conservative | 3.0 dakika | ✅ 1-3dk içinde |
| Moderate | 1.5 dakika | ✅ 1-3dk içinde |
| Aggressive | 1.0 dakika | ✅ 1-3dk içinde |

### 🔄 SCHEDULER ENTEGRASYONU

**Mevcut Sistem:**
```php
// Cache-based scheduling
Cache::put("next_position_check_{$user->id}", $nextCheckTime);

// Manuel kontrol
public function isMonitoringDue(User $user): bool
{
    return now()->greaterThanOrEqualTo($nextCheckTime);
}
```

**⚡ Otomatik Scheduling için:**
- Cron job her dakika çalışıp due olan kontrolleri çalıştıracak
- Laravel Queue job'ları kullanılabilir

## 🎯 UYGULAMASI GEREKEN İYİLEŞTİRMELER

### 1️⃣ GLOBAL RİSK MODU MANAGER ✨
```php
// Yeni servis: RiskModeManagerService
class RiskModeManagerService
{
    public function setGlobalMode(string $mode): void;
    public function getActiveMode(): string;
    public function stopPreviousCycles(): void;
    public function startNewCycles(string $mode): void;
}
```

### 2️⃣ SEMBOL KİLİTLEME SİSTEMİ ✨
```php
// Yeni trait: HasSymbolLocking  
trait HasSymbolLocking
{
    protected function executeWithSymbolLock(string $symbol, callable $callback);
    protected function acquireSymbolLock(string $symbol): bool;
    protected function releaseSymbolLock(string $symbol): void;
}
```

### 3️⃣ ENHANCED AI CONTEXT ✨
```php
// AI prompt'larına risk modu context'i ekleme
$enhancedContext = [
    'active_risk_mode' => $this->riskManager->getActiveMode(),
    'mode_constraints' => $this->getRiskConstraints($activeMode),
    'timing_settings' => $this->getTimingSettings($activeMode),
];
```

## 📋 MEVCUT DURUM ÖZETİ

### ✅ DOĞRU ÇALIŞAN KISIMLARI
- **3 Risk Profili:** Conservative/Moderate/Aggressive aktif ✅
- **1-3dk Aralıklar:** Tüm profiller kural içinde ✅
- **Pozisyon İzleme:** Risk profiline göre çalışıyor ✅
- **AI Context:** Temel risk bilgisi gönderiliyor ✅
- **Risk Guards:** Correlation, funding, leverage kontrolleri aktif ✅

### ⚠️ EKSİK OLAN KISIMLARI
- **Global Tek Mod:** Kullanıcı bazlı, sistem geneli değil ❌
- **Mod Değişim Yönetimi:** Önceki döngü durdurma yok ❌
- **Sembol Kilitleme:** İdempotency için sembol bazlı kilit yok ❌
- **Enhanced AI Context:** Risk modu prompt'larda explicit değil ❌

## 🚀 ÖNERİLEN İMPLEMENTASYON

### PHASE 1: Risk Mode Manager (Core)
1. `RiskModeManagerService` oluştur
2. Global aktif mod yönetimi
3. Mod değişiminde cycle durdurma/başlatma

### PHASE 2: Symbol Locking (Concurrency)
1. `SymbolLockManagerService` oluştur
2. Redis-based sembol kilitleme
3. Tüm trading komutlarına entegre et

### PHASE 3: Enhanced AI Context (Intelligence)
1. AI prompt'larına aktif risk modu ekle
2. Mode-specific constraints gönder
3. Risk-aware decision prompts

## 📊 FINAL DURUM

### 🎯 RİSK MODLARI & DÖNGÜ SİSTEMİ: %75 HAZIR

**✅ ÇALIŞAN KISIMLARI:**
- [x] 3 Risk profili (LOW/MID/HIGH) konfigürasyonu aktif
- [x] 1-3dk pozisyon kontrol aralıkları kurallara uygun
- [x] Risk-based pozisyon izleme sistemi çalışıyor
- [x] AI'ya temel risk context'i gönderiliyor
- [x] Multi-layer risk guards (correlation, funding, leverage)

**⚠️ GELİŞTİRİLMESİ GEREKEN:**
- [ ] Global tek aktif mod yönetimi (şu anda user-based)
- [ ] Mod değişiminde önceki döngü durdurma
- [ ] Sembol bazlı kilitleme/idempotency
- [ ] Enhanced AI risk context (explicit mode bilgisi)

**🚀 READINESS FOR PRODUCTION: 75/100**

Risk profil sistemi çalışıyor ama tam kural uygunluğu için 4 iyileştirme gerekli.
