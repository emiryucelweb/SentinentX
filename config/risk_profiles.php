<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | User Risk Profiles
    |--------------------------------------------------------------------------
    | 3 farklı risk profili. Her üye kendi profilini seçer.
    | Bu ayarlar AI'ların kararlarını ve pozisyon yönetimini etkiler.
    */

    'profiles' => [
        'conservative' => [
            'name' => 'Düşük Risk',
            'description' => 'Güvenli yatırım, düşük volatilite',
            'color' => '#10B981', // Yeşil
            'icon' => '🛡️',

            // Kaldıraç ayarları (YENİ)
            'leverage' => [
                'min' => 3,
                'max' => 15,
                'default' => 5,
            ],

            // Risk yönetimi (GÜNCELLENDİ)
            'risk' => [
                'daily_profit_target_pct' => 20.0,   // Günlük %20 kar hedefi
                'per_trade_risk_pct' => 0.5,         // İşlem başına %0.5 risk
                'max_concurrent_positions' => 2,      // Aynı anda max 2 pozisyon
                'stop_loss_pct' => 3.0,              // %3 stop loss
                'take_profit_pct' => 6.0,            // %6 take profit (2:1 risk/reward)
                'correlation_threshold' => 0.7,       // Düşük korelasyon eşiği
            ],

            // Pozisyon büyüklüğü (GÜNCELLENDİ)
            'position_sizing' => [
                'equity_usage_pct' => 50.0,          // Sermayenin %50'si kullanılabilir
                'base_qty_multiplier' => 1.0,        // Normal pozisyon büyüklüğü
            ],

            // AI güven eşikleri
            'ai_thresholds' => [
                'min_confidence' => 80,               // Yüksek güven gerektir
                'consensus_requirement' => 3,         // 3/3 AI anlaşması
                'veto_sensitivity' => 'high',         // Yüksek veto hassasiyeti
            ],

            // Zaman bazlı kısıtlar (GÜNCELLENDİ)
            'timing' => [
                'new_position_interval_hours' => 2,   // Her 2 saatte bir yeni pozisyon
                'position_check_minutes' => 3,        // 3 dakikada bir pozisyon kontrolü
                'market_hours_only' => false,         // 7/24 işlem
                'avoid_news_minutes' => 30,           // Haber öncesi/sonrası 30dk bekle
            ],
        ],

        'moderate' => [
            'name' => 'Orta Risk',
            'description' => 'Dengeli yaklaşım, orta volatilite',
            'color' => '#F59E0B', // Turuncu
            'icon' => '⚖️',

            // Kaldıraç ayarları (GÜNCELLENDİ)
            'leverage' => [
                'min' => 15,
                'max' => 45,
                'default' => 25,
            ],

            // Risk yönetimi (GÜNCELLENDİ)
            'risk' => [
                'daily_profit_target_pct' => 50.0,   // Günlük %50 kar hedefi
                'per_trade_risk_pct' => 1.0,         // İşlem başına %1 risk
                'max_concurrent_positions' => 3,      // Aynı anda max 3 pozisyon
                'stop_loss_pct' => 4.0,              // %4 stop loss
                'take_profit_pct' => 8.0,            // %8 take profit (2:1 risk/reward)
                'correlation_threshold' => 0.8,       // Orta korelasyon eşiği
            ],

            // Pozisyon büyüklüğü (GÜNCELLENDİ)
            'position_sizing' => [
                'equity_usage_pct' => 30.0,          // Sermayenin %30'u kullanılabilir
                'base_qty_multiplier' => 1.5,        // 1.5x pozisyon büyüklüğü
            ],

            // AI güven eşikleri
            'ai_thresholds' => [
                'min_confidence' => 70,               // Orta güven yeterli
                'consensus_requirement' => 2,         // 2/3 AI anlaşması
                'veto_sensitivity' => 'medium',       // Orta veto hassasiyeti
            ],

            // Zaman bazlı kısıtlar (GÜNCELLENDİ)
            'timing' => [
                'new_position_interval_hours' => 2,   // Her 2 saatte bir yeni pozisyon
                'position_check_minutes' => 1.5,      // 1.5 dakikada bir pozisyon kontrolü
                'market_hours_only' => false,         // 7/24 işlem
                'avoid_news_minutes' => 15,           // Haber öncesi/sonrası 15dk bekle
            ],
        ],

        'aggressive' => [
            'name' => 'Yüksek Risk',
            'description' => 'Maksimum getiri odaklı, yüksek volatilite',
            'color' => '#EF4444', // Kırmızı
            'icon' => '🚀',

            // Kaldıraç ayarları (GÜNCELLENDİ)
            'leverage' => [
                'min' => 45,
                'max' => 75,
                'default' => 60,
            ],

            // Risk yönetimi (GÜNCELLENDİ)
            'risk' => [
                'daily_profit_target_pct' => 150.0,  // Günlük %100-%200 kar hedefi (ortalama %150)
                'per_trade_risk_pct' => 2.0,         // İşlem başına %2 risk
                'max_concurrent_positions' => 4,      // Aynı anda max 4 pozisyon
                'stop_loss_pct' => 5.0,              // %5 stop loss
                'take_profit_pct' => 10.0,           // %10 take profit (2:1 risk/reward)
                'correlation_threshold' => 0.9,       // Yüksek korelasyon eşiği
            ],

            // Pozisyon büyüklüğü (GÜNCELLENDİ)
            'position_sizing' => [
                'equity_usage_pct' => 20.0,          // Sermayenin %20'si kullanılabilir
                'base_qty_multiplier' => 2.0,        // 2x pozisyon büyüklüğü
            ],

            // AI güven eşikleri
            'ai_thresholds' => [
                'min_confidence' => 60,               // Düşük güven yeterli
                'consensus_requirement' => 1,         // 1/3 AI anlaşması bile yeterli
                'veto_sensitivity' => 'low',          // Düşük veto hassasiyeti
            ],

            // Zaman bazlı kısıtlar (GÜNCELLENDİ)
            'timing' => [
                'new_position_interval_hours' => 2,   // Her 2 saatte bir yeni pozisyon
                'position_check_minutes' => 1,        // 1 dakikada bir pozisyon kontrolü
                'market_hours_only' => false,         // 7/24 işlem
                'avoid_news_minutes' => 5,            // Haber öncesi/sonrası 5dk bekle
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Risk Profile
    |--------------------------------------------------------------------------
    | Yeni kullanıcılar için varsayılan risk profili
    */
    'default_profile' => 'moderate',

    /*
    |--------------------------------------------------------------------------
    | Risk Profile Switching
    |--------------------------------------------------------------------------
    | Kullanıcılar risk profilini ne sıklıkla değiştirebilir
    */
    'profile_change' => [
        'cooldown_hours' => 24,              // 24 saat bekleme süresi
        'max_changes_per_month' => 3,        // Ayda max 3 değişiklik
        'requires_confirmation' => true,      // Değişiklik için onay gerekli
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Profile Validation
    |--------------------------------------------------------------------------
    | Her profil için minimum gereksinimler
    */
    'validation' => [
        'conservative' => [
            'min_account_value' => 1000,     // Min $1000 hesap değeri
            'trading_experience' => 'beginner', // Başlangıç seviyesi
        ],
        'moderate' => [
            'min_account_value' => 5000,     // Min $5000 hesap değeri
            'trading_experience' => 'intermediate', // Orta seviye
        ],
        'aggressive' => [
            'min_account_value' => 10000,    // Min $10000 hesap değeri
            'trading_experience' => 'advanced', // İleri seviye
            'risk_acknowledgment' => true,    // Risk onayı gerekli
        ],
    ],
];
