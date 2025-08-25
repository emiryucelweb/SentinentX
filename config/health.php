<?php

declare(strict_types=1);

return [
    'stablecoin' => [
        'depeg_threshold' => env('STABLECOIN_DEPEG_THRESHOLD', 0.98),
        'check_interval' => env('STABLECOIN_CHECK_INTERVAL', 300),
    ],
    'announcement' => [
        'check_interval' => env('ANNOUNCEMENT_CHECK_INTERVAL', 600),
        'telegram_channel' => env('ANNOUNCEMENT_TELEGRAM_CHANNEL'),
    ],
];
