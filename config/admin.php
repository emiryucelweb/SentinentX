<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Security Configuration
    |--------------------------------------------------------------------------
    |
    | Bu dosya admin endpoint'leri için güvenlik ayarlarını içerir.
    |
    */

    'allowed_ips' => env('ADMIN_ALLOWED_IPS', '127.0.0.1,::1'),

    'hmac_secret' => env('ADMIN_HMAC_SECRET', 'change-this-in-production'),

    'rate_limit' => [
        'max_requests' => env('ADMIN_RATE_LIMIT_MAX', 100),
        'decay_minutes' => env('ADMIN_RATE_LIMIT_DECAY', 1),
    ],

    'endpoints' => [
        'open_now' => [
            'enabled' => env('ADMIN_OPEN_NOW_ENABLED', true),
            'require_auth' => env('ADMIN_OPEN_NOW_AUTH', true),
        ],
        'status' => [
            'enabled' => env('ADMIN_STATUS_ENABLED', true),
            'require_auth' => env('ADMIN_STATUS_AUTH', false),
        ],
    ],
];
