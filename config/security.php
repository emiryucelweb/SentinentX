<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HMAC Authentication
    |--------------------------------------------------------------------------
    */
    'hmac_secret' => env('HMAC_SECRET', env('ADMIN_HMAC_SECRET', 'default-test-secret-key-change-in-production')),
    'hmac_ttl' => env('HMAC_TTL', 300), // 5 minutes

    /*
    |--------------------------------------------------------------------------
    | IP Allowlist Configuration
    |--------------------------------------------------------------------------
    */
    'allowlist' => [
        'cidrs' => array_filter(explode(',', (string) env('IP_ALLOWLIST', '127.0.0.1/32,::1/128'))),
        'mode' => env('IP_ALLOWLIST_MODE', 'deny'), // deny|allow
        'enabled' => env('IP_ALLOWLIST_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'api_calls' => env('RATE_LIMIT_API', 60), // per minute
        'commands' => env('RATE_LIMIT_COMMANDS', 10), // per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'hsts' => env('SECURITY_HSTS', true),
        'csp' => env('SECURITY_CSP', true),
        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'DENY'),
    ],
];
