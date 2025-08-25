<?php

declare(strict_types=1);

return [
    'bybit' => [
        'testnet' => env('BYBIT_TESTNET', true),
        'api_key' => env('BYBIT_API_KEY'),
        'api_secret' => env('BYBIT_API_SECRET'),
        'recv_window' => env('BYBIT_RECV_WINDOW', 15000),
        'account_type' => 'UNIFIED',
        'account_mode' => 'ONE_WAY',
        'default_margin_mode' => 'CROSS',
        'category' => 'linear',
        'timeout' => env('BYBIT_TIMEOUT', 30),
        'retry_attempts' => env('BYBIT_RETRY_ATTEMPTS', 3),
        'endpoints' => [
            'rest' => [
                'testnet' => 'https://api-testnet.bybit.com',
                'mainnet' => 'https://api.bybit.com',
            ],
            'ws' => [
                'public' => [
                    'testnet' => 'wss://stream-testnet.bybit.com/v5/public/linear',
                    'mainnet' => 'wss://stream.bybit.com/v5/public/linear',
                ],
                'private' => [
                    'testnet' => 'wss://stream-testnet.bybit.com/v5/private',
                    'mainnet' => 'wss://stream.bybit.com/v5/private',
                ],
            ],
        ],
        'rate_limiting' => [
            'enabled' => env('BYBIT_RATE_LIMITING', true),
            'max_requests_per_second' => env('BYBIT_MAX_REQUESTS_PER_SECOND', 10),
            'max_requests_per_minute' => env('BYBIT_MAX_REQUESTS_PER_MINUTE', 600),
        ],
    ],
];
