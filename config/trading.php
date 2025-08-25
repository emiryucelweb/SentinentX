<?php

declare(strict_types=1);

return [
    'mode' => [
        'account' => 'ONE_WAY',
        'margin' => 'CROSS',
        'max_leverage' => 75,
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
    'lab' => [
        'testnet_duration_days' => 15,
        'mainnet_thresholds' => ['pf' => 1.2, 'max_dd_pct' => 15.0, 'sharpe' => 0.8],
    ],
];
