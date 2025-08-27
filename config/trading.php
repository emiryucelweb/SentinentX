<?php

declare(strict_types=1);

return [
    'mode' => [
        'account' => 'ONE_WAY',
        'margin' => 'CROSS',
        'max_leverage' => 75,
    ],

    // Risk Profiles - User selectable risk levels
    'risk_profiles' => [
        'conservative' => [
            'daily_profit_target_pct' => 20.0,
            'capital_usage_pct' => 50.0,
            'leverage' => ['min' => 3, 'max' => 15],
            'position_check_interval_minutes' => 3,
            'stop_loss_pct' => 1.5,
            'take_profit_pct' => 2.5,
        ],
        'moderate' => [
            'daily_profit_target_pct' => 50.0,
            'capital_usage_pct' => 30.0,
            'leverage' => ['min' => 15, 'max' => 45],
            'position_check_interval_minutes' => 1.5,
            'stop_loss_pct' => 2.0,
            'take_profit_pct' => 4.0,
        ],
        'aggressive' => [
            'daily_profit_target_pct' => 150.0, // 100-200% range
            'capital_usage_pct' => 20.0,
            'leverage' => ['min' => 45, 'max' => 75],
            'position_check_interval_minutes' => 1,
            'stop_loss_pct' => 3.0,
            'take_profit_pct' => 6.0,
        ],
    ],
    'risk' => [
        'daily_max_loss_pct' => 20.0,
        'liq_buffer_k' => 1.2,
        'per_trade_risk_pct' => 1.0,
        'funding_window_minutes' => 5,
        'funding_limit_bps' => 30,
        'corr_threshold' => 0.85,
        'enable_composite_gate' => true, // CycleRunner entegrasyonunu aç/kapat (GEÇİCİ: AÇIK)
        'cooldown_minutes' => 60,
        'max_concurrent_positions' => 4,
        'atr_k' => 1.5, // ATR stop loss çarpanı
        'tp_k' => 3.0,  // ATR take profit çarpanı

        // ATR Güvenlik Parametreleri
        'base_volatility_pct' => env('TRADING_BASE_VOLATILITY_PCT', 0.003), // %0.3 base volatility
        'min_atr_threshold' => env('TRADING_MIN_ATR_THRESHOLD', 0.0001), // Minimum ATR threshold
        'max_atr_threshold' => env('TRADING_MAX_ATR_THRESHOLD', 0.1), // Maximum ATR threshold (%10)

        // Position Sizing Güvenlik Parametreleri
        'position_sizing' => [
            'max_qty_multiplier' => env('TRADING_MAX_QTY_MULTIPLIER', 10.0), // Equity'nin maksimum 10x'i
            'min_unit_risk_threshold' => env('TRADING_MIN_UNIT_RISK_THRESHOLD', 0.0001), // Minimum unit risk
            'max_qty_absolute' => env('TRADING_MAX_QTY_ABSOLUTE', 1000000), // Maksimum mutlak qty
            'safe_division_enabled' => env('TRADING_SAFE_DIVISION_ENABLED', true), // Safe division aktif
            'overflow_protection' => env('TRADING_OVERFLOW_PROTECTION', true), // Overflow koruması aktif
            'qty_precision' => env('TRADING_QTY_PRECISION', 8), // Qty hassasiyeti
        ],

        // Sembol-bazlı override'lar
        'symbols' => [
            'BTCUSDT' => [
                'funding_limit_bps' => 25, // BTC için daha sıkı funding kontrolü
                'corr_threshold' => 0.80,  // BTC için daha düşük korelasyon eşiği
                'max_qty_multiplier' => 8.0, // BTC için daha düşük qty multiplier
                'volatility_pct' => 0.002, // BTC için daha düşük volatility (%0.2)
            ],
            'ETHUSDT' => [
                'funding_limit_bps' => 35, // ETH için daha gevşek funding kontrolü
                'corr_threshold' => 0.90,  // ETH için daha yüksek korelasyon eşiği
                'max_qty_multiplier' => 12.0, // ETH için daha yüksek qty multiplier
                'volatility_pct' => 0.004, // ETH için daha yüksek volatility (%0.4)
            ],
        ],
    ],
    'execution' => [
        'slippage_cap_pct' => 0.05,
        'atr' => ['length' => 14, 'timeframe' => '1h'],
        'stop_limit_enabled' => false,
        'postonly_fallback' => true, // PostOnly → Market IOC fallback
        'twap_enabled' => false,     // TWAP execution (gelecek özellik)
    ],
    'ai' => [
        'min_confidence' => 60,
        'timeout_seconds' => 30,
        'retry_attempts' => 1,
    ],
    'symbols' => ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'],

    // 4 Ana Coin Özel Ayarları
    'symbol_configs' => [
        'BTCUSDT' => [
            'display_name' => 'Bitcoin',
            'symbol' => 'BTC',
            'priority' => 1,                    // En yüksek öncelik
            'min_notional' => 5.0,             // Min $5 işlem
            'tick_size' => 0.1,                // $0.1 fiyat adımı
            'qty_precision' => 6,              // 0.000001 BTC hassasiyeti
            'max_leverage' => 75,              // Max 75x kaldıraç
            'funding_check_enabled' => true,   // Funding kontrolü aktif
        ],
        'ETHUSDT' => [
            'display_name' => 'Ethereum',
            'symbol' => 'ETH',
            'priority' => 2,
            'min_notional' => 5.0,
            'tick_size' => 0.01,               // $0.01 fiyat adımı
            'qty_precision' => 5,              // 0.00001 ETH hassasiyeti
            'max_leverage' => 75,
            'funding_check_enabled' => true,
        ],
        'SOLUSDT' => [
            'display_name' => 'Solana',
            'symbol' => 'SOL',
            'priority' => 3,
            'min_notional' => 5.0,
            'tick_size' => 0.001,              // $0.001 fiyat adımı
            'qty_precision' => 4,              // 0.0001 SOL hassasiyeti
            'max_leverage' => 50,              // Max 50x kaldıraç
            'funding_check_enabled' => true,
        ],
        'XRPUSDT' => [
            'display_name' => 'Ripple',
            'symbol' => 'XRP',
            'priority' => 4,
            'min_notional' => 5.0,
            'tick_size' => 0.0001,             // $0.0001 fiyat adımı
            'qty_precision' => 3,              // 0.001 XRP hassasiyeti
            'max_leverage' => 50,
            'funding_check_enabled' => true,
        ],
    ],
    'lab' => [
        'testnet_duration_days' => 15,
        'mainnet_thresholds' => ['pf' => 1.2, 'max_dd_pct' => 15.0, 'sharpe' => 0.8],
    ],
];
