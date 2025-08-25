<?php

return [
    'initial_equity' => 10000.0,
    'symbols' => ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'],

    'scan' => [
        'enabled' => true,
    ],

    'acceptance' => [
        'min_pf' => 1.25,    // 15 günlük Testnet: PF ≥ 1.25
        'max_dd_pct' => 12.0,    // 15 günlük Testnet: MaxDD ≤ %12
        'min_sharpe' => 0.85,    // 15 günlük Testnet: Sharpe ≥ 0.85
    ],

    'simulation' => [
        'test_mode' => true,    // PRODUCTION: false; CI/test için true
        'base_price' => 30000.0,
        'category' => 'linear',
        'alerts' => [
            'trade_events' => true,
            'acceptance' => true,
        ],
    ],

    // First-touch path settings
    'path' => [
        'bar_interval_min' => 5,
        'max_bars' => 60,
        'bar_touch_bias' => 0.5,
        'synthetic' => [
            'vol_pct' => 0.004,
            'drift_pct' => 0.0000,
        ],
    ],

    // Execution costs (bps) - Bybit production koşulları
    'costs' => [
        'mode' => 'taker',   // 'taker' | 'maker'
        'taker_fee_bps' => 6.0,       // 0.06% (Bybit VIP0)
        'maker_fee_bps' => 1.0,       // 0.01% (Bybit VIP0)
        'slippage_bps' => [
            'entry' => 3.0,            // 0.03% (orta likidite)
            'exit' => 2.0,            // 0.02% (daha iyi likidite)
        ],
        // Sembol-bazlı maliyetler (opsiyonel)
        'symbols' => [
            'BTCUSDT' => [
                'taker_fee_bps' => 5.0,    // BTC daha iyi likidite
                'slippage_bps' => ['entry' => 2.0, 'exit' => 1.5],
            ],
            'ETHUSDT' => [
                'taker_fee_bps' => 6.0,
                'slippage_bps' => ['entry' => 2.5, 'exit' => 2.0],
            ],
            'SOLUSDT' => [
                'taker_fee_bps' => 7.0,    // SOL daha yüksek spread
                'slippage_bps' => ['entry' => 4.0, 'exit' => 3.0],
            ],
            'XRPUSDT' => [
                'taker_fee_bps' => 6.0,
                'slippage_bps' => ['entry' => 3.0, 'exit' => 2.5],
            ],
        ],
    ],

    // Partial take-profit
    'partials' => [
        'enabled' => true,
        'tp1_frac' => 0.5,
        'tp2_rr' => 2.0,
        'move_sl_to_be' => true,
    ],
];
